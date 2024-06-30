<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           04.06.22
 */

namespace FastyBird\Module\Devices\Documents\Channels;

use DateTimeInterface;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Exchange\Documents\Mapping as EXCHANGE;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;

/**
 * Channel document
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[DOC\Document(entity: Entities\Channels\Channel::class)]
#[DOC\InheritanceType('SINGLE_TABLE')]
#[DOC\DiscriminatorColumn(name: 'type', type: 'string')]
#[DOC\MappedSuperclass]
#[EXCHANGE\RoutingMap([
	Devices\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_REPORTED_ROUTING_KEY,
	Devices\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_CREATED_ROUTING_KEY,
	Devices\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_UPDATED_ROUTING_KEY,
	Devices\Constants::MESSAGE_BUS_CHANNEL_DOCUMENT_DELETED_ROUTING_KEY,
])]
abstract class Channel implements Documents\Document, MetadataDocuments\Owner, MetadataDocuments\CreatedAt, MetadataDocuments\UpdatedAt
{

	use MetadataDocuments\TOwner;
	use MetadataDocuments\TCreatedAt;
	use MetadataDocuments\TUpdatedAt;

	/**
	 * @param array<Uuid\UuidInterface> $properties
	 * @param array<Uuid\UuidInterface> $controls
	 */
	public function __construct(
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BackedEnumValue(class: Types\ChannelCategory::class),
			new ObjectMapper\Rules\InstanceOfValue(type: Types\ChannelCategory::class),
		])]
		private readonly Types\ChannelCategory $category,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $identifier,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $device,
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
		#[ObjectMapper\Rules\ArrayOf(
			new ApplicationObjectMapper\Rules\UuidValue(),
		)]
		private readonly array $properties = [],
		#[ObjectMapper\Rules\ArrayOf(
			new ApplicationObjectMapper\Rules\UuidValue(),
		)]
		private readonly array $controls = [],
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
		protected readonly DateTimeInterface|null $createdAt = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('updated_at')]
		protected readonly DateTimeInterface|null $updatedAt = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	abstract public static function getType(): string;

	public function getCategory(): Types\ChannelCategory
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

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
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

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'type' => static::getType(),
			'source' => $this->getSource()->value,
			'category' => $this->getCategory()->value,
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'device' => $this->getDevice()->toString(),
			'properties' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getProperties(),
			),
			'controls' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getControls(),
			),
			'owner' => $this->getOwner()?->toString(),
			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

	public function getSource(): MetadataTypes\Sources\Source
	{
		return MetadataTypes\Sources\Module::DEVICES;
	}

}
