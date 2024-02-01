<?php declare(strict_types = 1);

/**
 * VariablePropertyFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           29.01.24
 */

namespace FastyBird\Connector\HomeKit\Protocol\Characteristics;

use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Protocol;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use Ramsey\Uuid;
use function assert;

/**
 * HAP channel variable property characteristic factory
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class VariablePropertyFactory implements CharacteristicFactory
{

	/**
	 * @param array<string> $permissions
	 * @param array<int>|null $validValues
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function create(
		Uuid\UuidInterface $typeId,
		string $name,
		Types\DataType $dataType,
		array $permissions,
		Protocol\Services\Service $service,
		MetadataDocuments\DevicesModule\ChannelProperty|null $property = null,
		array|null $validValues = [],
		int|null $maxLength = null,
		float|null $minValue = null,
		float|null $maxValue = null,
		float|null $minStep = null,
		Types\CharacteristicUnit|null $unit = null,
	): VariableProperty
	{
		assert($property instanceof MetadataDocuments\DevicesModule\ChannelVariableProperty);

		return new VariableProperty(
			$typeId,
			$name,
			$dataType,
			$permissions,
			$service,
			$property,
			$validValues,
			$maxLength,
			$minValue,
			$maxValue,
			$minStep,
			$unit,
		);
	}

	/**
	 * @return class-string<DevicesEntities\Channels\Properties\Variable>
	 */
	public function getEntityClass(): string
	{
		return DevicesEntities\Channels\Properties\Variable::class;
	}

}