<?php declare(strict_types = 1);

/**
 * IStatesManager.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           03.03.20
 */

namespace FastyBird\Plugin\CouchDb\Models;

use FastyBird\Plugin\CouchDb\States;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Base states manager interface
 *
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IStatesManager
{

	public function create(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
		string $class = States\State::class,
	): States\IState;

	public function update(
		States\IState $state,
		Utils\ArrayHash $values,
	): States\IState;

	public function delete(States\IState $state): bool;

}
