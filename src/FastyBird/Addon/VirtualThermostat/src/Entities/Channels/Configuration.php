<?php declare(strict_types = 1);

/**
 * Configuration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           20.10.23
 */

namespace FastyBird\Addon\VirtualThermostat\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Addon\VirtualThermostat\Entities;
use FastyBird\Addon\VirtualThermostat\Types;
use FastyBird\Connector\Virtual\Entities as VirtualEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use function floatval;
use function is_numeric;

/**
 * @ORM\Entity
 */
class Configuration extends VirtualEntities\VirtualChannel
{

	public const TYPE = 'virtual-thermostat-addon-configuration';

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::TYPE;
	}

	public function getSource(): MetadataTypes\AddonSource
	{
		return MetadataTypes\AddonSource::get(MetadataTypes\AddonSource::VIRTUAL_THERMOSTAT);
	}

	/**
	 * Maximum allowed temperature measured with floor sensor
	 *
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getMaximumFloorTemp(): float
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Properties\Property $property): bool => $property->getIdentifier() === Types\ChannelPropertyIdentifier::MAXIMUM_FLOOR_TEMPERATURE
			)
			->first();

		if (
			$property instanceof DevicesEntities\Channels\Properties\Variable
			&& is_numeric($property->getValue())
		) {
			return floatval($property->getValue());
		}

		return Entities\Devices\Thermostat::MAXIMUM_FLOOR_TEMPERATURE;
	}

	/**
	 * Set a minimum amount of time that the actor will be turned on or off
	 *
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getMinimumCycleDuration(): float|null
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Properties\Property $property): bool => $property->getIdentifier() === Types\ChannelPropertyIdentifier::MINIMUM_CYCLE_DURATION
			)
			->first();

		if (
			$property instanceof DevicesEntities\Channels\Properties\Variable
			&& is_numeric($property->getValue())
		) {
			return floatval($property->getValue());
		}

		return null;
	}

	/**
	 * Minimum temperature value to be cooler actor turned on (hysteresis low value)
	 * For example, if the target temperature is 25 and the tolerance is 0.5 the heater will start when the sensor equals or goes below 24.5
	 *
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getLowTargetTempTolerance(): float
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Properties\Property $property): bool => $property->getIdentifier() === Types\ChannelPropertyIdentifier::LOW_TARGET_TEMPERATURE_TOLERANCE
			)
			->first();

		if (
			$property instanceof DevicesEntities\Channels\Properties\Variable
			&& is_numeric($property->getValue())
		) {
			return floatval($property->getValue());
		}

		return Entities\Devices\Thermostat::COLD_TOLERANCE;
	}

	/**
	 * Maximum temperature value to be cooler actor turned on (hysteresis high value)
	 * For example, if the target temperature is 25 and the tolerance is 0.5 the heater will stop when the sensor equals or goes above 25.5
	 *
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getHighTargetTempTolerance(): float
	{
		$property = $this->properties
			->filter(
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Properties\Property $property): bool => $property->getIdentifier() === Types\ChannelPropertyIdentifier::HIGH_TARGET_TEMPERATURE_TOLERANCE
			)
			->first();

		if (
			$property instanceof DevicesEntities\Channels\Properties\Variable
			&& is_numeric($property->getValue())
		) {
			return floatval($property->getValue());
		}

		return Entities\Devices\Thermostat::HOT_TOLERANCE;
	}

}
