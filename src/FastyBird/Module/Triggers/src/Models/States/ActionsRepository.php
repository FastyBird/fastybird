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
use FastyBird\TriggersModule\Exceptions;
use FastyBird\TriggersModule\States;
use Nette;

/**
 * Action state repository
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ActionsRepository
{

	use Nette\SmartObject;

	/** @var IActionsRepository|null */
	protected ?IActionsRepository $repository;

	public function __construct(
		?IActionsRepository $repository
	) {
		$this->repository = $repository;
	}

	/**
	 * @param Entities\Actions\IAction $action
	 *
	 * @return States\IAction|null
	 */
	public function findOne(
		Entities\Actions\IAction $action
	): ?States\IAction {
		if ($this->repository === null) {
			throw new Exceptions\NotImplementedException('Action state repository is not registered');
		}

		return $this->repository->findOne($action);
	}

}
