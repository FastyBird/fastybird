<?php declare(strict_types = 1);

/**
 * Header.php
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

namespace FastyBird\Connector\NsPanel\API\Messages;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Request & Response header
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class Header implements Message
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Header::class)]
		private Types\Header $name,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('message_id')]
		private string $messageId,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $version,
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
			'name' => $this->getName()->value,
			'message_id' => $this->getMessageId(),
			'version' => $this->getVersion(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->name = $this->getName()->value;
		$json->message_id = $this->getMessageId();
		$json->version = $this->getVersion();

		return $json;
	}

}
