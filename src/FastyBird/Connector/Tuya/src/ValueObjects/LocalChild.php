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
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;

/**
 * Local device child entity
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     ValueObjects
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class LocalChild implements ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('node_id')]
		private readonly string $nodeId,
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\LocalDeviceType::class)]
		private readonly Types\LocalDeviceType $type,
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
