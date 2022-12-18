<?php declare(strict_types = 1);

/**
 * SmsNotification.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\TriggersModule;

use FastyBird\Library\Metadata\Exceptions;
use IPub\Phone\Entities as PhoneEntities;
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

	private PhoneEntities\Phone|null $phone = null;

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getPhone(): PhoneEntities\Phone
	{
		if ($this->phone === null) {
			throw new Exceptions\InvalidState('Entity was not properly created');
		}

		return $this->phone;
	}

	public function setPhone(PhoneEntities\Phone $phone): void
	{
		$this->phone = $phone;
	}

	/**
	 * @throws Exceptions\InvalidState
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
	 * @throws Exceptions\InvalidState
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
