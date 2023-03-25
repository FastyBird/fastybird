<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     States
 * @since          1.0.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Plugin\CouchDb\States;

use FastyBird\Plugin\CouchDb\Exceptions;
use Nette;
use PHPOnCouch;
use Ramsey\Uuid;

/**
 * Base state
 *
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class State
{

	use Nette\SmartObject;

	public const CREATED_AT_FIELD = 'createdAt';

	public const UPDATED_AT_FIELD = 'updatedAt';

	private Uuid\UuidInterface $id;

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function __construct(string $id, private readonly PHPOnCouch\CouchDocument $document)
	{
		if (!Uuid\Uuid::isValid($id)) {
			throw new Exceptions\InvalidState('Provided state id is not valid');
		}

		$this->id = Uuid\Uuid::fromString($id);
	}

	/**
	 * @return array<string>|array<int, int|string|bool|null>
	 */
	public static function getCreateFields(): array
	{
		return [
			'id',
		];
	}

	/**
	 * @return array<string>
	 */
	public static function getUpdateFields(): array
	{
		return [];
	}

	public function getDocument(): PHPOnCouch\CouchDocument
	{
		return $this->document;
	}

	/**
	 * @return array<string, mixed|null>
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
