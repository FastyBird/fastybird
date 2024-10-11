<?php declare(strict_types = 1);

/**
 * Group.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Mapping
 * @since          1.0.0
 *
 * @date           02.10.24
 */

namespace FastyBird\Connector\NsPanel\Mapping\Capabilities;

use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;

/**
 * Basic group interface
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Mapping
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class Group implements Mapping\Mapping
{

	/**
	 * @param array<Mapping\Capabilities\Capability> $capabilities
	 */
	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Group::class)]
		private Types\Group $type,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string $description,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Capabilities\Capability::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $capabilities,
	)
	{
	}

	public function getType(): Types\Group
	{
		return $this->type;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	/**
	 * @return array<Mapping\Capabilities\Capability>
	 */
	public function getCapabilities(): array
	{
		return $this->capabilities;
	}

}
