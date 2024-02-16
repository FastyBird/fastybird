<?php declare(strict_types = 1);

/**
 * SyncDevicesEventPayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\API\Messages\Request;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use stdClass;
use function array_map;

/**
 * Synchronise third-party devices with NS Panel event payload request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class SyncDevicesEventPayload implements API\Messages\Message
{

	/**
	 * @param array<SyncDevicesEventPayloadEndpoint> $endpoints
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(SyncDevicesEventPayloadEndpoint::class),
		)]
		private array $endpoints,
	)
	{
	}

	/**
	 * @return array<SyncDevicesEventPayloadEndpoint>
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
				static fn (SyncDevicesEventPayloadEndpoint $description): array => $description->toArray(),
				$this->getEndpoints(),
			),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->endpoints = array_map(
			static fn (SyncDevicesEventPayloadEndpoint $description): object => $description->toJson(),
			$this->getEndpoints(),
		);

		return $json;
	}

}
