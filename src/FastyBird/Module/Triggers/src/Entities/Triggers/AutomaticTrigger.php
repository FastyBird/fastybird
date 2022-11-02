<?php declare(strict_types = 1);

/**
 * AutomaticTrigger.php
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

namespace FastyBird\TriggersModule\Entities\Triggers;

use Doctrine\Common;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\TriggersModule\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Ramsey\Uuid;
use Throwable;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_triggers_module_triggers_automatic",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Automatic triggers"
 *     }
 * )
 */
class AutomaticTrigger extends Trigger implements IAutomaticTrigger
{

	/**
	 * @var Common\Collections\Collection<int, Entities\Conditions\ICondition>
	 *
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\OneToMany(targetEntity="FastyBird\TriggersModule\Entities\Conditions\Condition", mappedBy="trigger", cascade={"persist", "remove"}, orphanRemoval=true)
	 */
	private Common\Collections\Collection $conditions;

	/**
	 * @param string $name
	 * @param Uuid\UuidInterface|null $id
	 *
	 * @throws Throwable
	 */
	public function __construct(
		string $name,
		?Uuid\UuidInterface $id = null
	) {
		parent::__construct($name, $id);

		$this->conditions = new Common\Collections\ArrayCollection();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getType(): MetadataTypes\TriggerTypeType
	{
		return MetadataTypes\TriggerTypeType::get(MetadataTypes\TriggerTypeType::TYPE_AUTOMATIC);
	}

	/**
	 * {@inheritDoc}
	 */
	public function addCondition(Entities\Conditions\ICondition $condition): void
	{
		// Check if collection does not contain inserting entity
		if (!$this->conditions->contains($condition)) {
			// ...and assign it to collection
			$this->conditions->add($condition);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getCondition(string $id): ?Entities\Conditions\ICondition
	{
		$found = $this->conditions
			->filter(function (Entities\Conditions\ICondition $row) use ($id): bool {
				return $id === $row->getPlainId();
			});

		return $found->isEmpty() ? null : $found->first();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getConditions(): array
	{
		return $this->conditions->toArray();
	}

	/**
	 * {@inheritDoc}
	 */
	public function setConditions(array $conditions = []): void
	{
		$this->conditions = new Common\Collections\ArrayCollection();

		// Process all passed entities...
		/** @var Entities\Conditions\ICondition $entity */
		foreach ($conditions as $entity) {
			if (!$this->conditions->contains($entity)) {
				// ...and assign them to collection
				$this->conditions->add($entity);
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function removeCondition(Entities\Conditions\ICondition $condition): void
	{
		// Check if collection contain removing entity...
		if ($this->conditions->contains($condition)) {
			// ...and remove it from collection
			$this->conditions->removeElement($condition);
		}
	}

}
