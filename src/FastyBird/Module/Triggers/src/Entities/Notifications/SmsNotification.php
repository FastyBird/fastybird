<?php declare(strict_types = 1);

/**
 * SmsNotification.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Entities\Notifications;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\Phone;
use Ramsey\Uuid;
use function array_merge;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_triggers_module_notifications_sms",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="SMS notifications"
 *     }
 * )
 */
class SmsNotification extends Notification
{

	/**
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="phone", name="notification_phone", length=150, nullable=true)
	 */
	private Phone\Entities\Phone $phone;

	public function __construct(
		Phone\Entities\Phone $phone,
		Entities\Triggers\Trigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($trigger, $id);

		$this->phone = $phone;
	}

	public function getType(): MetadataTypes\TriggerNotificationType
	{
		return MetadataTypes\TriggerNotificationType::get(MetadataTypes\TriggerNotificationType::TYPE_SMS);
	}

	public function getPhone(): Phone\Entities\Phone
	{
		return $this->phone;
	}

	public function setPhone(Phone\Entities\Phone $phone): void
	{
		$this->phone = $phone;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'phone' => $this->getPhone()->getInternationalNumber(),
		]);
	}

}
