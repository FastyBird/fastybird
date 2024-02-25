<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Fixtures\Dummy;

use DateTimeInterface;
use FastyBird\Plugin\RedisDb\States;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

class DummyState extends States\State
{

	public function __construct(
		Uuid\UuidInterface $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $value = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $camelCased = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName(self::CREATED_AT_FIELD)]
		private readonly DateTimeInterface|null $createdAt = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName(self::UPDATED_AT_FIELD)]
		private readonly DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct($id);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getCreateFields(): array
	{
		return [
			0 => 'id',
			1 => 'value',
			'camelCased' => null,
			self::CREATED_AT_FIELD => null,
			self::UPDATED_AT_FIELD => null,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getUpdateFields(): array
	{
		return [
			'value',
			'camelCased',
			self::UPDATED_AT_FIELD,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge([
			'value' => $this->getValue(),
			'camelCased' => $this->getCamelCased(),
			'created_at' => $this->getCreated()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdated()?->format(DateTimeInterface::ATOM),
		], parent::toArray());
	}

	public function getValue(): string|null
	{
		return $this->value;
	}

	public function getCamelCased(): string|null
	{
		return $this->camelCased;
	}

	public function getCreated(): DateTimeInterface|null
	{
		return $this->createdAt;
	}

	public function getUpdated(): DateTimeInterface|null
	{
		return $this->updatedAt;
	}

}
