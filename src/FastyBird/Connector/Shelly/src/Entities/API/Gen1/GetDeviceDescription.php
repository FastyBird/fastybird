<?php declare(strict_types = 1);

/**
 * GetDeviceDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           20.12.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen1;

use FastyBird\Connector\Shelly\Entities;
use function array_map;
use function array_unique;
use const SORT_REGULAR;

/**
 * Generation 1 device description entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class GetDeviceDescription implements Entities\API\Entity
{

	/** @var array<DeviceBlockDescription> */
	private array $blocks;

	/**
	 * @param array<DeviceBlockDescription> $blocks
	 */
	public function __construct(array $blocks)
	{
		$this->blocks = array_unique($blocks, SORT_REGULAR);
	}

	/**
	 * @return array<DeviceBlockDescription>
	 */
	public function getBlocks(): array
	{
		return $this->blocks;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'blocks' => array_map(
				static fn (DeviceBlockDescription $block): array => $block->toArray(),
				$this->getBlocks(),
			),
		];
	}

}
