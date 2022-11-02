<?php declare(strict_types = 1);

/**
 * IActionsRepository.php
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

/**
 * Action state repository interface
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IActionsRepository
{

	/**
	 * @param Entities\Actions\IAction $action
	 *
	 * @return States\IAction|null
	 */
	public function findOne(
		Entities\Actions\IAction $action
	): ?States\IAction;

}
