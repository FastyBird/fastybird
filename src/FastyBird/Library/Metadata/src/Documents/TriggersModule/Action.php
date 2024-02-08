<?php declare(strict_types = 1);

/**
 * Action.php
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

namespace FastyBird\Library\Metadata\Documents\TriggersModule;

use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata\Documents;
use Orisai\ObjectMapper;
use Ramsey\Uuid;

/**
 * Action document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Action implements Documents\Document, Documents\Owner
{

	use Documents\TOwner;

	public function __construct(
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $id,
		#[ApplicationObjectMapper\Rules\UuidValue()]
		private readonly Uuid\UuidInterface $trigger,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $type,
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

	public function getType(): string
	{
		return $this->type;
	}

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
			'type' => $this->getType(),
			'enabled' => $this->isEnabled(),
			'is_triggered' => $this->isTriggered(),
			'owner' => $this->getOwner()?->toString(),
		];
	}

}
