<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           15.10.23
 */

namespace FastyBird\Connector\Virtual\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette\Utils;
use function array_filter;
use function assert;
use function sprintf;

/**
 * @ORM\Entity
 */
class Thermostat extends Entities\VirtualDevice
{

	public const TYPE = 'virtual-thermostat';

	public const MINIMUM_TEMPERATURE = 7.0;

	public const MAXIMUM_TEMPERATURE = 35.0;

	public const MAXIMUM_FLOOR_TEMPERATURE = 28.0;

	public const PRECISION = 0.1;

	public const COLD_TOLERANCE = 0.3;

	public const HOT_TOLERANCE = 0.3;

	public function getType(): string
	{
		return self::TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::TYPE;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getThermostat(): Entities\Channels\Thermostat
	{
		$channels = $this->channels
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Channel $channel): bool => $channel->getIdentifier() === Types\ChannelIdentifier::THERMOSTAT
			);

		if ($channels->count() !== 1) {
			throw new Exceptions\InvalidState('Thermostat channel is not configured');
		}

		$channel = $channels->first();
		assert($channel instanceof Entities\Channels\Thermostat);

		return $channel;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getPreset(string $preset): Entities\Channels\Preset
	{
		$channels = $this->channels
			->filter(
				static fn (DevicesEntities\Channels\Channel $channel): bool => $channel->getIdentifier() === $preset
			);

		if ($channels->count() !== 1) {
			throw new Exceptions\InvalidState(sprintf('Preset channel: %s is not configured', $preset));
		}

		$channel = $channels->first();
		assert($channel instanceof Entities\Channels\Preset);

		return $channel;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getTargetTemp(Types\ThermostatMode $mode): float|null
	{
		if ($mode->equalsValue(Types\ThermostatMode::AWAY)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_AWAY)->getTargetTemp();
		}

		if ($mode->equalsValue(Types\ThermostatMode::ECO)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ECO)->getTargetTemp();
		}

		if ($mode->equalsValue(Types\ThermostatMode::HOME)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_HOME)->getTargetTemp();
		}

		if ($mode->equalsValue(Types\ThermostatMode::COMFORT)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_COMFORT)->getTargetTemp();
		}

		if ($mode->equalsValue(Types\ThermostatMode::SLEEP)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_SLEEP)->getTargetTemp();
		}

		if ($mode->equalsValue(Types\ThermostatMode::ANTI_FREEZE)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ANTI_FREEZE)->getTargetTemp();
		}

		return $this->getThermostat()->getTargetTemp();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getCoolingThresholdTemp(Types\ThermostatMode $mode): float|null
	{
		if ($mode->equalsValue(Types\ThermostatMode::AWAY)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_AWAY)->getCoolingThresholdTemp();
		}

		if ($mode->equalsValue(Types\ThermostatMode::ECO)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ECO)->getCoolingThresholdTemp();
		}

		if ($mode->equalsValue(Types\ThermostatMode::HOME)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_HOME)->getCoolingThresholdTemp();
		}

		if ($mode->equalsValue(Types\ThermostatMode::COMFORT)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_COMFORT)->getCoolingThresholdTemp();
		}

		if ($mode->equalsValue(Types\ThermostatMode::SLEEP)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_SLEEP)->getCoolingThresholdTemp();
		}

		if ($mode->equalsValue(Types\ThermostatMode::ANTI_FREEZE)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ANTI_FREEZE)->getCoolingThresholdTemp();
		}

		return $this->getThermostat()->getCoolingThresholdTemp();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getHeatingThresholdTemp(Types\ThermostatMode $mode): float|null
	{
		if ($mode->equalsValue(Types\ThermostatMode::AWAY)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_AWAY)->getHeatingThresholdTemp();
		}

		if ($mode->equalsValue(Types\ThermostatMode::ECO)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ECO)->getHeatingThresholdTemp();
		}

		if ($mode->equalsValue(Types\ThermostatMode::HOME)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_HOME)->getHeatingThresholdTemp();
		}

		if ($mode->equalsValue(Types\ThermostatMode::COMFORT)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_COMFORT)->getHeatingThresholdTemp();
		}

		if ($mode->equalsValue(Types\ThermostatMode::SLEEP)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_SLEEP)->getHeatingThresholdTemp();
		}

		if ($mode->equalsValue(Types\ThermostatMode::ANTI_FREEZE)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ANTI_FREEZE)->getHeatingThresholdTemp();
		}

		return $this->getThermostat()->getHeatingThresholdTemp();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getMaximumFloorTemp(): float
	{
		return $this->getThermostat()->getMaximumFloorTemp();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getAllowedMinimumTemp(): float
	{
		return $this->getThermostat()->getAllowedMinimumTemp();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getAllowedMaximumTemp(): float
	{
		return $this->getThermostat()->getAllowedMaximumTemp();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getMinimumCycleDuration(): float|null
	{
		return $this->getThermostat()->getMinimumCycleDuration();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getLowTargetTempTolerance(): float|null
	{
		return $this->getThermostat()->getLowTargetTempTolerance();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getHighTargetTempTolerance(): float|null
	{
		return $this->getThermostat()->getHighTargetTempTolerance();
	}

	/**
	 * @return array<int, DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped>
	 */
	public function getActors(): array
	{
		$channels = $this->channels
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Channel $channel): bool => $channel->getIdentifier() === Types\ChannelIdentifier::ACTORS
			);

		if ($channels->count() !== 1) {
			return [];
		}

		$channel = $channels->first();
		assert($channel instanceof Entities\Channels\Actors);

		return array_filter(
			$channel->getActors(),
			static fn (DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped $property): bool =>
				Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::HEATER)
				|| Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::COOLER),
		);
	}

	public function hasHeaters(): bool
	{
		return array_filter(
			$this->getActors(),
			static fn ($sensor): bool => Utils\Strings::startsWith(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::HEATER,
			)
		) !== [];
	}

	public function hasCoolers(): bool
	{
		return array_filter(
			$this->getActors(),
			static fn ($sensor): bool => Utils\Strings::startsWith(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::COOLER,
			)
		) !== [];
	}

	/**
	 * @return array<int, DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped>
	 */
	public function getSensors(): array
	{
		$channels = $this->channels
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Channel $channel): bool => $channel->getIdentifier() === Types\ChannelIdentifier::SENSORS
			);

		if ($channels->count() !== 1) {
			return [];
		}

		$channel = $channels->first();
		assert($channel instanceof Entities\Channels\Sensors);

		return array_filter(
			$channel->getSensors(),
			static fn (DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped $property): bool =>
				Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::TARGET_SENSOR)
				|| Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::FLOOR_SENSOR),
		);
	}

	public function hasFloorSensors(): bool
	{
		return array_filter(
			$this->getSensors(),
			static fn ($sensor): bool => Utils\Strings::startsWith(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::FLOOR_SENSOR,
			)
		) !== [];
	}

	/**
	 * @return array<int, DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped>
	 */
	public function getOpenings(): array
	{
		$channels = $this->channels
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Channel $channel): bool => $channel->getIdentifier() === Types\ChannelIdentifier::OPENINGS
			);

		if ($channels->count() !== 1) {
			return [];
		}

		$channel = $channels->first();
		assert($channel instanceof Entities\Channels\Sensors);

		return array_filter(
			$channel->getSensors(),
			static fn (DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped $property): bool =>
				Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::SENSOR),
		);
	}

}
