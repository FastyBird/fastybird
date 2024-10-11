<?php declare(strict_types = 1);

/**
 * Capability.php
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

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use TypeError;
use ValueError;
use function array_key_exists;
use function preg_match;

/**
 * Basic capability interface
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Mapping
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class Capability implements Mapping\Mapping
{

	/**
	 * @param array<Mapping\Configurations\Configuration> $configurations
	 * @param array<Mapping\Attributes\Attribute> $attributes
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('capability')]
		private string $capabilityWithName,
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Permission::class)]
		private Types\Permission $permission,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $multiple,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Configurations\Configuration::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $configurations,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Attributes\Attribute::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $attributes,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getCapability(): Types\Capability
	{
		preg_match(NsPanel\Constants::CHANNEL_IDENTIFIER, $this->capabilityWithName, $matches);

		if (!array_key_exists('capability', $matches)) {
			throw new Exceptions\InvalidState('Capability definition invalid value');
		}

		try {
			return Types\Capability::from($matches['capability']);
		} catch (TypeError | ValueError) {
			throw new Exceptions\InvalidState('Capability definition invalid value');
		}
	}

	public function getName(): string|null
	{
		preg_match(NsPanel\Constants::CHANNEL_IDENTIFIER, $this->capabilityWithName, $matches);

		return $matches['name'] ?? null;
	}

	public function getPermission(): Types\Permission
	{
		return $this->permission;
	}

	public function isMultiple(): bool
	{
		return $this->multiple;
	}

	/**
	 * @return array<Mapping\Configurations\Configuration>
	 */
	public function getConfigurations(): array
	{
		return $this->configurations;
	}

	public function findConfiguration(string $type): Mapping\Configurations\Configuration|null
	{
		foreach ($this->configurations as $configuration) {
			if ($configuration->getConfiguration()->value === $type) {
				return $configuration;
			}
		}

		return null;
	}

	/**
	 * @return array<Mapping\Attributes\Attribute>
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function findAttribute(Types\Attribute $type): Mapping\Attributes\Attribute|null
	{
		foreach ($this->attributes as $attribute) {
			if ($attribute->getAttribute() === $type) {
				return $attribute;
			}
		}

		return null;
	}

}
