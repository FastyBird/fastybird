<?php declare(strict_types = 1);

/**
 * SmsNotification.php
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

use FastyBird\Library\Metadata\Types;
use IPub\Phone\Entities as PhoneEntities;
use IPub\Phone\Exceptions as PhoneExceptions;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * SMS notification entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SmsNotification extends Notification
{

	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		Types\TriggerNotificationType $type,
		bool $enabled,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $phone,
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $type, $enabled, $owner);
	}

	/**
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 */
	public function getPhone(): PhoneEntities\Phone
	{
		return PhoneEntities\Phone::fromNumber($this->phone);
	}

	/**
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'phone' => $this->getPhone()->getInternationalNumber(),
		]);
	}

	/**
	 * @return array<string, mixed>
	 *
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
