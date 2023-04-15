<?php declare(strict_types = 1);

/**
 * Email.php
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
use Ramsey\Uuid;

/**
 * Email entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Email implements Entities\Entity
{

	private Uuid\UuidInterface $id;

	private Uuid\UuidInterface $account;

	public function __construct(
		string $id,
		string $account,
		private readonly string $address,
		private readonly bool $default,
		private readonly bool $verified,
		private readonly bool $private,
		private readonly bool $public,
	)
	{
		$this->id = Uuid\Uuid::fromString($id);
		$this->account = Uuid\Uuid::fromString($account);
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getAccount(): Uuid\UuidInterface
	{
		return $this->account;
	}

	public function getAddress(): string
	{
		return $this->address;
	}

	public function isDefault(): bool
	{
		return $this->default;
	}

	public function isVerified(): bool
	{
		return $this->verified;
	}

	public function isPrivate(): bool
	{
		return $this->private;
	}

	public function isPublic(): bool
	{
		return $this->public;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'account' => $this->getAccount()->toString(),
			'address' => $this->getAddress(),
			'default' => $this->isDefault(),
			'verified' => $this->isVerified(),
			'private' => $this->isPrivate(),
			'public' => $this->isPublic(),
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
