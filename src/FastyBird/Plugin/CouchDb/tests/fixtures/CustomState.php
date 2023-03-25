<?php declare(strict_types = 1);

namespace FastyBird\Plugin\CouchDb\Tests\Fixtures;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use FastyBird\Plugin\CouchDb\States;
use function array_merge;
use const DATE_ATOM;

class CustomState extends States\State
{

	private string|null $value = null;

	private string|null $camelCased = null;

	private string|null $created = null;

	private string|null $updated = null;

	public function getValue(): string|null
	{
		return $this->value;
	}

	public function setValue(string|null $value): void
	{
		$this->value = $value;
	}

	public function getCamelCased(): string|null
	{
		return $this->camelCased;
	}

	public function setCamelCased(string|null $camelCased): void
	{
		$this->camelCased = $camelCased;
	}

	/**
	 * @throws Exception
	 */
	public function getCreated(): DateTimeInterface|null
	{
		return $this->created !== null ? new DateTimeImmutable($this->created) : null;
	}

	public function setCreated(string|null $created): void
	{
		$this->created = $created;
	}

	/**
	 * @throws Exception
	 */
	public function getUpdated(): DateTimeInterface|null
	{
		return $this->updated !== null ? new DateTimeImmutable($this->updated) : null;
	}

	public function setUpdated(string|null $updated): void
	{
		$this->updated = $updated;
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
			'created' => $this->getCreated()?->format(DATE_ATOM),
			'updated' => $this->getUpdated()?->format(DATE_ATOM),
		], parent::toArray());
	}

}
