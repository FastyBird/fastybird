<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     States
 * @since          0.1.0
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
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class State implements IState
{

	use Nette\SmartObject;

	private Uuid\UuidInterface $id;

	public function __construct(string $id, private PHPOnCouch\CouchDocument $document)
	{
		if (!Uuid\Uuid::isValid($id)) {
			throw new Exceptions\InvalidState('Provided state id is not valid');
		}

		$this->id = Uuid\Uuid::fromString($id);
	}

	public function getDocument(): PHPOnCouch\CouchDocument
	{
		return $this->document;
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getId()->toString(),
		];
	}

}
