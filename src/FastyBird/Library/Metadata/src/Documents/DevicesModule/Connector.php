<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           04.06.22
 */

namespace FastyBird\Library\Metadata\Documents\DevicesModule;

use DateTimeInterface;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;

/**
 * Connector document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector implements Documents\Document, Documents\Owner
{

	use Documents\TOwner;
	use Documents\TCreatedAt;
	use Documents\TUpdatedAt;

	/**
	 * @param array<Uuid\UuidInterface> $properties
	 * @param array<Uuid\UuidInterface> $controls
	 * @param array<Uuid\UuidInterface> $devices
	 */
	public function __construct(
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $type,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\ConnectorCategory::class),
			new ObjectMapper\Rules\InstanceOfValue(type: Types\ConnectorCategory::class),
		])]
		private readonly Types\ConnectorCategory $category,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $name = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $comment = null,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $enabled = false,
		#[ObjectMapper\Rules\ArrayOf(
			new ApplicationObjectMapper\Rules\UuidValue(),
		)]
		private readonly array $properties = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ApplicationObjectMapper\Rules\UuidValue(),
		)]
		private readonly array $controls = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ApplicationObjectMapper\Rules\UuidValue(),
		)]
		private readonly array $devices = [],
		#[ObjectMapper\Rules\AnyOf([
			new ApplicationObjectMapper\Rules\UuidValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		protected readonly Uuid\UuidInterface|null $owner = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('created_at')]
		private readonly DateTimeInterface|null $createdAt = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('updated_at')]
		private readonly DateTimeInterface|null $updatedAt = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getCategory(): Types\ConnectorCategory
	{
		return $this->category;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getComment(): string|null
	{
		return $this->comment;
	}

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getProperties(): array
	{
		return $this->properties;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getControls(): array
	{
		return $this->controls;
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getDevices(): array
	{
		return $this->devices;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'type' => $this->getType(),
			'category' => $this->getCategory()->value,
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'enabled' => $this->isEnabled(),
			'properties' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getProperties(),
			),
			'controls' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getControls(),
			),
			'devices' => array_map(static fn (Uuid\UuidInterface $id): string => $id->toString(), $this->getDevices()),
			'owner' => $this->getOwner()?->toString(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

}
