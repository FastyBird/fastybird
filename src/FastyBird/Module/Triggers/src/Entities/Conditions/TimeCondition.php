<?php declare(strict_types = 1);

/**
 * TimeCondition.php
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
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\TriggersModule\Entities;
use FastyBird\TriggersModule\Exceptions;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Nette\Utils;
use Ramsey\Uuid;
use Throwable;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_triggers_module_conditions_time",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Time conditions"
 *     }
 * )
 */
class TimeCondition extends Condition implements ITimeCondition
{

	/**
	 * @var DateTimeInterface
	 *
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="time", name="condition_time", nullable=true)
	 */
	private DateTimeInterface $time;

	/**
	 * @var int[]|mixed[]
	 *
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="simple_array", name="condition_days", nullable=true)
	 */
	private array $days;

	/**
	 * @param DateTimeInterface $time
	 * @param Utils\ArrayHash $days
	 * @param Entities\Triggers\IAutomaticTrigger $trigger
	 * @param Uuid\UuidInterface|null $id
	 *
	 * @throws Throwable
	 */
	public function __construct(
		DateTimeInterface $time,
		Utils\ArrayHash $days,
		Entities\Triggers\IAutomaticTrigger $trigger,
		?Uuid\UuidInterface $id = null
	) {
		parent::__construct($trigger, $id);

		$this->setTime($time);
		$this->setDays($days);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getType(): MetadataTypes\TriggerConditionTypeType
	{
		return MetadataTypes\TriggerConditionTypeType::get(MetadataTypes\TriggerConditionTypeType::TYPE_TIME);
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate(DateTimeInterface $date): bool
	{
		if (in_array((int) $date->format('N'), (array) $this->getDays()) === false) {
			return false;
		}

		return $date->format('h:i:s') === $this->getTime()->format('h:i:s');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDays(): Utils\ArrayHash
	{
		$days = [];

		foreach ($this->days as $day) {
			$days[] = (int) $day;
		}

		return Utils\ArrayHash::from($days);
	}

	/**
	 * {@inheritDoc}
	 */
	public function setDays($days): void
	{
		if (!is_array($days) && !$days instanceof Utils\ArrayHash) {
			throw new Exceptions\InvalidArgumentException('Provided days have to be valid array.');
		}

		foreach ($days as $day) {
			if (!in_array($day, [1, 2, 3, 4, 5, 6, 7], true)) {
				throw new Exceptions\InvalidArgumentException('Provided days array is not valid.');
			}
		}

		$this->days = (array) $days;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getTime(): DateTimeInterface
	{
		return $this->time;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setTime(DateTimeInterface $time): void
	{
		if (method_exists($time, 'setTimezone')) {
			$time->setTimezone(new DateTimeZone('UTC'));
		}

		$this->time = $time;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'time' => $this->getTime()->format(DATE_ATOM),
			'days' => (array) $this->getDays(),
		]);
	}

}
