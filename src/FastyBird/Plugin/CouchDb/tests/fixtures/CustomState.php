<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use FastyBird\CouchDbPlugin\States;
use function array_merge;
use const DATE_ATOM;

class CustomState extends States\State
{

	public const CREATE_FIELDS = [
		0 => 'id',
		1 => 'value',
		'created' => false,
	];

	public const UPDATE_FIELDS = [
		'value',
		'updated',
	];

	private string|null $value = null;

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
	public function toArray(): array
	{
		return array_merge([
			'value' => $this->getValue(),
			'created' => $this->getCreated()?->format(DATE_ATOM),
			'updated' => $this->getUpdated()?->format(DATE_ATOM),
		], parent::toArray());
	}

}
