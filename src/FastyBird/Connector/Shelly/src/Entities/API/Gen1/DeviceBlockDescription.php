<?php declare(strict_types = 1);

/**
 * DeviceBlockDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use FastyBird\Connector\Shelly\Entities;
use Orisai\ObjectMapper;
use function array_map;
use function array_unique;
use const SORT_REGULAR;

/**
 * Block description entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceBlockDescription implements Entities\API\Entity
{

	/** @var array<BlockSensorDescription> */
	private array $filteredSensors;

	/**
	 * @param array<BlockSensorDescription> $sensors
	 */
	public function __construct(
		#[ObjectMapper\Rules\IntValue()]
		private readonly int $identifier,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $description,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(BlockSensorDescription::class),
		)]
		private readonly array $sensors = [],
	)
	{
		$this->filteredSensors = array_unique($this->sensors, SORT_REGULAR);
	}

	public function getIdentifier(): int
	{
		return $this->identifier;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	/**
	 * @return array<BlockSensorDescription>
	 */
	public function getSensors(): array
	{
		return $this->filteredSensors;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'identifier' => $this->getIdentifier(),
			'description' => $this->getDescription(),
			'sensors' => array_map(
				static fn (BlockSensorDescription $sensor): array => $sensor->toArray(),
				$this->getSensors(),
			),
		];
	}

}
