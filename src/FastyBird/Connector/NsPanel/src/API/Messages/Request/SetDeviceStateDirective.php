<?php declare(strict_types = 1);

/**
 * SetDeviceStateDirective.php
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

/**
 * NS Panel requested set device state directive request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class SetDeviceStateDirective implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(API\Messages\Header::class)]
		private API\Messages\Header $header,
		#[ObjectMapper\Rules\MappedObjectValue(SetDeviceStateDirectiveEndpoint::class)]
		private SetDeviceStateDirectiveEndpoint $endpoint,
		#[ObjectMapper\Rules\MappedObjectValue(SetDeviceStateDirectivePayload::class)]
		private SetDeviceStateDirectivePayload $payload,
	)
	{
	}

	public function getHeader(): API\Messages\Header
	{
		return $this->header;
	}

	public function getEndpoint(): SetDeviceStateDirectiveEndpoint
	{
		return $this->endpoint;
	}

	public function getPayload(): SetDeviceStateDirectivePayload
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
