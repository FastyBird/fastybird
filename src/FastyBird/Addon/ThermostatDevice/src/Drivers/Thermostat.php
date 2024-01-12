<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Drivers
 * @since          1.0.0
 *
 * @date           16.10.23
 */

namespace FastyBird\Addon\ThermostatDevice\Drivers;

use DateTimeInterface;
use FastyBird\Addon\ThermostatDevice;
use FastyBird\Addon\ThermostatDevice\Exceptions;
use FastyBird\Addon\ThermostatDevice\Helpers;
use FastyBird\Addon\ThermostatDevice\Types;
use FastyBird\Connector\Virtual\Drivers as VirtualDrivers;
use FastyBird\Connector\Virtual\Entities as VirtualEntities;
use FastyBird\Connector\Virtual\Exceptions as VirtualExceptions;
use FastyBird\Connector\Virtual\Helpers as VirtualHelpers;
use FastyBird\Connector\Virtual\Queue as VirtualQueue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette\Utils;
use React\Promise;
use function array_filter;
use function array_key_exists;
use function array_sum;
use function assert;
use function count;
use function floatval;
use function in_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function max;
use function min;
use function preg_match;

/**
 * Thermostat service
 *
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Drivers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Thermostat implements VirtualDrivers\Driver
{

	/** @var array<string, bool|null> */
	private array $heaters = [];

	/** @var array<string, bool|null> */
	private array $coolers = [];

	/** @var array<string, float|null> */
	private array $targetTemperature = [];

	/** @var array<string, float|null> */
	private array $actualTemperature = [];

	/** @var array<string, float|null> */
	private array $actualFloorTemperature = [];

	/** @var array<string, bool|null> */
	private array $openingsState = [];

	private Types\Preset|null $presetMode = null;

	private Types\HvacMode|null $hvacMode = null;

	private bool $connected = false;

	private DateTimeInterface|null $connectedAt = null;

	public function __construct(
		private readonly MetadataDocuments\DevicesModule\Device $device,
		private readonly VirtualHelpers\Entity $entityHelper,
		private readonly Helpers\Device $deviceHelper,
		private readonly VirtualQueue\Queue $queue,
		private readonly ThermostatDevice\Logger $logger,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStatesManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function connect(): Promise\PromiseInterface
	{
		if (
			!$this->deviceHelper->hasSensors($this->device)
			|| (
				!$this->deviceHelper->hasHeaters($this->device)
				&& !$this->deviceHelper->hasCoolers($this->device)
			)
		) {
			return Promise\reject(
				new Exceptions\InvalidState('Thermostat has not configured all required actors or sensors'),
			);
		}

		foreach ($this->deviceHelper->getActors($this->device) as $actor) {
			$state = $this->channelPropertiesStatesManager->readValue($actor);

			$actualValue = $state?->getActualValue();

			if (Utils\Strings::startsWith($actor->getIdentifier(), Types\ChannelPropertyIdentifier::HEATER_ACTOR)) {
				$this->heaters[$actor->getId()->toString()] = is_bool($actualValue)
					? $actualValue
					: null;
			} elseif (Utils\Strings::startsWith(
				$actor->getIdentifier(),
				Types\ChannelPropertyIdentifier::COOLER_ACTOR,
			)) {
				$this->coolers[$actor->getId()->toString()] = is_bool($actualValue)
					? $actualValue
					: null;
			}
		}

		foreach ($this->deviceHelper->getSensors($this->device) as $sensor) {
			$state = $this->channelPropertiesStatesManager->readValue($sensor);

			$actualValue = $state?->getActualValue();

			if (Utils\Strings::startsWith($sensor->getIdentifier(), Types\ChannelPropertyIdentifier::FLOOR_SENSOR)) {
				$this->actualFloorTemperature[$sensor->getId()->toString()] = is_numeric($actualValue)
					? floatval($actualValue)
					: null;
			} elseif (Utils\Strings::startsWith(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::TARGET_SENSOR,
			)) {
				$this->actualTemperature[$sensor->getId()->toString()] = is_numeric($actualValue)
					? floatval($actualValue)
					: null;
			}
		}

		foreach ($this->deviceHelper->getOpenings($this->device) as $opening) {
			$state = $this->channelPropertiesStatesManager->readValue($opening);

			$actualValue = $state?->getActualValue();

			if (Utils\Strings::startsWith($opening->getIdentifier(), Types\ChannelPropertyIdentifier::OPENING_SENSOR)) {
				$this->openingsState[$opening->getId()->toString()] = is_bool($actualValue) ? $actualValue : null;
			}
		}

		foreach ($this->deviceHelper->getPresetModes($this->device) as $mode) {
			$property = $this->deviceHelper->getTargetTemp($this->device, Types\Preset::get($mode));

			if ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
				$state = $this->channelPropertiesStatesManager->readValue($property);

				if (is_numeric($state?->getActualValue())) {
					$this->targetTemperature[$mode] = floatval($state->getActualValue());
				}
			}
		}

		if ($this->deviceHelper->getHvacMode($this->device) !== null) {
			$state = $this->channelPropertiesStatesManager->readValue(
				$this->deviceHelper->getHvacMode($this->device),
			);

			if ($state !== null && Types\HvacMode::isValidValue($state->getActualValue())) {
				$this->hvacMode = Types\HvacMode::get($state->getActualValue());
			}
		}

		if ($this->deviceHelper->getPresetMode($this->device) !== null) {
			$state = $this->channelPropertiesStatesManager->readValue(
				$this->deviceHelper->getPresetMode($this->device),
			);

			if ($state !== null && Types\Preset::isValidValue($state->getActualValue())) {
				$this->presetMode = Types\Preset::get($state->getActualValue());
			}
		}

		$this->connected = true;
		$this->connectedAt = $this->dateTimeFactory->getNow();

		return Promise\resolve(true);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
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
		return $this->connected && $this->connectedAt !== null;
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
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 */
	public function process(): Promise\PromiseInterface
	{
		if ($this->hvacMode === null || $this->presetMode === null) {
			$this->setActorState(false, false);

			$this->connected = false;

			return Promise\reject(new Exceptions\InvalidState('Thermostat mode is not configured'));
		}

		if (
			!array_key_exists($this->presetMode->getValue(), $this->targetTemperature)
			|| $this->targetTemperature[$this->presetMode->getValue()] === null
		) {
			$this->setActorState(false, false);

			$this->connected = false;

			return Promise\reject(new Exceptions\InvalidState('Target temperature is not configured'));
		}

		$targetTemp = $this->targetTemperature[$this->presetMode->getValue()];

		$targetTempLow = $targetTemp - ($this->deviceHelper->getLowTargetTempTolerance($this->device) ?? 0);
		$targetTempHigh = $targetTemp + ($this->deviceHelper->getHighTargetTempTolerance($this->device) ?? 0);

		if ($targetTempLow > $targetTempHigh) {
			$this->setActorState(false, false);

			$this->connected = false;

			return Promise\reject(new Exceptions\InvalidState('Target temperature boundaries are wrongly configured'));
		}

		$measuredTemp = array_filter(
			$this->actualTemperature,
			static fn (float|null $temp): bool => $temp !== null,
		);
		$measuredFloorTemp = array_filter(
			$this->actualFloorTemperature,
			static fn (float|null $temp): bool => $temp !== null,
		);

		$minActualTemp = min($measuredTemp);
		$maxActualTemp = max($measuredTemp);

		$this->queue->append(
			$this->entityHelper->create(
				VirtualEntities\Messages\StoreChannelPropertyState::class,
				[
					'connector' => $this->device->getConnector(),
					'device' => $this->device->getId(),
					'channel' => $this->deviceHelper->getConfiguration($this->device)->getId(),
					'property' => Types\ChannelPropertyIdentifier::ACTUAL_TEMPERATURE,
					'value' => $measuredTemp !== []
						? array_sum($measuredTemp) / count($measuredTemp)
						: null,
				],
			),
		);

		if ($this->deviceHelper->hasFloorSensors($this->device)) {
			$this->queue->append(
				$this->entityHelper->create(
					VirtualEntities\Messages\StoreChannelPropertyState::class,
					[
						'connector' => $this->device->getConnector(),
						'device' => $this->device->getId(),
						'channel' => $this->deviceHelper->getConfiguration($this->device)->getId(),
						'property' => Types\ChannelPropertyIdentifier::ACTUAL_FLOOR_TEMPERATURE,
						'value' => $measuredFloorTemp !== []
							? array_sum($measuredFloorTemp) / count($measuredFloorTemp)
							: null,
					],
				),
			);
		}

		if (!$this->isOpeningsClosed()) {
			$this->setActorState(false, false);

			return Promise\resolve(true);
		}

		if ($this->hvacMode->equalsValue(Types\HvacMode::OFF)) {
			$this->setActorState(false, false);

			return Promise\resolve(true);
		}

		if ($this->isFloorOverHeating()) {
			$this->setActorState(false, $this->isCooling());

			return Promise\resolve(true);
		}

		if ($this->hvacMode->equalsValue(Types\HvacMode::HEAT)) {
			if (!$this->deviceHelper->hasHeaters($this->device)) {
				$this->setActorState(false, false);

				$this->connected = false;

				return Promise\reject(new Exceptions\InvalidState('Thermostat has not configured any heater actor'));
			}

			if ($maxActualTemp >= $targetTempHigh) {
				$this->setActorState(false, false);
			} elseif ($minActualTemp <= $targetTempLow) {
				$this->setActorState(true, false);
			}
		} elseif ($this->hvacMode->equalsValue(Types\HvacMode::COOL)) {
			if (!$this->deviceHelper->hasCoolers($this->device)) {
				$this->setActorState(false, false);

				$this->connected = false;

				return Promise\reject(new Exceptions\InvalidState('Thermostat has not configured any cooler actor'));
			}

			if ($maxActualTemp >= $targetTempHigh) {
				$this->setActorState(false, true);
			} elseif ($minActualTemp <= $targetTempLow) {
				$this->setActorState(false, false);
			}
		} elseif ($this->hvacMode->equalsValue(Types\HvacMode::AUTO)) {
			$heatingThresholdTemp = $this->deviceHelper->getHeatingThresholdTemp($this->device, $this->presetMode);
			$coolingThresholdTemp = $this->deviceHelper->getCoolingThresholdTemp($this->device, $this->presetMode);

			if (
				$heatingThresholdTemp === null
				|| $coolingThresholdTemp === null
				|| $heatingThresholdTemp >= $coolingThresholdTemp
				|| $heatingThresholdTemp > $targetTemp
				|| $coolingThresholdTemp < $targetTemp
			) {
				$this->connected = false;

				return Promise\reject(
					new Exceptions\InvalidState('Heating and cooling threshold temperatures are wrongly configured'),
				);
			}

			if ($minActualTemp <= $heatingThresholdTemp) {
				$this->setActorState(true, false);
			} elseif ($maxActualTemp >= $coolingThresholdTemp) {
				$this->setActorState(false, true);
			} elseif (
				$this->isHeating()
				&& !$this->isCooling()
				&& $maxActualTemp >= $targetTempHigh
			) {
				$this->setActorState(false, false);
			} elseif (
				!$this->isHeating()
				&& $this->isCooling()
				&& $minActualTemp <= $targetTempLow
			) {
				$this->setActorState(false, false);
			} elseif ($this->isHeating() && $this->isCooling()) {
				$this->setActorState(false, false);
			}
		}

		return Promise\resolve(true);
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 */
	public function writeState(
		MetadataDocuments\DevicesModule\DeviceDynamicProperty|MetadataDocuments\DevicesModule\ChannelDynamicProperty $property,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $expectedValue,
	): Promise\PromiseInterface
	{
		if ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
			$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
			$findChannelQuery->byId($property->getChannel());

			$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

			if ($channel === null) {
				return Promise\reject(
					new Exceptions\InvalidArgument('Channel for provided property could not be found'),
				);
			}

			if ($channel->getIdentifier() === Types\ChannelIdentifier::CONFIGURATION) {
				if ($property->getIdentifier() === Types\ChannelPropertyIdentifier::PRESET_MODE) {
					if (
						is_string($expectedValue)
						&& Types\Preset::isValidValue($expectedValue)
					) {
						$this->presetMode = Types\Preset::get($expectedValue);

						$this->queue->append(
							$this->entityHelper->create(
								VirtualEntities\Messages\StoreChannelPropertyState::class,
								[
									'connector' => $this->device->getConnector(),
									'device' => $this->device->getId(),
									'channel' => $this->deviceHelper->getConfiguration($this->device)->getId(),
									'property' => $property->getId(),
									'value' => $expectedValue,
								],
							),
						);

						return Promise\resolve(true);
					} else {
						return Promise\reject(new Exceptions\InvalidArgument('Provided value is not valid'));
					}
				} elseif ($property->getIdentifier() === Types\ChannelPropertyIdentifier::HVAC_MODE) {
					if (
						is_string($expectedValue)
						&& Types\HvacMode::isValidValue($expectedValue)
					) {
						$this->hvacMode = Types\HvacMode::get($expectedValue);

						$this->queue->append(
							$this->entityHelper->create(
								VirtualEntities\Messages\StoreChannelPropertyState::class,
								[
									'connector' => $this->device->getConnector(),
									'device' => $this->device->getId(),
									'channel' => $this->deviceHelper->getConfiguration($this->device)->getId(),
									'property' => $property->getId(),
									'value' => $expectedValue,
								],
							),
						);

						return Promise\resolve(true);
					} else {
						return Promise\reject(new Exceptions\InvalidArgument('Provided value is not valid'));
					}
				} elseif ($property->getIdentifier() === Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE) {
					if (is_numeric($expectedValue)) {
						$this->targetTemperature[Types\Preset::MANUAL] = floatval($expectedValue);

						$this->queue->append(
							$this->entityHelper->create(
								VirtualEntities\Messages\StoreChannelPropertyState::class,
								[
									'connector' => $this->device->getConnector(),
									'device' => $this->device->getId(),
									'channel' => $this->deviceHelper->getConfiguration($this->device)->getId(),
									'property' => $property->getId(),
									'value' => $expectedValue,
								],
							),
						);

						return Promise\resolve(true);
					} else {
						return Promise\reject(new Exceptions\InvalidArgument('Provided value is not valid'));
					}
				}
			} elseif (
				preg_match(
					ThermostatDevice\Constants::PRESET_CHANNEL_PATTERN,
					$channel->getIdentifier(),
					$matches,
				) === 1
				&& in_array('preset', $matches, true)
			) {
				if (
					Types\Preset::isValidValue($matches['preset'])
					&& is_numeric($expectedValue)
				) {
					$this->targetTemperature[$matches['preset']] = floatval($expectedValue);

					$this->queue->append(
						$this->entityHelper->create(
							VirtualEntities\Messages\StoreChannelPropertyState::class,
							[
								'connector' => $this->device->getConnector(),
								'device' => $this->device->getId(),
								'channel' => $channel->getId(),
								'property' => $property->getId(),
								'value' => $expectedValue,
							],
						),
					);

					return Promise\resolve(true);
				} else {
					return Promise\reject(new Exceptions\InvalidArgument('Provided value is not valid'));
				}
			}
		}

		return Promise\reject(new Exceptions\InvalidArgument('Provided property is unsupported'));
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	public function notifyState(
		MetadataDocuments\DevicesModule\DeviceMappedProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty $property,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $actualValue,
	): Promise\PromiseInterface
	{
		if ($property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty) {
			$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
			$findChannelQuery->byId($property->getChannel());

			$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

			if ($channel === null) {
				return Promise\reject(
					new Exceptions\InvalidArgument('Channel for provided property could not be found'),
				);
			}

			if ($channel->getIdentifier() === Types\ChannelIdentifier::ACTORS) {
				if (
					Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::HEATER_ACTOR)
					&& (is_bool($actualValue) || $actualValue === null)
				) {
					$this->heaters[$property->getId()->toString()] = $actualValue;

					return Promise\resolve(true);
				} elseif (
					Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::COOLER_ACTOR)
					&& (is_bool($actualValue) || $actualValue === null)
				) {
					$this->coolers[$property->getId()->toString()] = $actualValue;

					return Promise\resolve(true);
				}
			} elseif ($channel->getIdentifier() === Types\ChannelIdentifier::SENSORS) {
				if (
					Utils\Strings::startsWith(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::TARGET_SENSOR,
					)
					&& (is_numeric($actualValue) || $actualValue === null)
				) {
					$this->actualTemperature[$property->getId()->toString()] = floatval($actualValue);

					return Promise\resolve(true);
				} elseif (
					Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::FLOOR_SENSOR)
					&& (is_numeric($actualValue) || $actualValue === null)
				) {
					$this->actualFloorTemperature[$property->getId()->toString()] = floatval($actualValue);

					return Promise\resolve(true);
				}
			} elseif ($channel->getIdentifier() === Types\ChannelIdentifier::OPENINGS) {
				if (
					Utils\Strings::startsWith(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::OPENING_SENSOR,
					)
					&& (is_bool($actualValue) || $actualValue === null)
				) {
					$this->openingsState[$property->getId()->toString()] = $actualValue;

					return Promise\resolve(true);
				}
			}
		}

		return Promise\reject(new Exceptions\InvalidArgument('Provided property is unsupported'));
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 */
	private function setActorState(bool $heaters, bool $coolers): void
	{
		if (!$this->deviceHelper->hasHeaters($this->device)) {
			$heaters = false;
		}

		if (!$this->deviceHelper->hasCoolers($this->device)) {
			$coolers = false;
		}

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
				VirtualEntities\Messages\StoreChannelPropertyState::class,
				[
					'connector' => $this->device->getConnector(),
					'device' => $this->device->getId(),
					'channel' => $this->deviceHelper->getConfiguration($this->device)->getId(),
					'property' => Types\ChannelPropertyIdentifier::HVAC_STATE,
					'value' => $state,
				],
			),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
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
						'id' => $this->device->getConnector()->toString(),
					],
					'device' => [
						'id' => $this->device->getId()->toString(),
					],
				],
			);

			return;
		}

		foreach ($this->deviceHelper->getActors($this->device) as $actor) {
			assert($actor instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty);

			if (!Utils\Strings::startsWith($actor->getIdentifier(), Types\ChannelPropertyIdentifier::HEATER_ACTOR)) {
				continue;
			}

			$this->queue->append(
				$this->entityHelper->create(
					VirtualEntities\Messages\StoreChannelPropertyState::class,
					[
						'connector' => $this->device->getConnector(),
						'device' => $this->device->getId(),
						'channel' => $actor->getChannel(),
						'property' => $actor->getId(),
						'value' => $state,
					],
				),
			);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws VirtualExceptions\Runtime
	 */
	private function setCoolerState(bool $state): void
	{
		foreach ($this->deviceHelper->getActors($this->device) as $actor) {
			assert($actor instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty);

			if (!Utils\Strings::startsWith($actor->getIdentifier(), Types\ChannelPropertyIdentifier::COOLER_ACTOR)) {
				continue;
			}

			$this->queue->append(
				$this->entityHelper->create(
					VirtualEntities\Messages\StoreChannelPropertyState::class,
					[
						'connector' => $this->device->getConnector(),
						'device' => $this->device->getId(),
						'channel' => $actor->getChannel(),
						'property' => $actor->getId(),
						'value' => $state,
					],
				),
			);
		}
	}

	private function isHeating(): bool
	{
		return in_array(true, $this->heaters, true);
	}

	private function isCooling(): bool
	{
		return in_array(true, $this->coolers, true);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function isFloorOverHeating(): bool
	{
		if ($this->deviceHelper->hasFloorSensors($this->device)) {
			$maxFloorActualTemp = max(
				array_filter($this->actualFloorTemperature, static fn (float|null $temp): bool => $temp !== null),
			);

			if ($maxFloorActualTemp >= $this->deviceHelper->getMaximumFloorTemp($this->device)) {
				return true;
			}
		}

		return false;
	}

	private function isOpeningsClosed(): bool
	{
		return !in_array(true, $this->openingsState, true);
	}

}
