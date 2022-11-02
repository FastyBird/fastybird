<?php declare(strict_types = 1);

/**
 * IConditionsRepository.php
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
 * Condition state repository interface
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IConditionsRepository
{

	/**
	 * @param Entities\Conditions\ICondition $condition
	 *
	 * @return States\ICondition|null
	 */
	public function findOne(
		Entities\Conditions\ICondition $condition
	): ?States\ICondition;

}
