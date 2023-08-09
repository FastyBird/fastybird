<?php declare(strict_types = 1);

/**
 * ErrorEvent.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           32.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API\Response;

use FastyBird\Connector\NsPanel\Entities;
use Orisai\ObjectMapper;
use stdClass;

/**
 * NS Panel event error response definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ErrorEvent implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(Entities\API\Header::class)]
		private readonly Entities\API\Header $header,
		#[ObjectMapper\Rules\MappedObjectValue(ErrorEventPayload::class)]
		private readonly ErrorEventPayload $payload,
	)
	{
	}

	public function getHeader(): Entities\API\Header
	{
		return $this->header;
	}

	public function getPayload(): ErrorEventPayload
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
