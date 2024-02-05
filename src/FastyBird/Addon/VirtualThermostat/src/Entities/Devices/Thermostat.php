<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           05.02.24
 */

namespace FastyBird\Addon\VirtualThermostat\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Addon\VirtualThermostat\Exceptions;
use FastyBird\Addon\VirtualThermostat\Types;
use FastyBird\Connector\Virtual\Entities as VirtualEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Nette\Utils;
use function array_filter;
use function array_map;
use function assert;
use function sprintf;

/**
 * @ORM\Entity
 */
class Thermostat extends VirtualEntities\Devices\Device
{

	public const TYPE = 'virtual-thermostat-addon';

	public const MINIMUM_TEMPERATURE = 7.0;

	public const MAXIMUM_TEMPERATURE = 35.0;

	public const MAXIMUM_FLOOR_TEMPERATURE = 28.0;

	public const PRECISION = 0.1;

	public const COLD_TOLERANCE = 0.3;

	public const HOT_TOLERANCE = 0.3;

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\Sources\Addon
	{
		return MetadataTypes\Sources\Addon::get(MetadataTypes\Sources\Addon::VIRTUAL_THERMOSTAT);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getConfiguration(): Entities\Channels\Configuration
	{
		$channels = $this->channels
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Channel $channel): bool => $channel->getIdentifier() === Types\ChannelIdentifier::CONFIGURATION
			);

		if ($channels->count() !== 1) {
			throw new Exceptions\InvalidState('Configuration channel is not configured');
		}

		$channel = $channels->first();
		assert($channel instanceof Entities\Channels\Configuration);

