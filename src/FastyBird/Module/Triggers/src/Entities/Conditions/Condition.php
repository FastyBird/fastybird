<?php declare(strict_types = 1);

/**
 * Condition.php
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

namespace FastyBird\Module\Triggers\Entities\Conditions;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Triggers\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineDynamicDiscriminatorMap\Entities as DoctrineDynamicDiscriminatorMapEntities;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;
use function assert;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_triggers_module_conditions",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Triggers conditions"
 *     }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="condition_type", type="string", length=100)
 * @ORM\DiscriminatorMap({
 *    "condition" = "FastyBird\Module\Triggers\Entities\Conditions\Condition"
 * })
 * @ORM\MappedSuperclass
 */
abstract class Condition implements Entities\Entity,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated,
	DoctrineDynamicDiscriminatorMapEntities\IDiscriminatorProvider
{

	use Entities\TEntity;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="uuid_binary", name="condition_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="boolean", name="condition_enabled", length=1, nullable=false, options={"default": true})
	 */
	protected bool $enabled = true;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\ManyToOne(targetEntity="FastyBird\Module\Triggers\Entities\Triggers\AutomaticTrigger", inversedBy="conditions")
	 * @ORM\JoinColumn(name="trigger_id", referencedColumnName="trigger_id", onDelete="CASCADE")
	 */
	protected Entities\Triggers\AutomaticTrigger|null $trigger;

	public function __construct(
		Entities\Triggers\AutomaticTrigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		// @phpstan-ignore-next-line
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->trigger = $trigger;
	}

	abstract public function getType(): string;

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function setEnabled(bool $enabled): void
	{
		$this->enabled = $enabled;
	}

	public function getTrigger(): Entities\Triggers\AutomaticTrigger
	{
		assert($this->trigger instanceof Entities\Triggers\AutomaticTrigger);

		return $this->trigger;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getPlainId(),
			'type' => $this->getType(),
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
