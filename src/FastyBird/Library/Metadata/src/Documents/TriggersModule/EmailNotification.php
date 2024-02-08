<?php declare(strict_types = 1);

/**
 * EmailNotification.php
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

use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Email notification document
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EmailNotification extends Notification
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		string $type,
		bool $enabled,
		#[ObjectMapper\Rules\StringValue(pattern: '/^[\w\-\.]+@[\w\-\.]+\.+[\w-]{2,63}$/', notEmpty: true)]
		private readonly string $email,
		Uuid\UuidInterface|null $owner = null,
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

}
