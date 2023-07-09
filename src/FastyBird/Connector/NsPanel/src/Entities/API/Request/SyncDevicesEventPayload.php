<?php declare(strict_types = 1);

/**
 * SyncDevicesEventPayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API\Request;

use FastyBird\Connector\NsPanel\Entities;
use Nette;
use stdClass;
use function array_map;

/**
 * Gateway sync devices event payload request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SyncDevicesEventPayload implements Entities\API\Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<Description> $endpoints
	 */
	public function __construct(private readonly array $endpoints)
	{
	}

	/**
	 * @return array<Description>
	 */
	public function getEndpoints(): array
	{
		return $this->endpoints;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'endpoints' => array_map(
				static fn (Description $description): array => $description->toArray(),
				$this->getEndpoints(),
			),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->endpoints = array_map(
			static fn (Description $description): object => $description->toJson(),
			$this->getEndpoints(),
		);

		return $json;
	}

}
