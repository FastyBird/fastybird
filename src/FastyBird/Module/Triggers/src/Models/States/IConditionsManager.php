<?php declare(strict_types = 1);

/**
 * IConditionsManager.php
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
 * Conditions states manager interface
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IConditionsManager
{

	/**
	 * @param Entities\Conditions\ICondition $action
	 * @param Utils\ArrayHash $values
	 *
	 * @return States\ICondition
	 */
	public function create(
		Entities\Conditions\ICondition $action,
		Utils\ArrayHash $values
	): States\ICondition;

	/**
	 * @param States\ICondition $state
	 * @param Utils\ArrayHash $values
	 *
	 * @return States\ICondition
	 */
	public function update(
		States\ICondition $state,
		Utils\ArrayHash $values
	): States\ICondition;

	/**
	 * @param States\ICondition $state
	 *
	 * @return bool
	 */
	public function delete(
		States\ICondition $state
	): bool;

}
