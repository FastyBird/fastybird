<?php declare(strict_types = 1);

/**
 * DeviceBlockDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use FastyBird\Connector\Shelly\Entities\API\Entity;
use Nette;
use function array_map;
use function array_unique;
use const SORT_REGULAR;

/**
 * Block description entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceBlockDescription implements Entity
{

	use Nette\SmartObject;

	/** @var array<BlockSensorDescription> */
	private array $sensors;

	/**
	 * @param array<BlockSensorDescription> $sensors
	 */
	public function __construct(
		private readonly int $identifier,
		private readonly string $description,
		array $sensors = [],
	)
	{
		$this->sensors = array_unique($sensors, SORT_REGULAR);
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
		return $this->sensors;
	}

	public function addSensor(BlockSensorDescription $sensor): void
	{
		$this->sensors[] = $sensor;

		$this->sensors = array_unique($this->sensors, SORT_REGULAR);
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
