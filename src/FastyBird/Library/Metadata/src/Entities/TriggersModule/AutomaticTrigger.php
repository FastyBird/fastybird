<?php declare(strict_types = 1);

/**
 * AutomaticTrigger.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\TriggersModule;

use function array_merge;

/**
 * Automatic  trigger entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AutomaticTrigger extends Trigger
{

	private bool|null $fulfilled;

	public function __construct(
		string $id,
		string $type,
		string $name,
		bool $enabled,
		string|null $comment = null,
		bool|null $isTriggered = null,
		bool|null $isFulfilled = null,
		string|null $owner = null,
	)
	{
		parent::__construct($id, $type, $name, $enabled, $comment, $isTriggered, $owner);

		$this->fulfilled = $isFulfilled;
	}

	public function isFulfilled(): bool|null
	{
		return $this->fulfilled;
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
