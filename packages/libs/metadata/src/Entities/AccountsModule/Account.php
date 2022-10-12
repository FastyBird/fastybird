<?php declare(strict_types = 1);

/**
 * Account.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Metadata\Entities\AccountsModule;

use DateTimeInterface;
use FastyBird\Metadata\Entities;
use FastyBird\Metadata\Types;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;

/**
 * Account entity
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Account implements Entities\Entity
{

	private Uuid\UuidInterface $id;

	private Types\AccountState $state;

	private DateTimeInterface|null $registered = null;

	private DateTimeInterface|null $lastVisit = null;

	/** @var array<string> */
	private array $roles;

	protected Uuid\UuidInterface|null $parent;

	/** @var array<Uuid\UuidInterface> */
	protected array $children;

	/**
	 * @param Array<int, string>|Utils\ArrayHash<string> $roles
	 * @param Array<int, string>|Utils\ArrayHash<string> $children
	 */
	public function __construct(
		string $id,
		private readonly string $firstName,
		private readonly string $lastName,
		private readonly string $language,
		string $state,
		private readonly string|null $middleName = null,
		private readonly string|null $email = null,
		string|null $registered = null,
		string|null $lastVisit = null,
		array|Utils\ArrayHash $roles = [],
		string|null $parent = null,
		array|Utils\ArrayHash $children = [],
	)
	{
		$this->id = Uuid\Uuid::fromString($id);
		$this->state = Types\AccountState::get($state);
		$this->roles = (array) $roles;

		if ($registered !== null) {
			$registered = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, $registered);

			if ($registered instanceof DateTimeInterface) {
				$this->registered = $registered;
			}
		}

		if ($lastVisit !== null) {
			$lastVisit = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, $lastVisit);

			if ($lastVisit instanceof DateTimeInterface) {
				$this->lastVisit = $lastVisit;
			}
		}

		$this->parent = $parent !== null ? Uuid\Uuid::fromString($parent) : null;
		$this->children = array_map(
			static fn (string $item): Uuid\UuidInterface => Uuid\Uuid::fromString($item),
			(array) $children,
		);
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getFirstName(): string
	{
		return $this->firstName;
	}

	public function getLastName(): string
	{
		return $this->lastName;
	}

	public function getMiddleName(): string|null
	{
		return $this->middleName;
	}

	public function getEmail(): string|null
	{
		return $this->email;
	}

	public function getState(): Types\AccountState
	{
		return $this->state;
	}

	public function getLanguage(): string
	{
		return $this->language;
	}

	public function getRegistered(): DateTimeInterface|null
	{
		return $this->registered;
	}

	public function getLastVisit(): DateTimeInterface|null
	{
		return $this->lastVisit;
	}

	/**
	 * @return array<string>
	 */
	public function getRoles(): array
	{
		return $this->roles;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'first_name' => $this->getFirstName(),
			'last_name' => $this->getLastName(),
			'middle_name' => $this->getMiddleName(),
			'email' => $this->getEmail(),
			'state' => $this->getState()->getValue(),
			'language' => $this->getLanguage(),
			'registered' => $this->getRegistered()?->format(DateTimeInterface::ATOM),
			'last_visit' => $this->getLastVisit()?->format(DateTimeInterface::ATOM),
			'roles' => $this->getRoles(),
		];
	}

	/**
	 * @return Array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
