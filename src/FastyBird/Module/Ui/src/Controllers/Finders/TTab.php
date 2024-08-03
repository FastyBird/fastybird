<?php declare(strict_types = 1);

/**
 * TTab.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           03.08.24
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
 * @property-read Models\Entities\Dashboards\Tabs\Repository $tabsRepository
 */
trait TTab
{

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function findTab(
		string $id,
		Entities\Dashboards\Dashboard|null $dashboard = null,
	): Entities\Dashboards\Tabs\Tab
	{
		try {
			if ($dashboard !== null) {
				$findQuery = new Queries\Entities\FindTabs();
				$findQuery->forDashboard($dashboard);
				$findQuery->byId(Uuid\Uuid::fromString($id));

				$tab = $this->tabsRepository->findOneBy($findQuery);

			} else {
				$tab = $this->tabsRepository->find(Uuid\Uuid::fromString($id));
			}

			if ($tab === null) {
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

		return $tab;
	}

}
