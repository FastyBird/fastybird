<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\Tests\Fixtures;

use DateTimeInterface;
use FastyBird\Plugin\CouchDb\States;
use Orisai\ObjectMapper;
use PHPOnCouch;
use Ramsey\Uuid;
use function array_merge;

class CustomState extends States\State
{

	public function __construct(
		Uuid\UuidInterface $id,
		PHPOnCouch\CouchDocument $document,
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
		private readonly DateTimeInterface|null $created = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly DateTimeInterface|null $updated = null,
	)
	{
		parent::__construct($id, $document);
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
			'created' => null,
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
			'updated',
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
			'created' => $this->getCreated()?->format(DateTimeInterface::ATOM),
			'updated' => $this->getUpdated()?->format(DateTimeInterface::ATOM),
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
		return $this->created;
	}

	public function getUpdated(): DateTimeInterface|null
	{
		return $this->updated;
	}

}
