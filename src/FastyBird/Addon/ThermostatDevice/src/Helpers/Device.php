<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           21.11.23
 */

namespace FastyBird\Addon\ThermostatDevice\Helpers;

use FastyBird\Addon\ThermostatDevice\Entities;
use FastyBird\Addon\ThermostatDevice\Exceptions;
use FastyBird\Addon\ThermostatDevice\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette\Utils;
use function array_filter;
use function array_map;
use function assert;
use function floatval;
use function is_numeric;
use function sprintf;

/**
 * Thermostat helper
 *
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device
{

	public function __construct(
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	public function getConfiguration(
		MetadataDocuments\DevicesModule\Device $device,
	): MetadataDocuments\DevicesModule\Channel
	{
		$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(Types\ChannelIdentifier::CONFIGURATION);
		$findChannelQuery->byType(Entities\Channels\Configuration::TYPE);

		$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			throw new Exceptions\InvalidState('Configuration channel is not configured');
		}

		return $channel;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	public function getPreset(
		MetadataDocuments\DevicesModule\Device $device,
		string $preset,
	): MetadataDocuments\DevicesModule\Channel
	{
		$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier($preset);
		$findChannelQuery->byType(Entities\Channels\Preset::TYPE);

		$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			throw new Exceptions\InvalidState(sprintf('Preset channel: %s is not configured', $preset));
		}

		return $channel;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	public function getHvacMode(
		MetadataDocuments\DevicesModule\Device $device,
	): MetadataDocuments\DevicesModule\ChannelDynamicProperty|null
	{
		$channel = $this->getConfiguration($device);

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::HVAC_MODE);

		return $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	public function getPresetMode(
		MetadataDocuments\DevicesModule\Device $device,
	): MetadataDocuments\DevicesModule\ChannelDynamicProperty|null
	{
		$channel = $this->getConfiguration($device);

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::PRESET_MODE);

		return $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	public function getTargetTemp(
		MetadataDocuments\DevicesModule\Device $device,
		Types\Preset $preset,
	): MetadataDocuments\DevicesModule\ChannelDynamicProperty|null
	{
		if ($preset->equalsValue(Types\Preset::AUTO)) {
			return null;
		}

		if ($preset->equalsValue(Types\Preset::AWAY)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_AWAY);
		} elseif ($preset->equalsValue(Types\Preset::ECO)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_ECO);
		} elseif ($preset->equalsValue(Types\Preset::HOME)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_HOME);
		} elseif ($preset->equalsValue(Types\Preset::COMFORT)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_COMFORT);
		} elseif ($preset->equalsValue(Types\Preset::SLEEP)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_SLEEP);
		} elseif ($preset->equalsValue(Types\Preset::ANTI_FREEZE)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_ANTI_FREEZE);
		} elseif ($preset->equalsValue(Types\Preset::MANUAL)) {
			$channel = $this->getConfiguration($device);
		} else {
			throw new Exceptions\InvalidState('Provided preset is not configured');
		}

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE);

		return $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getCoolingThresholdTemp(
		MetadataDocuments\DevicesModule\Device $device,
		Types\Preset $preset,
	): float|null
	{
		if ($preset->equalsValue(Types\Preset::AWAY)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_AWAY);
		} elseif ($preset->equalsValue(Types\Preset::ECO)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_ECO);
		} elseif ($preset->equalsValue(Types\Preset::HOME)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_HOME);
		} elseif ($preset->equalsValue(Types\Preset::COMFORT)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_COMFORT);
		} elseif ($preset->equalsValue(Types\Preset::SLEEP)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_SLEEP);
		} elseif ($preset->equalsValue(Types\Preset::ANTI_FREEZE)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_ANTI_FREEZE);
		} elseif ($preset->equalsValue(Types\Preset::MANUAL)) {
			$channel = $this->getConfiguration($device);
		} else {
			throw new Exceptions\InvalidState('Provided preset is not configured');
		}

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelVariableProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE);

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ChannelVariableProperty::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getHeatingThresholdTemp(
		MetadataDocuments\DevicesModule\Device $device,
		Types\Preset $preset,
	): float|null
	{
		if ($preset->equalsValue(Types\Preset::AWAY)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_AWAY);
		} elseif ($preset->equalsValue(Types\Preset::ECO)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_ECO);
		} elseif ($preset->equalsValue(Types\Preset::HOME)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_HOME);
		} elseif ($preset->equalsValue(Types\Preset::COMFORT)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_COMFORT);
		} elseif ($preset->equalsValue(Types\Preset::SLEEP)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_SLEEP);
		} elseif ($preset->equalsValue(Types\Preset::ANTI_FREEZE)) {
			$channel = $this->getPreset($device, Types\ChannelIdentifier::PRESET_ANTI_FREEZE);
		} elseif ($preset->equalsValue(Types\Preset::MANUAL)) {
			$channel = $this->getConfiguration($device);
		} else {
			throw new Exceptions\InvalidState('Provided preset is not configured');
		}

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelVariableProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE);

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ChannelVariableProperty::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getMaximumFloorTemp(MetadataDocuments\DevicesModule\Device $device): float
	{
		$channel = $this->getConfiguration($device);

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelVariableProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::MAXIMUM_FLOOR_TEMPERATURE);

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ChannelVariableProperty::class,
		);

		if ($property?->getValue() === null) {
			return Entities\ThermostatDevice::MAXIMUM_FLOOR_TEMPERATURE;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getMinimumCycleDuration(MetadataDocuments\DevicesModule\Device $device): float|null
	{
		$channel = $this->getConfiguration($device);

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelVariableProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::MINIMUM_CYCLE_DURATION);

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ChannelVariableProperty::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

	/**
	 * Minimum temperature value to be cooler actor turned on (hysteresis low value)
	 * For example, if the target temperature is 25 and the tolerance is 0.5 the heater will start when the sensor equals or goes below 24.5
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getLowTargetTempTolerance(MetadataDocuments\DevicesModule\Device $device): float|null
	{
		$channel = $this->getConfiguration($device);

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelVariableProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::LOW_TARGET_TEMPERATURE_TOLERANCE);

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ChannelVariableProperty::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

	/**
	 * Maximum temperature value to be cooler actor turned on (hysteresis high value)
	 * For example, if the target temperature is 25 and the tolerance is 0.5 the heater will stop when the sensor equals or goes above 25.5
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getHighTargetTempTolerance(MetadataDocuments\DevicesModule\Device $device): float|null
	{
		$channel = $this->getConfiguration($device);

		$findPropertyQuery = new DevicesQueries\Configuration\FindChannelVariableProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::HIGH_TARGET_TEMPERATURE_TOLERANCE);

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			MetadataDocuments\DevicesModule\ChannelVariableProperty::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

	/**
	 * @return array<int, MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty>
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	public function getActors(MetadataDocuments\DevicesModule\Device $device): array
	{
		$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(Types\ChannelIdentifier::ACTORS);
		$findChannelQuery->byType(Entities\Channels\Actors::TYPE);

		$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			return [];
		}

		$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		$properties = $this->channelsPropertiesConfigurationRepository->findAllBy($findChannelPropertiesQuery);

		return array_filter(
			$properties,
			static fn (MetadataDocuments\DevicesModule\ChannelProperty $property): bool =>
				(
					$property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
					|| $property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty
				) && (
					Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::HEATER_ACTOR)
					|| Utils\Strings::startsWith(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::COOLER_ACTOR,
					)
				),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function hasHeaters(MetadataDocuments\DevicesModule\Device $device): bool
	{
		return array_filter(
			$this->getActors($device),
			static fn ($actor): bool => Utils\Strings::startsWith(
				$actor->getIdentifier(),
				Types\ChannelPropertyIdentifier::HEATER_ACTOR,
			)
		) !== [];
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function hasCoolers(MetadataDocuments\DevicesModule\Device $device): bool
	{
		return array_filter(
			$this->getActors($device),
			static fn ($actor): bool => Utils\Strings::startsWith(
				$actor->getIdentifier(),
				Types\ChannelPropertyIdentifier::COOLER_ACTOR,
			)
		) !== [];
	}

	/**
	 * @return array<int, MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty>
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	public function getSensors(MetadataDocuments\DevicesModule\Device $device): array
	{
		$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(Types\ChannelIdentifier::SENSORS);
		$findChannelQuery->byType(Entities\Channels\Sensors::TYPE);

		$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			return [];
		}

		$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		$properties = $this->channelsPropertiesConfigurationRepository->findAllBy($findChannelPropertiesQuery);

		return array_filter(
			$properties,
			static fn (MetadataDocuments\DevicesModule\ChannelProperty $property): bool =>
				(
					$property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
					|| $property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty
				) && (
					Utils\Strings::startsWith(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::TARGET_SENSOR,
					)
					|| Utils\Strings::startsWith(
						$property->getIdentifier(),
						Types\ChannelPropertyIdentifier::FLOOR_SENSOR,
					)
				),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function hasSensors(MetadataDocuments\DevicesModule\Device $device): bool
	{
		return array_filter(
			$this->getSensors($device),
			static fn ($sensor): bool => Utils\Strings::startsWith(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::TARGET_SENSOR,
			)
		) !== [];
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function hasFloorSensors(MetadataDocuments\DevicesModule\Device $device): bool
	{
		return array_filter(
			$this->getSensors($device),
			static fn ($sensor): bool => Utils\Strings::startsWith(
				$sensor->getIdentifier(),
				Types\ChannelPropertyIdentifier::FLOOR_SENSOR,
			)
		) !== [];
	}

	/**
	 * @return array<int, MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty>
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	public function getOpenings(MetadataDocuments\DevicesModule\Device $device): array
	{
		$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(Types\ChannelIdentifier::OPENINGS);
		$findChannelQuery->byType(Entities\Channels\Sensors::TYPE);

		$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			return [];
		}

		$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		$properties = $this->channelsPropertiesConfigurationRepository->findAllBy($findChannelPropertiesQuery);

		return array_filter(
			$properties,
			static fn (MetadataDocuments\DevicesModule\ChannelProperty $property): bool =>
				(
					$property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
					|| $property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty
				) && Utils\Strings::startsWith(
					$property->getIdentifier(),
					Types\ChannelPropertyIdentifier::OPENING_SENSOR,
				),
		);
	}

	/**
	 * @return array<string>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 */
	public function getHvacModes(MetadataDocuments\DevicesModule\Device $device): array
	{
		$format = $this->getHvacMode($device)?->getFormat();

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
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 */
	public function getPresetModes(MetadataDocuments\DevicesModule\Device $device): array
	{
		$format = $this->getPresetMode($device)?->getFormat();

		if (!$format instanceof MetadataValueObjects\StringEnumFormat) {
			return [];
		}

		return array_map(
			static fn (string $item): string => Types\Preset::get($item)->getValue(),
			$format->toArray(),
		);
	}

}
