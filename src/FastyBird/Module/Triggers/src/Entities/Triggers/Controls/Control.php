<?php declare(strict_types = 1);

/**
 * Control.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Entities
 * @since          0.4.0
 *
 * @date           01.10.21
 */

namespace FastyBird\TriggersModule\Entities\Triggers\Controls;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\TriggersModule\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Ramsey\Uuid;
use Throwable;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_triggers_module_triggers_controls",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Triggers controls"
 *     },
 *     uniqueConstraints={
 *       @ORM\UniqueConstraint(name="trigger_control_unique", columns={"control_name", "trigger_id"})
 *     },
 *     indexes={
 *       @ORM\Index(name="control_name_idx", columns={"control_name"})
 *     }
 * )
 */
class Control implements IControl
{

	use Entities\TEntity;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	/**
	 * @var Uuid\UuidInterface
	 *
	 * @ORM\Id
	 * @ORM\Column(type="uuid_binary", name="control_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @var string
	 *
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="string", name="control_name", length=100, nullable=false)
	 */
	private string $name;

	/**
	 * @var Entities\Triggers\ITrigger
	 *
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\ManyToOne(targetEntity="FastyBird\TriggersModule\Entities\Triggers\Trigger", inversedBy="controls")
	 * @ORM\JoinColumn(name="trigger_id", referencedColumnName="trigger_id", onDelete="CASCADE", nullable=false)
	 */
	private Entities\Triggers\ITrigger $trigger;

	/**
	 * @param string $name
	 * @param Entities\Triggers\ITrigger $trigger
	 *
	 * @throws Throwable
	 */
	public function __construct(string $name, Entities\Triggers\ITrigger $trigger)
	{
		$this->id = Uuid\Uuid::uuid4();

		$this->name = $name;
		$this->trigger = $trigger;

		$trigger->addControl($this);
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id'      => $this->getPlainId(),
			'name'    => $this->getName(),
			'trigger' => $this->getTrigger()->getPlainId(),
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getTrigger(): Entities\Triggers\ITrigger
	{
		return $this->trigger;
	}

}
