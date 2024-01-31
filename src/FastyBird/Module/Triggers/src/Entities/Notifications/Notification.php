<?php declare(strict_types = 1);

/**
 * Notification.php
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
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;
use function assert;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_triggers_module_notifications",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Triggers notifications"
 *     }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="notification_type", type="string", length=100)
 * @ORM\DiscriminatorMap({
 *    "sms"     = "FastyBird\Module\Triggers\Entities\Notifications\SmsNotification",
 *    "email"   = "FastyBird\Module\Triggers\Entities\Notifications\EmailNotification",
 * })
 * @ORM\MappedSuperclass
 */
abstract class Notification implements Entities\Entity,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="uuid_binary", name="notification_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="boolean", name="notification_enabled", length=1, nullable=false, options={"default": true})
	 */
	protected bool $enabled = true;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\ManyToOne(targetEntity="FastyBird\Module\Triggers\Entities\Triggers\Trigger", inversedBy="notifications")
	 * @ORM\JoinColumn(name="trigger_id", referencedColumnName="trigger_id", onDelete="CASCADE")
	 */
	protected Entities\Triggers\Trigger|null $trigger;

	public function __construct(
		Entities\Triggers\Trigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		// @phpstan-ignore-next-line
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->trigger = $trigger;
	}

	abstract public function getType(): MetadataTypes\TriggerNotificationType;

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function setEnabled(bool $enabled): void
	{
		$this->enabled = $enabled;
	}

	public function getTrigger(): Entities\Triggers\Trigger
	{
		assert($this->trigger instanceof Entities\Triggers\Trigger);

		return $this->trigger;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getPlainId(),
			'type' => $this->getType()->getValue(),
			'enabled' => $this->isEnabled(),

			'trigger' => $this->getTrigger()->getPlainId(),

			'owner' => $this->getTrigger()->getOwnerId(),
		];
	}

	/**
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
