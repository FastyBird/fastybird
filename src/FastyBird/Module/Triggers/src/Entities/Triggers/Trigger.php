<?php declare(strict_types = 1);

/**
 * Trigger.php
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

namespace FastyBird\Module\Triggers\Entities\Triggers;

use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Entities;
use FastyBird\SimpleAuth\Entities as SimpleAuthEntities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_triggers_module_triggers",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Actions triggers"
 *     }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="trigger_type", type="string", length=40)
 * @ORM\DiscriminatorMap({
 *    "automatic"  = "FastyBird\Module\Triggers\Entities\Triggers\AutomaticTrigger",
 *    "manual"     = "FastyBird\Module\Triggers\Entities\Triggers\ManualTrigger"
 * })
 * @ORM\MappedSuperclass
 */
abstract class Trigger implements Entities\Entity,
	Entities\EntityParams,
	SimpleAuthEntities\Owner,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use Entities\TEntityParams;
	use SimpleAuthEntities\TOwner;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="uuid_binary", name="trigger_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="string", name="trigger_name", length=100, nullable=false)
	 */
	protected string $name;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="text", name="trigger_comment", nullable=true, options={"default": null})
	 */
	protected string|null $comment = null;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="boolean", name="trigger_enabled", length=1, nullable=false, options={"default": true})
	 */
	protected bool $enabled = true;

	/**
	 * @var Common\Collections\Collection<int, Entities\Actions\Action>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\OneToMany(targetEntity="FastyBird\Module\Triggers\Entities\Actions\Action", mappedBy="trigger", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	protected Common\Collections\Collection $actions;

	/**
	 * @var Common\Collections\Collection<int, Entities\Notifications\Notification>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\OneToMany(targetEntity="FastyBird\Module\Triggers\Entities\Notifications\Notification", mappedBy="trigger", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	protected Common\Collections\Collection $notifications;

	/**
	 * @var Common\Collections\Collection<int, Entities\Triggers\Controls\Control>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\OneToMany(targetEntity="FastyBird\Module\Triggers\Entities\Triggers\Controls\Control", mappedBy="trigger", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private Common\Collections\Collection $controls;

	public function __construct(string $name, Uuid\UuidInterface|null $id = null)
	{
		// @phpstan-ignore-next-line
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->setName($name);

		$this->actions = new Common\Collections\ArrayCollection();
		$this->notifications = new Common\Collections\ArrayCollection();
		$this->controls = new Common\Collections\ArrayCollection();
	}

	/**
	 * @return array<Entities\Actions\Action>
	 */
	public function getActions(): array
	{
		return $this->actions->toArray();
	}

	/**
	 * @param array<Entities\Actions\Action> $actions
	 */
	public function setActions(array $actions = []): void
	{
		$this->actions = new Common\Collections\ArrayCollection();

		foreach ($actions as $entity) {
			$this->addAction($entity);
		}
	}

	public function addAction(Entities\Actions\Action $action): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->actions->contains($action)) {
			// ...and assign it to collection
			$this->actions->add($action);
		}
	}

	public function getAction(string $id): Entities\Actions\Action|null
	{
		$found = $this->actions
			->filter(static fn (Entities\Actions\Action $row): bool => $id === $row->getPlainId());

		return $found->isEmpty() ? null : $found->first();
	}

	public function removeAction(Entities\Actions\Action $action): void
	{
		// Check if collection contain removing entity...
		if ($this->actions->contains($action)) {
			// ...and remove it from collection
			$this->actions->removeElement($action);
		}
	}

	/**
	 * @return array<Entities\Notifications\Notification>
	 */
	public function getNotifications(): array
	{
		return $this->notifications->toArray();
	}

	/**
	 * @param array<Entities\Notifications\Notification> $notifications
	 */
	public function setNotifications(array $notifications = []): void
	{
		$this->notifications = new Common\Collections\ArrayCollection();

		foreach ($notifications as $entity) {
			$this->addNotification($entity);
		}
	}

	public function addNotification(Entities\Notifications\Notification $notification): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->notifications->contains($notification)) {
			// ...and assign it to collection
			$this->notifications->add($notification);
		}
	}

	public function getNotification(string $id): Entities\Notifications\Notification|null
	{
		$found = $this->notifications
			->filter(static fn (Entities\Notifications\Notification $row): bool => $id === $row->getPlainId());

		return $found->isEmpty() ? null : $found->first();
	}

	public function removeNotification(Entities\Notifications\Notification $notification): void
	{
		// Check if collection contain removing entity...
		if ($this->notifications->contains($notification)) {
			// ...and remove it from collection
			$this->notifications->removeElement($notification);
		}
	}

	/**
	 * @return array<Entities\Triggers\Controls\Control>
	 */
	public function getControls(): array
	{
		return $this->controls->toArray();
	}

	/**
	 * @param array<Entities\Triggers\Controls\Control> $controls
	 */
	public function setControls(array $controls = []): void
	{
		$this->controls = new Common\Collections\ArrayCollection();

		foreach ($controls as $entity) {
			$this->addControl($entity);
		}
	}

	public function addControl(Entities\Triggers\Controls\Control $control): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->controls->contains($control)) {
			// ...and assign it to collection
			$this->controls->add($control);
		}
	}

	public function getControl(string $name): Entities\Triggers\Controls\Control|null
	{
		$found = $this->controls
			->filter(static fn (Entities\Triggers\Controls\Control $row): bool => $name === $row->getName());

		return $found->isEmpty() ? null : $found->first();
	}

	public function removeControl(Entities\Triggers\Controls\Control $control): void
	{
		// Check if collection contain removing entity...
		if ($this->controls->contains($control)) {
			// ...and remove it from collection
			$this->controls->removeElement($control);
		}
	}

	public function hasControl(string $name): bool
	{
		return $this->findControl($name) !== null;
	}

	public function findControl(string $name): Entities\Triggers\Controls\Control|null
	{
		$found = $this->controls
			->filter(static fn (Entities\Triggers\Controls\Control $row): bool => $name === $row->getName());

		return $found->isEmpty() ? null : $found->first();
	}

	abstract public function getType(): MetadataTypes\TriggerType;

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getComment(): string|null
	{
		return $this->comment;
	}

	public function setComment(string|null $comment = null): void
	{
		$this->comment = $comment;
	}

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function setEnabled(bool $enabled): void
	{
		$this->enabled = $enabled;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getPlainId(),
			'type' => $this->getType()->getValue(),
			'name' => $this->getName(),
			'comment' => $this->getComment(),
			'enabled' => $this->isEnabled(),

			'owner' => $this->getOwnerId(),
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
