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

namespace FastyBird\Module\Triggers\Entities\Conditions;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Ramsey\Uuid;
use function array_merge;
use function assert;
use const DATE_ATOM;

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
class DateCondition extends Condition
{

	/**
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="datetime", name="condition_date", nullable=true)
	 */
	private DateTimeInterface|null $date;

	public function __construct(
		DateTimeInterface $date,
		Entities\Triggers\AutomaticTrigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($trigger, $id);

		$this->date = $date;
	}

	public function getType(): MetadataTypes\TriggerConditionType
	{
		return MetadataTypes\TriggerConditionType::get(MetadataTypes\TriggerConditionType::TYPE_DATE);
	}

	public function getDate(): DateTimeInterface
	{
		assert($this->date instanceof DateTimeInterface);

		return $this->date;
	}

	public function setDate(DateTimeInterface $date): void
	{
		$this->date = $date;
	}

	public function validate(DateTimeInterface $date): bool
	{
		assert($this->date instanceof DateTimeInterface);

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

}
