<?php declare(strict_types = 1);

/**
 * TimeCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Metadata\Entities\TriggersModule;

use DateTimeInterface;
use FastyBird\Metadata\Exceptions;
use Nette\Utils;
use function array_merge;

/**
 * Time condition entity
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TimeCondition extends Condition
{

	private DateTimeInterface $time;

	/**
	 * @param array<int> $days
	 */
	public function __construct(
		string $id,
		string $trigger,
		string $type,
		bool $enabled,
		string $time,
		private readonly array $days,
		bool|null $isFulfilled = null,
		string|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $type, $enabled, $isFulfilled, $owner);

		$time = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, $time);

		if ($time instanceof DateTimeInterface) {
			$this->time = $time;

		} else {
			throw new Exceptions\InvalidArgument('Provided time attribute is not valid');
		}
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
	 * @return Array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}

}
