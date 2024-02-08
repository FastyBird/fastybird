<?php declare(strict_types = 1);

/**
 * Account.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Library\Metadata\Documents\AccountsModule;

use DateTimeInterface;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;

/**
 * Account document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Account implements Documents\Document
{

	/**
	 * @param array<int, string> $roles
	 * @param array<int, Uuid\UuidInterface> $children
	 */
	public function __construct(
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('first_name')]
		private readonly string $firstName,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('last_name')]
		private readonly string $lastName,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $language,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\AccountState::class),
			new ObjectMapper\Rules\InstanceOfValue(type: Types\AccountState::class),
		])]
		private readonly Types\AccountState $state,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('middle_name')]
		private readonly string|null $middleName = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(pattern: '/^[\w\-\.]+@[\w\-\.]+\.+[\w-]{2,63}$/', notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $email = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly DateTimeInterface|null $registered = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('last_visit')]
		private readonly DateTimeInterface|null $lastVisit = null,
		#[ObjectMapper\Rules\ArrayOf(
			item: new ObjectMapper\Rules\StringValue(notEmpty: true),
			minItems: 1,
		)]
		private readonly array $roles = [],
		#[ObjectMapper\Rules\AnyOf([
			new ApplicationObjectMapper\Rules\UuidValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly Uuid\UuidInterface|null $parent = null,
		#[ObjectMapper\Rules\ArrayOf(
			new ApplicationObjectMapper\Rules\UuidValue(),
		)]
		private readonly array $children = [],
	)
	{
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

	public function getParent(): Uuid\UuidInterface|null
	{
		return $this->parent;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getChildren(): array
	{
		return $this->children;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'first_name' => $this->getFirstName(),
			'last_name' => $this->getLastName(),
			'middle_name' => $this->getMiddleName(),
			'email' => $this->getEmail(),
			'state' => $this->getState()->value,
			'language' => $this->getLanguage(),
			'registered' => $this->getRegistered()?->format(DateTimeInterface::ATOM),
			'last_visit' => $this->getLastVisit()?->format(DateTimeInterface::ATOM),
			'roles' => $this->getRoles(),
			'parent' => $this->getParent()?->toString(),
			'children' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getChildren(),
			),
		];
	}

}
