<?php declare(strict_types = 1);

/**
 * ReportDeviceOnlineEvent.php
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
 * Report third-party device online state to NS Panel event request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ReportDeviceOnlineEvent implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(Entities\API\Header::class)]
		private readonly Entities\API\Header $header,
		#[ObjectMapper\Rules\MappedObjectValue(ReportDeviceOnlineEventEndpoint::class)]
		private readonly ReportDeviceOnlineEventEndpoint $endpoint,
		#[ObjectMapper\Rules\MappedObjectValue(ReportDeviceOnlineEventPayload::class)]
		private readonly ReportDeviceOnlineEventPayload $payload,
	)
	{
	}

	public function getHeader(): Entities\API\Header
	{
		return $this->header;
	}

	public function getEndpoint(): ReportDeviceOnlineEventEndpoint
	{
		return $this->endpoint;
	}

	public function getPayload(): ReportDeviceOnlineEventPayload
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
			'endpoint' => $this->getEndpoint()->toArray(),
			'payload' => $this->getPayload()->toArray(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->header = $this->getHeader()->toJson();
		$json->endpoint = $this->getEndpoint()->toJson();
		$json->payload = $this->getPayload()->toJson();

		return $json;
	}

}
