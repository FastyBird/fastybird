<?php declare(strict_types = 1);

/**
 * TChannelProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 * @since          0.33.0
 *
 * @date           09.02.22
 */

namespace FastyBird\DevicesModule\Controllers\Finders;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\Queries;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Localization;
use Ramsey\Uuid;

/**
 * @property-read Localization\Translator $translator
 * @property-read Models\Channels\Properties\PropertiesRepository $channelPropertiesRepository
 */
trait TChannelProperty
{

	/**
	 * @throws JsonApiExceptions\JsonApi
	 */
	private function findProperty(
		string $id,
		Entities\Channels\Channel $channel,
	): Entities\Channels\Properties\Property
	{
		try {
			$findQuery = new Queries\FindChannelProperties();
			$findQuery->forChannel($channel);
			$findQuery->byId(Uuid\Uuid::fromString($id));

			$property = $this->channelPropertiesRepository->findOneBy($findQuery);

			if ($property === null) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_NOT_FOUND,
					$this->translator->translate('//devices-module.base.messages.notFound.heading'),
					$this->translator->translate('//devices-module.base.messages.notFound.message'),
				);
			}
		} catch (Uuid\Exception\InvalidUuidStringException) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				$this->translator->translate('//devices-module.base.messages.notFound.heading'),
				$this->translator->translate('//devices-module.base.messages.notFound.message'),
			);
		}

		return $property;
	}

}
