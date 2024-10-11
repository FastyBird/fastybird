<?php declare(strict_types = 1);

/**
 * Category.php
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

namespace FastyBird\Connector\NsPanel\Mapping\Categories;

use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;

/**
 * Basic category interface
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Mapping
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class Category implements Mapping\Mapping
{

	/**
	 * @param array<Types\Capability> $requiredCapabilities
	 * @param array<Types\Capability> $optionalCapabilities
	 */
	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Category::class)]
		private Types\Category $category,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string $description,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Capability::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $requiredCapabilities,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\BackedEnumValue(class: Types\Capability::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $optionalCapabilities,
	)
	{
	}

	public function getCategory(): Types\Category
	{
		return $this->category;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	/**
	 * @return array<Types\Capability>
	 */
	public function getRequiredCapabilities(): array
	{
		return $this->requiredCapabilities;
	}

	/**
	 * @return array<Types\Capability>
	 */
	public function getOptionalCapabilities(): array
	{
		return $this->optionalCapabilities;
	}

}
