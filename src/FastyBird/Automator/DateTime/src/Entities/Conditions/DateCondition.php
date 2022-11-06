<?php declare(strict_types = 1);

/**
 * DateCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DateTimeAutomator!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Automator\DateTime\Entities\Conditions;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Ramsey\Uuid;
use function array_merge;
use function assert;
use const DATE_ATOM;

/**
 * @ORM\Entity
 */
class DateCondition extends TriggersEntities\Conditions\Condition
{

	/**
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="datetime", name="condition_date", nullable=true)
	 */
	private DateTimeInterface|null $date;

	public function __construct(
		DateTimeInterface $date,
		TriggersEntities\Triggers\AutomaticTrigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($trigger, $id);

		$this->date = $date;
	}

	public function getType(): string
	{
		return 'date';
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

	public function getDiscriminatorName(): string
	{
		return $this->getType();
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
