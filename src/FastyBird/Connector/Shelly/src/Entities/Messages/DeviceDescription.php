<?php declare(strict_types = 1);

/**
 * DeviceDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 * @since          0.37.0
 *
 * @date           18.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\Messages;

use FastyBird\Connector\Shelly\Types;
use Ramsey\Uuid;
use function array_map;
use function array_merge;
use function array_unique;
use const SORT_REGULAR;

/**
 * Device description message entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceDescription extends Device
{

	/** @var Array<BlockDescription> */
	private array $blocks;

	/**
	 * @param Array<BlockDescription> $blocks
	 */
	public function __construct(
		Types\MessageSource $source,
		Uuid\UuidInterface $connector,
		string $identifier,
		string|null $type,
		string $ipAddress,
		array $blocks,
	)
	{
		parent::__construct($source, $connector, $identifier, $type, $ipAddress);

		$this->blocks = array_unique($blocks, SORT_REGULAR);
	}

	/**
	 * @return Array<BlockDescription>
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
		return array_merge(parent::toArray(), [
			'blocks' => array_map(static fn (BlockDescription $block): array => $block->toArray(), $this->getBlocks()),
		]);
	}

}