		return $channel;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getState(): Entities\Channels\State
	{
		$channels = $this->channels
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Channel $channel): bool => $channel->getIdentifier() === Types\ChannelIdentifier::STATE
			);

		if ($channels->count() !== 1) {
			throw new Exceptions\InvalidState('State channel is not configured');
		}

		$channel = $channels->first();
		assert($channel instanceof Entities\Channels\State);

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
	 * @throws Exceptions\InvalidState
	 */
	public function getHvacMode(): DevicesEntities\Channels\Properties\Dynamic|null
	{
		return $this->getState()->getHvacMode();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getPresetMode(): DevicesEntities\Channels\Properties\Dynamic|null
	{
		return $this->getState()->getPresetMode();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getTargetTemp(Types\Preset $preset): DevicesEntities\Channels\Properties\Dynamic|null
	{
		if ($preset->equalsValue(Types\Preset::AUTO)) {
			return null;
		}

		if ($preset->equalsValue(Types\Preset::AWAY)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_AWAY)->getTargetTemp();
		}

		if ($preset->equalsValue(Types\Preset::ECO)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ECO)->getTargetTemp();
		}

		if ($preset->equalsValue(Types\Preset::HOME)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_HOME)->getTargetTemp();
		}

		if ($preset->equalsValue(Types\Preset::COMFORT)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_COMFORT)->getTargetTemp();
		}

		if ($preset->equalsValue(Types\Preset::SLEEP)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_SLEEP)->getTargetTemp();
		}

		if ($preset->equalsValue(Types\Preset::ANTI_FREEZE)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ANTI_FREEZE)->getTargetTemp();
		}

		if ($preset->equalsValue(Types\Preset::MANUAL)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_MANUAL)->getTargetTemp();
		}

		throw new Exceptions\InvalidState('Provided preset is not configured');
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getCoolingThresholdTemp(Types\Preset $preset): float|null
	{
		if ($preset->equalsValue(Types\Preset::AUTO)) {
			return null;
		}

		if ($preset->equalsValue(Types\Preset::AWAY)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_AWAY)->getCoolingThresholdTemp();
		}

		if ($preset->equalsValue(Types\Preset::ECO)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ECO)->getCoolingThresholdTemp();
		}

		if ($preset->equalsValue(Types\Preset::HOME)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_HOME)->getCoolingThresholdTemp();
		}

		if ($preset->equalsValue(Types\Preset::COMFORT)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_COMFORT)->getCoolingThresholdTemp();
		}

		if ($preset->equalsValue(Types\Preset::SLEEP)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_SLEEP)->getCoolingThresholdTemp();
		}

		if ($preset->equalsValue(Types\Preset::ANTI_FREEZE)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ANTI_FREEZE)->getCoolingThresholdTemp();
		}

		if ($preset->equalsValue(Types\Preset::MANUAL)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_MANUAL)->getCoolingThresholdTemp();
		}

		throw new Exceptions\InvalidState('Provided preset is not configured');
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getHeatingThresholdTemp(Types\Preset $preset): float|null
	{
		if ($preset->equalsValue(Types\Preset::AUTO)) {
			return null;
		}

		if ($preset->equalsValue(Types\Preset::AWAY)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_AWAY)->getHeatingThresholdTemp();
		}

		if ($preset->equalsValue(Types\Preset::ECO)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ECO)->getHeatingThresholdTemp();
		}

		if ($preset->equalsValue(Types\Preset::HOME)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_HOME)->getHeatingThresholdTemp();
		}

		if ($preset->equalsValue(Types\Preset::COMFORT)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_COMFORT)->getHeatingThresholdTemp();
		}

		if ($preset->equalsValue(Types\Preset::SLEEP)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_SLEEP)->getHeatingThresholdTemp();
		}

		if ($preset->equalsValue(Types\Preset::ANTI_FREEZE)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_ANTI_FREEZE)->getHeatingThresholdTemp();
		}

		if ($preset->equalsValue(Types\Preset::MANUAL)) {
			return $this->getPreset(Types\ChannelIdentifier::PRESET_MANUAL)->getHeatingThresholdTemp();
		}

		throw new Exceptions\InvalidState('Provided preset is not configured');
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getMaximumFloorTemp(): float
	{
		return $this->getConfiguration()->getMaximumFloorTemp();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getMinimumCycleDuration(): float|null
	{
		return $this->getConfiguration()->getMinimumCycleDuration();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getLowTargetTempTolerance(): float|null
	{
		return $this->getConfiguration()->getLowTargetTempTolerance();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getHighTargetTempTolerance(): float|null
	{
		return $this->getConfiguration()->getHighTargetTempTolerance();
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
				Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::HEATER_ACTOR)
				|| Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::COOLER_ACTOR),
		);
	}

	public function hasHeaters(): bool
	{
		return array_filter(
			$this->getActors(),
			static fn ($actor): bool => Utils\Strings::startsWith(
				$actor->getIdentifier(),
				Types\ChannelPropertyIdentifier::HEATER_ACTOR,
			)
		) !== [];
	}

	public function hasCoolers(): bool
	{
		return array_filter(
			$this->getActors(),
			static fn ($actor): bool => Utils\Strings::startsWith(
				$actor->getIdentifier(),
				Types\ChannelPropertyIdentifier::COOLER_ACTOR,
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
				Utils\Strings::startsWith(
					$property->getIdentifier(),
					Types\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR,
				)
				|| Utils\Strings::startsWith(
					$property->getIdentifier(),
					Types\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR,
				)
				|| Utils\Strings::startsWith(
					$property->getIdentifier(),
					Types\ChannelPropertyIdentifier::OPENING_SENSOR,
				)
				|| Utils\Strings::startsWith(
					$property->getIdentifier(),
					Types\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR,
				),
		);
	}

	public function hasRoomTemperatureSensors(): bool
	{
		return array_filter(
			$this->getSensors(),
			static fn ($sensor): bool => Utils\Strings::startsWith(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::ROOM_TEMPERATURE_SENSOR,
			)
		) !== [];
	}

	public function hasFloorTemperatureSensors(): bool
	{
		return array_filter(
			$this->getSensors(),
			static fn ($sensor): bool => Utils\Strings::startsWith(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::FLOOR_TEMPERATURE_SENSOR,
			)
		) !== [];
	}

	public function hasOpeningsSensors(): bool
	{
		return array_filter(
			$this->getSensors(),
			static fn ($sensor): bool => Utils\Strings::startsWith(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::OPENING_SENSOR,
			)
		) !== [];
	}

	public function hasRoomHumiditySensors(): bool
	{
		return array_filter(
			$this->getSensors(),
			static fn ($sensor): bool => Utils\Strings::startsWith(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::ROOM_HUMIDITY_SENSOR,
			)
		) !== [];
	}

	/**
	 * @return array<string>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 */
	public function getHvacModes(): array
	{
		$channel = $this->getState();

		$format = $channel->getHvacMode()?->getFormat();

		if (!$format instanceof MetadataValueObjects\StringEnumFormat) {
			return [];
		}

		return array_map(
			static fn (string $item): string => Types\HvacMode::get($item)->getValue(),
			$format->toArray(),
		);
	}

	/**
	 * @return array<string>
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 */
	public function getPresetModes(): array
	{
		$channel = $this->getState();

		$format = $channel->getPresetMode()?->getFormat();

		if (!$format instanceof MetadataValueObjects\StringEnumFormat) {
			return [];
		}

		return array_map(
			static fn (string $item): string => Types\Preset::get($item)->getValue(),
			$format->toArray(),
		);
	}

}
