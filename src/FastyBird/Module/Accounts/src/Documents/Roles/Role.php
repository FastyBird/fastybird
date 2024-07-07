<?php declare(strict_types = 1);

/**
 * Role.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Module\Accounts\Documents\Roles;

use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Exchange\Documents\Mapping as EXCHANGE;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Role document
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[DOC\Document(entity: Entities\Roles\Role::class)]
#[EXCHANGE\RoutingMap([
	Accounts\Constants::MESSAGE_BUS_ROLE_DOCUMENT_REPORTED_ROUTING_KEY,
	Accounts\Constants::MESSAGE_BUS_ROLE_DOCUMENT_CREATED_ROUTING_KEY,
	Accounts\Constants::MESSAGE_BUS_ROLE_DOCUMENT_UPDATED_ROUTING_KEY,
	Accounts\Constants::MESSAGE_BUS_ROLE_DOCUMENT_DELETED_ROUTING_KEY,
])]
final readonly class Role implements MetadataDocuments\Document
{

	public function __construct(
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private Uuid\UuidInterface $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $name,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $comment = null,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $anonymous = false,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $authenticated = false,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $administrator = false,
		#[ObjectMapper\Rules\AnyOf([
			new ApplicationObjectMapper\Rules\UuidValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private Uuid\UuidInterface|null $parent = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getComment(): string|null
	{
		return $this->comment;
	}

	public function isAnonymous(): bool
	{
		return $this->anonymous;
	}

	public function isAuthenticated(): bool
	{
		return $this->authenticated;
	}

	public function isAdministrator(): bool
	{
		return $this->administrator;
	}

	public function getParent(): Uuid\UuidInterface|null
	{
		return $this->parent;
	}

	public function getSource(): MetadataTypes\Sources\Source
	{
		return MetadataTypes\Sources\Module::ACCOUNTS;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'source' => $this->getSource()->value,
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'anonymous' => $this->isAnonymous(),
			'authenticated' => $this->isAuthenticated(),
			'administrator' => $this->isAdministrator(),
			'parent' => $this->getParent(),
		];
	}

}
