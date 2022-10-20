<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     States
 * @since          0.1.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Plugin\RedisDb\States;

use FastyBird\Plugin\RedisDb\Exceptions;
use Nette;
use Ramsey\Uuid;

/**
 * Base state
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class State
{

	use Nette\SmartObject;

	private Uuid\UuidInterface $id;

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function __construct(string $id, private readonly string $raw)
	{
		if (!Uuid\Uuid::isValid($id)) {
			throw new Exceptions\InvalidState('Provided state id is not valid');
		}

		$this->id = Uuid\Uuid::fromString($id);
	}

	/**
	 * @return Array<string>|Array<int, int|string|bool|null>
	 */
	public static function getCreateFields(): array
	{
		return [
			'id',
		];
	}

	/**
	 * @return Array<string>
	 */
	public static function getUpdateFields(): array
	{
		return [];
	}

	public function getRaw(): string
	{
		return $this->raw;
	}

	/**
	 * @return Array<string, mixed|null>
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
		];
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

}
