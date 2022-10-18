<?php declare(strict_types = 1);

/**
 * DateCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Library\Metadata\Entities\TriggersModule;

use DateTimeInterface;
use FastyBird\Library\Metadata\Exceptions;
use Nette\Utils;
use function array_merge;

/**
 * Date condition entity
 *
 * @package        FastyBird:MetadataLibrary!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DateCondition extends Condition
{

	private DateTimeInterface $date;

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(
		string $id,
		string $trigger,
		string $type,
		bool $enabled,
		string $date,
		bool|null $isFulfilled = null,
		string|null $owner = null,
	)
	{
		parent::__construct($id, $trigger, $type, $enabled, $isFulfilled, $owner);

		$date = Utils\DateTime::createFromFormat(DateTimeInterface::ATOM, $date);

		if ($date instanceof DateTimeInterface) {
			$this->date = $date;

		} else {
			throw new Exceptions\InvalidArgument('Provided time attribute is not valid');
		}
	}

	public function getDate(): DateTimeInterface
	{
		return $this->date;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'date' => $this->getDate()->format(DateTimeInterface::ATOM),
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
