<?php declare(strict_types = 1);

/**
 * Type.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           24.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Entities\Messages\Exposes;

use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use Orisai\ObjectMapper;

/**
 * Device expose type configuration message
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Type implements Entities\Messages\Entity
{

	public function __construct(
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\ExposeType::class)]
		private readonly Types\ExposeType $type,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Zigbee2Mqtt\Constants::VALUE_NOT_AVAILABLE]),
		])]
		private readonly string $name = Zigbee2Mqtt\Constants::VALUE_NOT_AVAILABLE,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Zigbee2Mqtt\Constants::VALUE_NOT_AVAILABLE]),
		])]
		private readonly string $label = Zigbee2Mqtt\Constants::VALUE_NOT_AVAILABLE,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\ArrayEnumValue(cases: [Zigbee2Mqtt\Constants::VALUE_NOT_AVAILABLE]),
		])]
		private readonly string $property = Zigbee2Mqtt\Constants::VALUE_NOT_AVAILABLE,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private readonly int $access = 0,
	)
	{
	}

	public function getType(): Types\ExposeType
	{
		return $this->type;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function getProperty(): string
	{
		return $this->property;
	}

	public function getAccess(): int
	{
		return $this->access;
	}

	public function isSettable(): bool
	{
		return ($this->access & 0b010) !== 0;
	}

	public function isQueryable(): bool
	{
		return ($this->access & 0b100) !== 0;
	}

	public function toArray(): array
	{
		return [
			'type' => $this->getType()->getValue(),
			'name' => $this->getName(),
			'label' => $this->getLabel(),
			'property' => $this->getProperty(),
			'access' => $this->getAccess(),
		];
	}

}
