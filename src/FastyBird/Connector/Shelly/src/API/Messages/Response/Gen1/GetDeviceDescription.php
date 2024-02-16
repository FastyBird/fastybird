<?php declare(strict_types = 1);

/**
 * GetDeviceDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           20.12.22
 */

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen1;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;
use function array_map;
use function array_unique;
use const SORT_REGULAR;

/**
 * Generation 1 device description message
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class GetDeviceDescription implements API\Messages\Message
{

	/**
	 * @param array<DeviceBlockDescription> $blocks
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(DeviceBlockDescription::class),
		)]
		private array $blocks,
	)
	{
	}

	/**
	 * @return array<DeviceBlockDescription>
	 */
	public function getBlocks(): array
	{
		return array_unique($this->blocks, SORT_REGULAR);
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
