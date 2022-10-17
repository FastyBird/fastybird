<?php declare(strict_types = 1);

/**
 * BlockDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 * @since          0.37.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\Messages;

use FastyBird\Connector\Shelly\Types;
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
final class BlockDescription implements Entity
{

	use Nette\SmartObject;

	/** @var Array<SensorDescription> */
	private array $sensors;

	/**
	 * @param Array<SensorDescription> $sensors
	 */
	public function __construct(
		private readonly Types\MessageSource $source,
		private readonly int $identifier,
		private readonly string $description,
		array $sensors = [],
	)
	{
		$this->sensors = array_unique($sensors, SORT_REGULAR);
	}

	public function getSource(): Types\MessageSource
	{
		return $this->source;
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
	 * @return Array<SensorDescription>
	 */
	public function getSensors(): array
	{
		return $this->sensors;
	}

	public function addSensor(SensorDescription $sensor): void
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
			'source' => $this->getSource()->getValue(),
			'identifier' => $this->getIdentifier(),
			'description' => $this->getDescription(),
			'sensors' => array_map(
				static fn (SensorDescription $sensor): array => $sensor->toArray(),
				$this->getSensors(),
			),
		];
	}

}
