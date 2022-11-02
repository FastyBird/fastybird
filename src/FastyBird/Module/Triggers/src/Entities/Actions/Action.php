<?php declare(strict_types = 1);

/**
 * Action.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\TriggersModule\Entities\Actions;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\TriggersModule\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Ramsey\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_triggers_module_actions",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Triggers actions"
 *     }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="action_type", type="string", length=40)
 * @ORM\DiscriminatorMap({
 *    "device-property"  = "FastyBird\TriggersModule\Entities\Actions\DevicePropertyAction",
 *    "channel-property" = "FastyBird\TriggersModule\Entities\Actions\ChannelPropertyAction"
 * })
 * @ORM\MappedSuperclass
 */
abstract class Action implements IAction
{

	use Entities\TEntity;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	/**
	 * @var Uuid\UuidInterface
	 *
	 * @ORM\Id
	 * @ORM\Column(type="uuid_binary", name="action_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @var bool
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="boolean", name="action_enabled", length=1, nullable=false, options={"default": true})
	 */
	protected bool $enabled = true;

	/**
	 * @var Entities\Triggers\ITrigger
	 *
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\ManyToOne(targetEntity="FastyBird\TriggersModule\Entities\Triggers\Trigger", inversedBy="actions")
	 * @ORM\JoinColumn(name="trigger_id", referencedColumnName="trigger_id", onDelete="CASCADE")
	 */
	protected Entities\Triggers\ITrigger $trigger;

	/**
	 * @param Entities\Triggers\ITrigger $trigger
	 * @param Uuid\UuidInterface|null $id
	 */
	public function __construct(
		Entities\Triggers\ITrigger $trigger,
		?Uuid\UuidInterface $id = null
	) {
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->trigger = $trigger;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id'      => $this->getPlainId(),
			'type'    => $this->getType()->getValue(),
			'enabled' => $this->isEnabled(),

			'trigger' => $this->getTrigger()->getPlainId(),

			'owner' => $this->getTrigger()->getOwnerId(),
		];
	}

	/**
	 * {@inheritDoc}
	 */
	abstract public function getType(): MetadataTypes\TriggerActionTypeType;

	/**
	 * {@inheritDoc}
	 */
	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setEnabled(bool $enabled): void
	{
		$this->enabled = $enabled;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getTrigger(): Entities\Triggers\ITrigger
	{
		return $this->trigger;
	}

}
