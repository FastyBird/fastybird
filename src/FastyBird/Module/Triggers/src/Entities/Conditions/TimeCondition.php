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

namespace FastyBird\Module\Triggers\Entities\Conditions;

use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Nette\Utils;
use Ramsey\Uuid;
use function array_merge;
use function assert;
use function in_array;
use function intval;
use function is_array;
use function method_exists;
use const DATE_ATOM;

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
class TimeCondition extends Condition
{

	/**
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="time", name="condition_time", nullable=true)
	 */
	private DateTimeInterface|null $time;

	/**
	 * @var Array<int>|null
	 *
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="simple_array", name="condition_days", nullable=true)
	 */
	private array|null $days;

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		DateTimeInterface $time,
		Utils\ArrayHash $days,
		Entities\Triggers\AutomaticTrigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($trigger, $id);

		$this->setTime($time);
		$this->setDays($days);
	}

	public function getType(): MetadataTypes\TriggerConditionType
	{
		return MetadataTypes\TriggerConditionType::get(MetadataTypes\TriggerConditionType::TYPE_TIME);
	}

	public function getDays(): Utils\ArrayHash
	{
		assert(is_array($this->days));

		$days = [];

		foreach ($this->days as $day) {
			$days[] = intval($day);
		}

		return Utils\ArrayHash::from($days);
	}

	/**
	 * @param Array<int> $days
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public function setDays(Utils\ArrayHash|array $days): void
	{
		foreach ($days as $day) {
			if (!in_array($day, [1, 2, 3, 4, 5, 6, 7], true)) {
				throw new Exceptions\InvalidArgument('Provided days array is not valid.');
			}
		}

		$this->days = (array) $days;
	}

	public function getTime(): DateTimeInterface
	{
		assert($this->time instanceof DateTimeInterface);

		return $this->time;
	}

	public function setTime(DateTimeInterface $time): void
	{
		if (method_exists($time, 'setTimezone')) {
			$time->setTimezone(new DateTimeZone('UTC'));
		}

		$this->time = $time;
	}

	public function validate(DateTimeInterface $date): bool
	{
		if (in_array((int) $date->format('N'), (array) $this->getDays(), true) === false) {
			return false;
		}

		return $date->format('h:i:s') === $this->getTime()->format('h:i:s');
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
