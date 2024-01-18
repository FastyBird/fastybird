<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           24.02.20
 */

namespace FastyBird\Connector\FbMqtt\Entities\Messages;

use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Device or channel property
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Property implements Entity
{

	/**
	 * @param array<PropertyAttribute> $attributes
	 */
	public function __construct(
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $connector,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $device,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $property,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly array $attributes = [],
		#[ObjectMapper\Rules\MappedObjectValue(class: PropertyAttribute::class)]
		private readonly string|null $value = FbMqtt\Constants::VALUE_NOT_SET,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $retained = false,
	)
	{
	}

	public function getConnector(): Uuid\UuidInterface
	{
		return $this->connector;
	}

	public function getDevice(): string
	{
		return $this->device;
	}

	public function getProperty(): string
	{
		return $this->property;
	}

	/**
	 * @return array<PropertyAttribute>
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function getValue(): string|null
	{
		return $this->value;
	}

	public function isRetained(): bool
	{
		return $this->retained;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\ParseMessage
	 */
	public function toArray(): array
	{
		$return = [
			'property' => $this->getProperty(),
		];

		foreach ($this->getAttributes() as $attribute) {
			$return[$attribute->getAttribute()] = $attribute->getValue();
		}

		if ($this->getValue() !== FbMqtt\Constants::VALUE_NOT_SET) {
			$return['value'] = $this->getValue();
		}

		return $return;
	}

}
