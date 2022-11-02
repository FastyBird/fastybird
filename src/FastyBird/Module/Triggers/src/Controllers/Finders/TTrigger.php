<?php declare(strict_types = 1);

/**
 * TTriggerFinder.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Controllers
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Controllers\Finders;

use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Queries;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Ramsey\Uuid;

/**
 * @property-read Localization\ITranslator $translator
 * @property-read Models\Triggers\ITriggersRepository $triggersRepository
 */
trait TTriggerFinder
{

	/**
	 * @param string $id
	 *
	 * @return Entities\Triggers\ITrigger
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	protected function findTrigger(string $id): Entities\Triggers\ITrigger
	{
		try {
			$findQuery = new Queries\FindTriggers();
			$findQuery->byId(Uuid\Uuid::fromString($id));

			$trigger = $this->triggersRepository->findOneBy($findQuery);

			if ($trigger === null) {
				throw new JsonApiExceptions\JsonApiErrorException(
					StatusCodeInterface::STATUS_NOT_FOUND,
					$this->translator->translate('//triggers-module.base.messages.notFound.heading'),
					$this->translator->translate('//triggers-module.base.messages.notFound.message')
				);
			}
		} catch (Uuid\Exception\InvalidUuidStringException $ex) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_NOT_FOUND,
				$this->translator->translate('//triggers-module.base.messages.notFound.heading'),
				$this->translator->translate('//triggers-module.base.messages.notFound.message')
			);
		}

		return $trigger;
	}

}
