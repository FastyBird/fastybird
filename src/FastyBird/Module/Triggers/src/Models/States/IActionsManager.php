<?php declare(strict_types = 1);

/**
 * IActionsManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 * @since          0.6.0
 *
 * @date           09.01.22
 */

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\States;
use Nette\Utils;

/**
 * Actions states manager interface
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IActionsManager
{

	public function create(
		Entities\Actions\Action $action,
		Utils\ArrayHash $values,
	): States\Action;

	public function update(
		States\Action $state,
		Utils\ArrayHash $values,
	): States\Action;

	public function delete(States\Action $state): bool;

}
