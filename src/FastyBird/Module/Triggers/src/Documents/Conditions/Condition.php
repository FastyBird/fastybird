<?php declare(strict_types = 1);

/**
 * Condition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Module\Triggers\Documents\Conditions;

use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Exchange\Documents\Mapping as EXCHANGE;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Condition document
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[DOC\Document(entity: Entities\Conditions\Condition::class)]
#[DOC\InheritanceType('SINGLE_TABLE')]
#[DOC\DiscriminatorColumn(name: 'type', type: 'string')]
#[DOC\MappedSuperclass]
#[EXCHANGE\RoutingMap([
	Triggers\Constants::MESSAGE_BUS_CONDITION_DOCUMENT_REPORTED_ROUTING_KEY,
	Triggers\Constants::MESSAGE_BUS_CONDITION_DOCUMENT_CREATED_ROUTING_KEY,
	Triggers\Constants::MESSAGE_BUS_CONDITION_DOCUMENT_UPDATED_ROUTING_KEY,
	Triggers\Constants::MESSAGE_BUS_CONDITION_DOCUMENT_DELETED_ROUTING_KEY,
])]
abstract class Condition implements MetadataDocuments\Document, MetadataDocuments\Owner
{

	use MetadataDocuments\TOwner;

	public function __construct(
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $trigger,
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $enabled,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('is_fulfilled')]
		private readonly bool|null $isFulfilled = null,
		#[ObjectMapper\Rules\AnyOf([
			new ApplicationObjectMapper\Rules\UuidValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		protected readonly Uuid\UuidInterface|null $owner = null,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getTrigger(): Uuid\UuidInterface
	{
		return $this->trigger;
	}

	abstract public static function getType(): string;

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function isFulfilled(): bool|null
	{
		return $this->isFulfilled;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'trigger' => $this->getTrigger()->toString(),
			'type' => static::getType(),
			'enabled' => $this->isEnabled(),
			'is_fulfilled' => $this->isFulfilled(),
			'owner' => $this->getOwner()?->toString(),
		];
	}

}
