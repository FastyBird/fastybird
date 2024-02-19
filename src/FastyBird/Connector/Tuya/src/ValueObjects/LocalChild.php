<?php declare(strict_types = 1);

/**
 * LocalChild.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     ValueObjects
 * @since          1.0.0
 *
 * @date           13.12.22
 */

namespace FastyBird\Connector\Tuya\ValueObjects;

use FastyBird\Connector\Tuya\Types;
use Orisai\ObjectMapper;

/**
 * Local device child
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     ValueObjects
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class LocalChild implements ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $identifier,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('node_id')]
		private string $nodeId,
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\LocalDeviceType::class)]
		private Types\LocalDeviceType $type,
	)
	{
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getType(): Types\LocalDeviceType
	{
		return $this->type;
	}

	public function getNodeId(): string
	{
		return $this->nodeId;
	}

}
