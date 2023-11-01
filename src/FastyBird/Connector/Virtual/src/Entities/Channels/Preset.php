<?php declare(strict_types = 1);

/**
 * PresetAway.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           20.10.23
 */

namespace FastyBird\Connector\Virtual\Entities\Channels;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use function floatval;
use function is_numeric;

/**
 * @ORM\Entity
 */
class Preset extends Entities\VirtualChannel
{

	public const TYPE = 'virtual-thermostat-preset';

	public function getType(): string
	{
		return self::TYPE;
	}

	public function getDiscriminatorName(): string
	{
		return self::TYPE;
	}

	public function getTargetTemp(): DevicesEntities\Channels\Properties\Dynamic|null
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Properties\Property $property): bool => $property->getIdentifier() === Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE
			)
			->first();

		if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
			return $property;
		}

		return null;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getCoolingThresholdTemp(): float|null
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Properties\Property $property): bool => $property->getIdentifier() === Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getHeatingThresholdTemp(): float|null
	{
		$property = $this->properties
			->filter(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				static fn (DevicesEntities\Channels\Properties\Property $property): bool => $property->getIdentifier() === Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE
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

}
