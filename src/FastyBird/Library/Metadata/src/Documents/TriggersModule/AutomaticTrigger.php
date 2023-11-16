<?php declare(strict_types = 1);

/**
 * AutomaticTrigger.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Documents\TriggersModule;

use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Automatic  trigger document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AutomaticTrigger extends Trigger
{

	public function __construct(
		Uuid\UuidInterface $id,
		Types\TriggerType $type,
		string $name,
		string|null $comment = null,
		bool $enabled = false,
		bool|null $isTriggered = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('is_fulfilled')]
		private readonly bool|null $isFulfilled = null,
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $type, $name, $comment, $enabled, $isTriggered, $owner);
	}

	public function isFulfilled(): bool|null
	{
		return $this->isFulfilled;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'is_fulfilled' => $this->isFulfilled(),
		]);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
