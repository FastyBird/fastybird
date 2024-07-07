<?php declare(strict_types = 1);

/**
 * Email.php
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

namespace FastyBird\Module\Accounts\Documents\Emails;

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
 * Email document
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[DOC\Document(entity: Entities\Emails\Email::class)]
#[EXCHANGE\RoutingMap([
	Accounts\Constants::MESSAGE_BUS_EMAIL_DOCUMENT_REPORTED_ROUTING_KEY,
	Accounts\Constants::MESSAGE_BUS_EMAIL_DOCUMENT_CREATED_ROUTING_KEY,
	Accounts\Constants::MESSAGE_BUS_EMAIL_DOCUMENT_UPDATED_ROUTING_KEY,
	Accounts\Constants::MESSAGE_BUS_EMAIL_DOCUMENT_DELETED_ROUTING_KEY,
])]
final readonly class Email implements MetadataDocuments\Document
{

	public function __construct(
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private Uuid\UuidInterface $id,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private Uuid\UuidInterface $account,
		#[ObjectMapper\Rules\StringValue(pattern: '/^[\w\-\.]+@[\w\-\.]+\.+[\w-]{2,63}$/', notEmpty: true)]
		private string $address,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $default = false,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $verified = false,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $private = false,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		private bool $public = false,
	)
	{
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

	public function getSource(): MetadataTypes\Sources\Source
	{
		return MetadataTypes\Sources\Module::ACCOUNTS;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'source' => $this->getSource()->value,
			'account' => $this->getAccount()->toString(),
			'address' => $this->getAddress(),
			'default' => $this->isDefault(),
			'verified' => $this->isVerified(),
			'private' => $this->isPrivate(),
			'public' => $this->isPublic(),
		];
	}

}
