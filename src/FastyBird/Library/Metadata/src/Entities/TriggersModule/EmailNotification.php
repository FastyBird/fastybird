<?php declare(strict_types = 1);

/**
 * EmailNotification.php
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
 * Email notification entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EmailNotification extends Notification
{

	public function __construct(
		string $id,
		string $trigger,
		string $type,
		bool $enabled,
		private readonly string $email,
		string|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $type, $enabled, $owner);
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'email' => $this->getEmail(),
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
