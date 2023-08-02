<?php declare(strict_types = 1);

/**
 * Header.php
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

namespace FastyBird\Connector\NsPanel\Entities\API;

use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Request & Response header
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Header implements Entity
{

	public function __construct(
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\Header::class)]
		private readonly Types\Header $name,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('message_id')]
		private readonly string $messageId,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $version,
	)
	{
	}

	public function getName(): Types\Header
	{
		return $this->name;
	}

	public function getMessageId(): string
	{
		return $this->messageId;
	}

	public function getVersion(): string
	{
		return $this->version;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'name' => $this->getName()->getValue(),
			'message_id' => $this->getMessageId(),
			'version' => $this->getVersion(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->name = $this->getName()->getValue();
		$json->message_id = $this->getMessageId();
		$json->version = $this->getVersion();

		return $json;
	}

}
