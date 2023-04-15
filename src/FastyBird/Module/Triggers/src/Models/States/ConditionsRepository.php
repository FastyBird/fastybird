<?php declare(strict_types = 1);

/**
 * IConditionsRepository.php
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

use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\States;
use Nette;

/**
 * Condition state repository
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConditionsRepository
{

	use Nette\SmartObject;

	public function __construct(protected readonly IConditionsRepository|null $repository = null)
	{
	}

	/**
	 * @throws Exceptions\NotImplemented
	 */
	public function findOne(
		Entities\Conditions\Condition $condition,
	): States\Condition|null
	{
		if ($this->repository === null) {
			throw new Exceptions\NotImplemented('Condition state repository is not registered');
		}

		return $this->repository->findOne($condition);
	}

}
