<?php declare(strict_types = 1);

/**
 * Identity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Library\Metadata\Entities\AccountsModule;

use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Types;
use Ramsey\Uuid;

/**
 * Identity entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Identity implements Entities\Entity
{

	private Uuid\UuidInterface $id;

	private Uuid\UuidInterface $account;

	private Types\IdentityState $state;

	public function __construct(
		string $id,
		string $account,
		string $state,
		private readonly string $uid,
		private readonly string|null $hash = null,
	)
	{
		$this->id = Uuid\Uuid::fromString($id);
		$this->account = Uuid\Uuid::fromString($account);
		$this->state = Types\IdentityState::get($state);
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getAccount(): Uuid\UuidInterface
	{
		return $this->account;
	}

	public function getState(): Types\IdentityState
	{
		return $this->state;
	}

	public function getUid(): string
	{
		return $this->uid;
	}

	public function getHash(): string|null
	{
		return $this->hash;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'account' => $this->getAccount()->toString(),
			'state' => $this->getState()->getValue(),
			'uid' => $this->getUid(),
			'hash' => $this->getHash(),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
