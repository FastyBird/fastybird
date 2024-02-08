<?php declare(strict_types = 1);

/**
 * Identity.php
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

use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Identity entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Identity implements Documents\Document
{

	public function __construct(
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $account,
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\IdentityState::class)]
		private readonly Types\IdentityState $state,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $uid,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $hash = null,
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
			'state' => $this->getState()->value,
			'uid' => $this->getUid(),
			'hash' => $this->getHash(),
		];
	}

}
