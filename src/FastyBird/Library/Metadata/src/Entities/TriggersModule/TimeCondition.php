<?php declare(strict_types = 1);

/**
 * TimeCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\TriggersModule;

use DateTimeInterface;
use FastyBird\Library\Metadata\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_merge;

/**
 * Time condition entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TimeCondition extends Condition
{

	/**
	 * @param array<int> $days
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $trigger,
		Types\TriggerConditionType $type,
		bool $enabled,
		#[ObjectMapper\Rules\DateTimeValue(format: DateTimeInterface::ATOM)]
		private readonly DateTimeInterface $time,
		#[ObjectMapper\Rules\ArrayOf(
			item: new ObjectMapper\Rules\IntValue(min: 1, max: 7, unsigned: true),
			minItems: 1,
			maxItems: 7,
		)]
		private readonly array $days,
		bool|null $isFulfilled = null,
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $type, $enabled, $isFulfilled, $owner);
	}

	public function getTime(): DateTimeInterface
	{
		return $this->time;
	}

	/**
	 * @return array<int>
	 */
	public function getDays(): array
	{
		return $this->days;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'time' => $this->getTime()->format(DateTimeInterface::ATOM),
			'days' => $this->getDays(),
		]);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
