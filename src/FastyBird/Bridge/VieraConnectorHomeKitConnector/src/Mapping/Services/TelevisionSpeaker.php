<?php declare(strict_types = 1);

/**
 * TelevisionSpeaker.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Mapping
 * @since          1.0.0
 *
 * @date           25.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping\Services;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use Orisai\ObjectMapper;
use TypeError;
use ValueError;
use function assert;
use function class_exists;

/**
 * Television speaker service mapping configuration
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Mapping
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
readonly class TelevisionSpeaker implements Service
{

	/**
	 * @param array<Mapping\Characteristics\Characteristic> $characteristics
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayEnumValue(cases: [HomeKitTypes\ServiceType::TELEVISION_SPEAKER->value])]
		private string $type,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $class,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $channel = null,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $multiple = false,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(class: Mapping\Characteristics\BasicCharacteristic::class),
			new ObjectMapper\Rules\IntValue(unsigned: true),
		)]
		private array $characteristics = [],
	)
	{
		assert(class_exists($this->class));
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getType(): HomeKitTypes\ServiceType
	{
		return HomeKitTypes\ServiceType::from($this->type);
	}

	public function getClass(): string
	{
		assert(class_exists($this->class));

		return $this->class;
	}

	public function getChannel(): string|null
	{
		return $this->channel;
	}

	public function isMultiple(): bool
	{
		return $this->multiple;
	}

	/**
	 * @return array<Mapping\Characteristics\Characteristic>
	 */
	public function getCharacteristics(): array
	{
		return $this->characteristics;
	}

}
