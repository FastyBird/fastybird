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

namespace FastyBird\TriggersModule\Models\States;

use FastyBird\TriggersModule\Entities;
use FastyBird\TriggersModule\States;
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

	/**
	 * @param Entities\Actions\IAction $action
	 * @param Utils\ArrayHash $values
	 *
	 * @return States\IAction
	 */
	public function create(
		Entities\Actions\IAction $action,
		Utils\ArrayHash $values
	): States\IAction;

	/**
	 * @param States\IAction $state
	 * @param Utils\ArrayHash $values
	 *
	 * @return States\IAction
	 */
	public function update(
		States\IAction $state,
		Utils\ArrayHash $values
	): States\IAction;

	/**
	 * @param States\IAction $state
	 *
	 * @return bool
	 */
	public function delete(
		States\IAction $state
	): bool;

}
