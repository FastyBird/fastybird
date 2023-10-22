<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Services
 * @since          1.0.0
 *
 * @date           16.10.23
 */

namespace FastyBird\Connector\Virtual\Drivers;

use DateTimeInterface;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Helpers;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Connector\Virtual\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette\Utils;
use React\Promise;
use function array_filter;
use function array_key_exists;
use function array_sum;
use function assert;
use function count;
use function is_bool;
use function is_float;
use function is_string;
use function max;
use function min;

/**
 * Thermostat service
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Services
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Thermostat implements Driver
{

	/** @var array<string, bool|null> */
	private array $heaters = [];

	/** @var array<string, bool|null> */
	private array $coolers = [];

	/** @var array<string, float|null> */
	private array $actualTemperature = [];

	/** @var array<string, float|null> */
	private array $actualFloorTemperature = [];

	/** @var array<string, bool|null> */
	private array $openingsState = [];

	private Types\ThermostatMode $mode;

	private Types\HvacMode $hvacMode;

	private DateTimeInterface|null $connectedAt = null;

	public function __construct(
		private readonly Entities\Devices\Thermostat $device,
		private readonly Helpers\Entity $entityHelper,
		private readonly Queue\Queue $queue,
		private readonly Virtual\Logger $logger,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStatesManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
	)
	{
		$this->mode = Types\ThermostatMode::get(Types\ThermostatMode::MANUAL);
		$this->hvacMode = Types\HvacMode::get(Types\HvacMode::OFF);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function connect(): Promise\PromiseInterface
	{
		foreach ($this->device->getActors() as $actor) {
			$state = $this->channelPropertiesStatesManager->readValue($actor);

			$actualValue = $state?->getActualValue();

			if ($actor instanceof DevicesEntities\Channels\Properties\Mapped) {
				$actualValue = Helpers\Transformer::fromMappedParent($actor, $actualValue);
			}

			$actualValue = Helpers\Transformer::normalizeValue(
				$actor->getDataType(),
				$actor->getFormat(),
				$actualValue,
			);

			if (Utils\Strings::startsWith($actor->getIdentifier(), Types\ChannelPropertyIdentifier::HEATER)) {
				$this->heaters[$actor->getId()->toString()] = is_bool($actualValue)
					? $actualValue
					: null;
			} elseif (Utils\Strings::startsWith($actor->getIdentifier(), Types\ChannelPropertyIdentifier::COOLER)) {
				$this->coolers[$actor->getId()->toString()] = is_bool($actualValue)
					? $actualValue
					: null;
			}
		}

		foreach ($this->device->getSensors() as $sensor) {
			$state = $this->channelPropertiesStatesManager->readValue($sensor);

			$actualValue = $state?->getActualValue();

			if ($sensor instanceof DevicesEntities\Channels\Properties\Mapped) {
				$actualValue = Helpers\Transformer::fromMappedParent($sensor, $actualValue);
			}

			$actualValue = Helpers\Transformer::normalizeValue(
				$sensor->getDataType(),
				$sensor->getFormat(),
				$actualValue,
			);

			if (Utils\Strings::startsWith($sensor->getIdentifier(), Types\ChannelPropertyIdentifier::FLOOR_SENSOR)) {
				$this->actualFloorTemperature[$sensor->getId()->toString()] = is_float($actualValue)
					? $actualValue
					: null;
			} elseif (Utils\Strings::startsWith(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::TARGET_SENSOR,
			)) {
				$this->actualTemperature[$sensor->getId()->toString()] = is_float($actualValue)
					? $actualValue
					: null;
			}
		}

		foreach ($this->device->getOpenings() as $opening) {
			$state = $this->channelPropertiesStatesManager->readValue($opening);

			$actualValue = $state?->getActualValue();

			if ($opening instanceof DevicesEntities\Channels\Properties\Mapped) {
				$actualValue = Helpers\Transformer::fromMappedParent($opening, $actualValue);
			}

			$actualValue = Helpers\Transformer::normalizeValue(
				$opening->getDataType(),
				$opening->getFormat(),
				$actualValue,
			);

			if (Utils\Strings::startsWith($opening->getIdentifier(), Types\ChannelPropertyIdentifier::SENSOR)) {
				$this->openingsState[$opening->getId()->toString()] = is_bool($actualValue) ? $actualValue : null;
			}
		}

		$this->connectedAt = $this->dateTimeFactory->getNow();

		return Promise\resolve(true);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function disconnect(): Promise\PromiseInterface
	{
		$this->setActorState(false, false);

		$this->actualTemperature = [];
		$this->actualFloorTemperature = [];

		$this->connectedAt = null;

		return Promise\resolve(true);
	}

	public function isConnected(): bool
	{
		return $this->connectedAt !== null;
	}

	public function isConnecting(): bool
	{
		return false;
	}

	public function getLastConnectAttempt(): DateTimeInterface|null
	{
		return $this->connectedAt;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function process(): Promise\PromiseInterface
	{
		if (!$this->isOpeningsClosed()) {
			$this->setActorState(false, false);

			return Promise\resolve(true);
		}

		if ($this->hvacMode->equalsValue(Types\HvacMode::OFF)) {
			$this->setActorState(false, false);

			return Promise\resolve(true);
		}

		$targetTemp = $this->device->getTargetTemp($this->mode);

		if ($targetTemp === null) {
			$this->setActorState(false, false);

			return Promise\reject(new Exceptions\InvalidState('Target temperature is not configured'));
		}

		$measuredTemp = array_filter($this->actualTemperature, static fn (float|null $temp): bool => $temp !== null);
		$measuredFloorTemp = array_filter(
			$this->actualFloorTemperature,
			static fn (float|null $temp): bool => $temp !== null,
		);

		$minActualTemp = min($measuredTemp);
		$maxActualTemp = max($measuredTemp);

		$targetTempLow = $targetTemp - ($this->device->getLowTargetTempTolerance() ?? 0);
		$targetTempHigh = $targetTemp + ($this->device->getHighTargetTempTolerance() ?? 0);

		if ($this->hvacMode->equalsValue(Types\HvacMode::HEAT)) {
			if (!$this->device->hasHeaters()) {
				$this->setActorState(false, false);

				return Promise\reject(new Exceptions\InvalidState('Thermostat has not configured any heater actor'));
			}

			if ($targetTempHigh >= $maxActualTemp) {
				$this->setActorState(false, false);
			} elseif ($targetTempLow <= $minActualTemp) {
				$this->setActorState(true, false);
			}
		} elseif ($this->hvacMode->equalsValue(Types\HvacMode::COOL)) {
			if (!$this->device->hasCoolers()) {
				$this->setActorState(false, false);

				return Promise\reject(new Exceptions\InvalidState('Thermostat has not configured any cooler actor'));
			}

			if ($targetTempHigh >= $maxActualTemp) {
				$this->setActorState(false, true);
			} elseif ($targetTempLow <= $minActualTemp) {
				$this->setActorState(false, false);
			}
		} elseif ($this->hvacMode->equalsValue(Types\HvacMode::AUTO)) {
			$heatingThresholdTemp = $this->device->getHeatingThresholdTemp($this->mode);
			$coolingThresholdTemp = $this->device->getCoolingThresholdTemp($this->mode);

			if (
				$heatingThresholdTemp === null
				|| $coolingThresholdTemp === null
				|| $heatingThresholdTemp >= $coolingThresholdTemp
				|| $heatingThresholdTemp > $targetTemp
				|| $coolingThresholdTemp < $targetTemp
			) {
				return Promise\reject(
					new Exceptions\InvalidState('Heating and cooling threshold temperature is wrongly configured'),
				);
			}

			if ($minActualTemp <= $heatingThresholdTemp) {
				$this->setActorState(true, false);
			} elseif ($targetTempHigh > $maxActualTemp) {
				$this->setActorState(false, false);
			} elseif ($targetTempLow > $minActualTemp) {
				$this->setActorState(false, false);
			} elseif ($maxActualTemp >= $coolingThresholdTemp) {
				$this->setActorState(false, true);
			}
		}

		$this->queue->append(
			$this->entityHelper->create(
				Entities\Messages\StoreChannelPropertyState::class,
				[
					'connector' => $this->device->getConnector()->getId(),
					'device' => $this->device->getId(),
					'channel' => $this->device->getThermostat()->getId(),
					'property' => Types\ChannelPropertyIdentifier::ACTUAL_TEMPERATURE,
					'value' => $measuredTemp !== [] ? array_sum($measuredTemp) / count($measuredTemp) : null,
				],
			),
		);

		if ($this->device->hasFloorSensors()) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreChannelPropertyState::class,
					[
						'connector' => $this->device->getConnector()->getId(),
						'device' => $this->device->getId(),
						'channel' => $this->device->getThermostat()->getId(),
						'property' => Types\ChannelPropertyIdentifier::ACTUAL_FLOOR_TEMPERATURE,
						'value' => $measuredFloorTemp !== [] ? array_sum($measuredFloorTemp) / count(
							$measuredFloorTemp,
						) : null,
					],
				),
			);
		}

		return Promise\resolve();
	}

	public function writeState(
		DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Devices\Properties\Dynamic $property,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $expectedValue,
	): Promise\PromiseInterface
	{
		if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
			if ($property->getChannel()->getIdentifier() === Types\ChannelIdentifier::THERMOSTAT) {
				if (
					$property->getIdentifier() === Types\ChannelPropertyIdentifier::PRESET_MODE
					&& is_string($expectedValue)
					&& Types\ThermostatMode::isValidValue($expectedValue)
				) {
					$this->mode = Types\ThermostatMode::get($expectedValue);
				}

				if (
					$property->getIdentifier() === Types\ChannelPropertyIdentifier::HVAC_MODE
					&& is_string($expectedValue)
					&& Types\HvacMode::isValidValue($expectedValue)
				) {
					$this->hvacMode = Types\HvacMode::get($expectedValue);
				}
			}
		}

		return Promise\resolve(true);
	}

	public function notifyState(
		DevicesEntities\Devices\Properties\Mapped|DevicesEntities\Channels\Properties\Mapped $property,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $actualValue,
	): Promise\PromiseInterface
	{
		if ($property instanceof DevicesEntities\Channels\Properties\Mapped) {
			if ($property->getChannel()->getIdentifier() === Types\ChannelIdentifier::ACTORS) {
				if (
					Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::HEATER)
					&& (is_bool($actualValue) || $actualValue === null)
				) {
					$this->heaters[$property->getId()->toString()] = $actualValue;

				} elseif (
					Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::COOLER)
					&& (is_bool($actualValue) || $actualValue === null)
				) {
					$this->coolers[$property->getId()->toString()] = $actualValue;
				}
			} elseif ($property->getChannel()->getIdentifier() === Types\ChannelIdentifier::SENSORS) {
				if (
					Utils\Strings::startsWith(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::TARGET_SENSOR,
					)
					&& (is_float($actualValue) || $actualValue === null)
				) {
					$this->actualTemperature[$property->getId()->toString()] = $actualValue;

				} elseif (
					Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::FLOOR_SENSOR)
					&& (is_float($actualValue) || $actualValue === null)
				) {
					$this->actualFloorTemperature[$property->getId()->toString()] = $actualValue;
				}
			} elseif ($property->getChannel()->getIdentifier() === Types\ChannelIdentifier::OPENINGS) {
				if (
					Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::SENSOR)
					&& (is_bool($actualValue) || $actualValue === null)
				) {
					$this->openingsState[$property->getId()->toString()] = $actualValue;
				}
			}
		}

		return Promise\resolve(true);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function setActorState(bool $heaters, bool $coolers): void
	{
		$this->setHeaterState($heaters);
		$this->setCoolerState($coolers);

		$state = Types\HvacState::INACTIVE;

		if ($heaters && !$coolers) {
			$state = Types\HvacState::HEATING;
		} elseif (!$heaters && $coolers) {
			$state = Types\HvacState::COOLING;
		} elseif (!$heaters && !$coolers) {
			$state = Types\HvacState::OFF;
		}

		$this->queue->append(
			$this->entityHelper->create(
				Entities\Messages\StoreChannelPropertyState::class,
				[
					'connector' => $this->device->getConnector()->getId(),
					'device' => $this->device->getId(),
					'channel' => $this->device->getThermostat()->getId(),
					'property' => Types\ChannelPropertyIdentifier::HVAC_STATE,
					'value' => $state,
				],
			),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function setHeaterState(bool $state): void
	{
		if ($state && $this->isFloorOverHeating()) {
			$this->setHeaterState(false);

			$this->logger->warning(
				'Floor is overheating. Turning off heaters actors',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
					'type' => 'thermostat-driver',
					'connector' => [
						'id' => $this->device->getConnector()->getId()->toString(),
					],
					'device' => [
						'id' => $this->device->getId()->toString(),
					],
				],
			);

			return;
		}

		foreach ($this->device->getActors() as $actor) {
			assert($actor instanceof DevicesEntities\Channels\Properties\Mapped);

			if (!Utils\Strings::startsWith($actor->getIdentifier(), Types\ChannelPropertyIdentifier::HEATER)) {
				continue;
			}

			if (
				array_key_exists($actor->getId()->toString(), $this->heaters)
				&& $this->heaters[$actor->getId()->toString()] === $state
			) {
				continue;
			}

			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreChannelPropertyState::class,
					[
						'connector' => $this->device->getConnector()->getId(),
						'device' => $this->device->getId(),
						'channel' => $actor->getChannel()->getId(),
						'property' => $actor->getId(),
						'value' => $state,
					],
				),
			);
		}
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	private function setCoolerState(bool $state): void
	{
		foreach ($this->device->getActors() as $actor) {
			assert($actor instanceof DevicesEntities\Channels\Properties\Mapped);

			if (!Utils\Strings::startsWith($actor->getIdentifier(), Types\ChannelPropertyIdentifier::COOLER)) {
				continue;
			}

			if (
				array_key_exists($actor->getId()->toString(), $this->coolers)
				&& $this->coolers[$actor->getId()->toString()] === $state
			) {
				continue;
			}

			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreChannelPropertyState::class,
					[
						'connector' => $this->device->getConnector()->getId(),
						'device' => $this->device->getId(),
						'channel' => $actor->getChannel()->getId(),
						'property' => $actor->getId(),
						'value' => $state,
					],
				),
			);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function isFloorOverHeating(): bool
	{
		if ($this->device->hasFloorSensors()) {
			$maxFloorActualTemp = max(
				array_filter($this->actualFloorTemperature, static fn (float|null $temp): bool => $temp !== null),
			);

			if ($maxFloorActualTemp >= $this->device->getMaximumFloorTemp()) {
				return true;
			}
		}

		return false;
	}

	private function isOpeningsClosed(): bool
	{
		foreach ($this->openingsState as $state) {
			if ($state === true) {
				return false;
			}
		}

		return true;
	}

}
