<?php declare(strict_types = 1);

/**
 * ChannelStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           18.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\Messages;

use FastyBird\Connector\Shelly\Types;
use Nette;
use function array_map;
use function array_unique;
use const SORT_REGULAR;

/**
 * Block status entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelStatus implements Entity
{

	use Nette\SmartObject;

	/** @var array<PropertyStatus> */
	private array $sensors;

	/**
	 * @param array<PropertyStatus> $sensors
	 */
	public function __construct(
		private readonly Types\MessageSource $source,
		private readonly int $channel,
		array $sensors = [],
	)
	{
		$this->sensors = array_unique($sensors, SORT_REGULAR);
	}

	public function getSource(): Types\MessageSource
	{
		return $this->source;
	}

	public function getChannel(): int
	{
		return $this->channel;
	}

	/**
	 * @return array<PropertyStatus>
	 */
	public function getSensors(): array
	{
		return $this->sensors;
	}

	public function addSensor(PropertyStatus $sensor): void
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
			'channel' => $this->getChannel(),
			'sensors' => array_map(
				static fn (PropertyStatus $sensor): array => $sensor->toArray(),
				$this->getSensors(),
			),
		];
	}

}
