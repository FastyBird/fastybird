<?php declare(strict_types = 1);

/**
 * TGroup.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           13.04.19
 */

namespace FastyBird\Module\Ui\Controllers\Finders;

use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Queries;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Ramsey\Uuid;

/**
 * @property-read Localization\Translator $translator
 * @property-read Models\Entities\Groups\Repository $groupsRepository
 */
trait TGroup
{

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function findGroup(
		string $id,
		Entities\Dashboards\Dashboard|null $dashboard = null,
	): Entities\Groups\Group
	{
		try {
			if ($dashboard !== null) {
				$findQuery = new Queries\Entities\FindGroups();
				$findQuery->forDashboard($dashboard);
				$findQuery->byId(Uuid\Uuid::fromString($id));

				$group = $this->groupsRepository->findOneBy($findQuery);

			} else {
				$group = $this->groupsRepository->find(Uuid\Uuid::fromString($id));
			}

			if ($group === null) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_NOT_FOUND,
					$this->translator->translate('//ui-module.base.messages.notFound.heading'),
					$this->translator->translate('//ui-module.base.messages.notFound.message'),
				);
			}
		} catch (Uuid\Exception\InvalidUuidStringException) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				$this->translator->translate('//ui-module.base.messages.notFound.heading'),
				$this->translator->translate('//ui-module.base.messages.notFound.message'),
			);
		}

		return $group;
	}

}
