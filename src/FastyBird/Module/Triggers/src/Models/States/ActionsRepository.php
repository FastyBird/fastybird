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

namespace FastyBird\Module\Triggers\Models\States;

use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\States;
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

	public function __construct(protected readonly IActionsRepository|null $repository = null)
	{
	}

	/**
	 * @throws Exceptions\NotImplemented
	 */
	public function findOne(Entities\Actions\Action $action): States\Action|null
	{
		if ($this->repository === null) {
			throw new Exceptions\NotImplemented('Action state repository is not registered');
		}

		return $this->repository->findOne($action);
	}

}
