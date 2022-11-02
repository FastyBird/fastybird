<?php declare(strict_types = 1);

/**
 * DateCondition.php
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

namespace FastyBird\TriggersModule\Entities\Conditions;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\TriggersModule\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Ramsey\Uuid;
use Throwable;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_triggers_module_conditions_date",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Date conditions"
 *     }
 * )
 */
class DateCondition extends Condition implements IDateCondition
{

	/**
	 * @var DateTimeInterface
	 *
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="datetime", name="condition_date", nullable=true)
	 */
	private DateTimeInterface $date;

	/**
	 * @param DateTimeInterface $date
	 * @param Entities\Triggers\IAutomaticTrigger $trigger
	 * @param Uuid\UuidInterface|null $id
	 *
	 * @throws Throwable
	 */
	public function __construct(
		DateTimeInterface $date,
		Entities\Triggers\IAutomaticTrigger $trigger,
		?Uuid\UuidInterface $id = null
	) {
		parent::__construct($trigger, $id);

		$this->date = $date;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getType(): MetadataTypes\TriggerConditionTypeType
	{
		return MetadataTypes\TriggerConditionTypeType::get(MetadataTypes\TriggerConditionTypeType::TYPE_DATE);
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate(DateTimeInterface $date): bool
	{
		return $date->getTimestamp() === $this->date->getTimestamp();
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'date' => $this->getDate()->format(DATE_ATOM),
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDate(): DateTimeInterface
	{
		return $this->date;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setDate(DateTimeInterface $date): void
	{
		$this->date = $date;
	}

}
