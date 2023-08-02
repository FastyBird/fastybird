<?php declare(strict_types = 1);

/**
 * SetDeviceStatusDirective.php
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
 * NS Panel requested set device status directive request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SetDeviceStatusDirective implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(Entities\API\Header::class)]
		private readonly Entities\API\Header $header,
		#[ObjectMapper\Rules\MappedObjectValue(SetDeviceStatusDirectiveEndpoint::class)]
		private readonly SetDeviceStatusDirectiveEndpoint $endpoint,
		#[ObjectMapper\Rules\MappedObjectValue(SetDeviceStatusDirectivePayload::class)]
		private readonly SetDeviceStatusDirectivePayload $payload,
	)
	{
	}

	public function getHeader(): Entities\API\Header
	{
		return $this->header;
	}

	public function getEndpoint(): SetDeviceStatusDirectiveEndpoint
	{
		return $this->endpoint;
	}

	public function getPayload(): SetDeviceStatusDirectivePayload
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
