<?php declare(strict_types = 1);

/**
 * SyncDevicesPayload.php
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

namespace FastyBird\Connector\NsPanel\Entities\API\Response;

use FastyBird\Connector\NsPanel\Entities;
use Orisai\ObjectMapper;
use stdClass;
use function array_map;

/**
 * Synchronise third-party devices with NS Panel even payload response definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SyncDevicesPayload implements Entities\API\Entity
{

	/**
	 * @param array<SyncDevicesPayloadEndpoint> $endpoints
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(SyncDevicesPayloadEndpoint::class),
		)]
		private readonly array $endpoints,
	)
	{
	}

	/**
	 * @return array<SyncDevicesPayloadEndpoint>
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
				static fn (SyncDevicesPayloadEndpoint $description): array => $description->toArray(),
				$this->getEndpoints(),
			),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->endpoints = array_map(
			static fn (SyncDevicesPayloadEndpoint $description): object => $description->toJson(),
			$this->getEndpoints(),
		);

		return $json;
	}

}
