<?php declare(strict_types = 1);

/**
 * IConditionsManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           09.01.22
 */

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Module\Triggers\States;
use Nette\Utils;
use Ramsey\Uuid;

/**
 * Conditions states manager interface
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IConditionsManager
{

	public function create(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\Condition;

	public function update(Uuid\UuidInterface $id, Utils\ArrayHash $values): States\Condition|false;

	public function delete(Uuid\UuidInterface $id): bool;

}
