<?php declare(strict_types = 1);

/**
 * SyncDevicesEvent.php
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
use Orisai\ObjectMapper;
use stdClass;

/**
 * Synchronise third-party devices with NS Panel event request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SyncDevicesEvent implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(Entities\API\Header::class)]
		private readonly Entities\API\Header $header,
		#[ObjectMapper\Rules\MappedObjectValue(SyncDevicesEventPayload::class)]
		private readonly SyncDevicesEventPayload $payload,
	)
	{
	}

	public function getHeader(): Entities\API\Header
	{
		return $this->header;
	}

	public function getPayload(): SyncDevicesEventPayload
	{
		return $this->payload;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'header' => $this->getHeader()->toArray(),
			'payload' => $this->getPayload()->toArray(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->header = $this->getHeader()->toJson();
		$json->payload = $this->getPayload()->toJson();

		return $json;
	}

}
