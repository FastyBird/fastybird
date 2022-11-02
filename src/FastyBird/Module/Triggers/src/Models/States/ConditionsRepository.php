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
use FastyBird\TriggersModule\Exceptions;
use FastyBird\TriggersModule\States;
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

	/** @var IConditionsRepository|null */
	protected ?IConditionsRepository $repository;

	public function __construct(
		?IConditionsRepository $repository
	) {
		$this->repository = $repository;
	}

	/**
	 * @param Entities\Conditions\ICondition $condition
	 *
	 * @return States\ICondition|null
	 */
	public function findOne(
		Entities\Conditions\ICondition $condition
	): ?States\ICondition {
		if ($this->repository === null) {
			throw new Exceptions\NotImplementedException('Condition state repository is not registered');
		}

		return $this->repository->findOne($condition);
	}

}
