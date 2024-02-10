<?php declare(strict_types = 1);

/**
 * DiscoveredDeviceParameter.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           15.05.23
 */

namespace FastyBird\Connector\Sonoff\Queue\Messages\Parameters;

use FastyBird\Connector\Sonoff\Queue;
use FastyBird\Connector\Sonoff\Types;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Orisai\ObjectMapper;

/**
 * Discovered device parameter entity
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DiscoveredDeviceParameter implements Queue\Messages\Message
{

	/**
	 * @param array<int, string>|array<int, string|int|float|array<int, string|int|float>>|null $format
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $group,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $name,
		#[ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\ParameterType::class)]
		private readonly Types\ParameterType $type,
		#[ObjectMapper\Rules\BackedEnumValue(class: MetadataTypes\DataType::class)]
		private readonly MetadataTypes\DataType $dataType,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayOf(
				new ObjectMapper\Rules\StringValue(),
				new ObjectMapper\Rules\IntValue(unsigned: true),
			),
			new ObjectMapper\Rules\ArrayOf(
				new ObjectMapper\Rules\AnyOf([
					new ObjectMapper\Rules\StringValue(),
					new ObjectMapper\Rules\IntValue(),
					new ObjectMapper\Rules\FloatValue(),
					new ObjectMapper\Rules\ArrayOf(
						new ObjectMapper\Rules\AnyOf([
							new ObjectMapper\Rules\StringValue(),
							new ObjectMapper\Rules\IntValue(),
							new ObjectMapper\Rules\FloatValue(),
						]),
						new ObjectMapper\Rules\IntValue(unsigned: true),
					),
				]),
				new ObjectMapper\Rules\IntValue(unsigned: true),
			),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly array|null $format,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $settable,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $queryable,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(unsigned: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		private readonly int|null $scale,
	)
	{
	}

	public function getGroup(): string
	{
		return $this->group;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getType(): Types\ParameterType
	{
		return $this->type;
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	/**
	 * @return array<int, string>|array<int, string|int|float|array<int, string|int|float>>|null
	 */
	public function getFormat(): array|null
	{
		return $this->format;
	}

	public function isSettable(): bool
	{
		return $this->settable;
	}

	public function isQueryable(): bool
	{
		return $this->queryable;
	}

	public function getScale(): int|null
	{
		return $this->scale;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'group' => $this->getGroup(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'type' => $this->getType()->getValue(),
			'data_type' => $this->getDataType()->value,
			'format' => $this->getFormat(),
			'settable' => $this->isSettable(),
			'queryable' => $this->isQueryable(),
			'scale' => $this->getScale(),
		];
	}

}
