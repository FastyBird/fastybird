<?php declare(strict_types = 1);

/**
 * Action.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           01.06.22
 */

namespace FastyBird\Module\Triggers\Documents\Actions;

use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Exchange\Documents\Mapping as EXCHANGE;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Action document
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[DOC\Document(entity: Entities\Actions\Action::class)]
#[DOC\InheritanceType('SINGLE_TABLE')]
#[DOC\DiscriminatorColumn(name: 'type', type: 'string')]
#[DOC\MappedSuperclass]
#[EXCHANGE\RoutingMap([
	Triggers\Constants::MESSAGE_BUS_ACTION_DOCUMENT_REPORTED_ROUTING_KEY,
	Triggers\Constants::MESSAGE_BUS_ACTION_DOCUMENT_CREATED_ROUTING_KEY,
	Triggers\Constants::MESSAGE_BUS_ACTION_DOCUMENT_UPDATED_ROUTING_KEY,
	Triggers\Constants::MESSAGE_BUS_ACTION_DOCUMENT_DELETED_ROUTING_KEY,
])]
abstract class Action implements MetadataDocuments\Document, MetadataDocuments\Owner
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
		#[ObjectMapper\Modifiers\FieldName('is_triggered')]
		private readonly bool|null $isTriggered = null,
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

	public function isTriggered(): bool|null
	{
		return $this->isTriggered;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
			'trigger' => $this->getTrigger()->toString(),
			'type' => static::getType(),
			'enabled' => $this->isEnabled(),
			'is_triggered' => $this->isTriggered(),
			'owner' => $this->getOwner()?->toString(),
		];
	}

}
