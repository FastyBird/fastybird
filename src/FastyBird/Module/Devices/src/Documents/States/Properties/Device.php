<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           23.01.24
 */

namespace FastyBird\Module\Devices\Documents\States\Properties;

use DateTimeInterface;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Exchange\Documents\Mapping as EXCHANGE;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Module\Devices;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function is_bool;

/**
 * Device property state document
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[DOC\Document]
#[EXCHANGE\RoutingMap([
	Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED_ROUTING_KEY,
	Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_CREATED_ROUTING_KEY,
	Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_UPDATED_ROUTING_KEY,
	Devices\Constants::MESSAGE_BUS_DEVICE_PROPERTY_STATE_DOCUMENT_DELETED_ROUTING_KEY,
])]
final class Device implements MetadataDocuments\Document, MetadataDocuments\CreatedAt, MetadataDocuments\UpdatedAt
{

	use MetadataDocuments\TCreatedAt;
	use MetadataDocuments\TUpdatedAt;

	public function __construct(
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $device,
		#[ObjectMapper\Rules\MappedObjectValue(class: Values::class)]
		private readonly Values $read,
		#[ObjectMapper\Rules\MappedObjectValue(class: Values::class)]
		private readonly Values $get,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM),
		])]
		private readonly bool|DateTimeInterface $pending = false,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $valid = false,
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

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getRead(): Values
	{
		return $this->read;
	}

	public function getGet(): Values
	{
		return $this->get;
	}

	public function getPending(): bool|DateTimeInterface
	{
		return $this->pending;
	}

	public function isPending(): bool
	{
		return is_bool($this->pending) ? $this->pending : true;
	}

	public function isValid(): bool
	{
		return $this->valid;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'device' => $this->getDevice()->toString(),
			'read' => $this->getRead()->toArray(),
			'get' => $this->getGet()->toArray(),
			'pending' => $this->getPending() instanceof DateTimeInterface
				? $this->getPending()->format(DateTimeInterface::ATOM)
				: $this->getPending(),
			'valid' => $this->isValid(),

			'created_at' => $this->getCreatedAt()?->format(DateTimeInterface::ATOM),
			'updated_at' => $this->getUpdatedAt()?->format(DateTimeInterface::ATOM),
		];
	}

}
