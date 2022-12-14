<?php declare(strict_types = 1);

/**
 * Condition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 * @since          0.2.0
 *
 * @date           12.01.22
 */

namespace FastyBird\Bridge\RedisDbTriggersModule\States;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use FastyBird\Module\Triggers\States as TriggersStates;
use FastyBird\Plugin\RedisDb\States as RedisDbStates;
use function array_merge;

/**
 * Trigger condition state
 *
 * @package        FastyBird:MiniServer!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Condition extends RedisDbStates\State implements TriggersStates\Condition
{

	private bool $validationResult = false;

	private string|null $createdAt = null;

	private string|null $updatedAt = null;

	public function isFulfilled(): bool
	{
		return $this->validationResult;
	}

	public function setFulfilled(bool $result): void
	{
		$this->validationResult = $result;
	}

	/**
	 * @throws Exception
	 */
	public function getCreatedAt(): DateTimeInterface|null
	{
		return $this->createdAt !== null ? new DateTimeImmutable($this->createdAt) : null;
	}

	public function setCreatedAt(string|null $createdAt): void
	{
		$this->createdAt = $createdAt;
	}

	/**
	 * @throws Exception
	 */
	public function getUpdatedAt(): DateTimeInterface|null
	{
		return $this->updatedAt !== null ? new DateTimeImmutable($this->updatedAt) : null;
	}

	public function setUpdatedAt(string|null $updatedAt): void
	{
		$this->updatedAt = $updatedAt;
	}

	public static function getCreateFields(): array
	{
		return [
			0 => 'id',
			'validationResult' => false,
			'createdAt' => null,
			'updatedAt' => null,
		];
	}

	public static function getUpdateFields(): array
	{
		return [
			'validationResult',
			'updatedAt',
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	public function toArray(): array
	{
		return array_merge([
			'validation_result' => $this->isFulfilled(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		], parent::toArray());
	}

}
