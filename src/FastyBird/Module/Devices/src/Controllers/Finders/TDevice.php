<?php declare(strict_types = 1);

/**
 * TDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 * @since          0.1.0
 *
 * @date           13.04.19
 */

namespace FastyBird\Module\Devices\Controllers\Finders;

use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Fig\Http\Message\StatusCodeInterface;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette\Localization;
use Ramsey\Uuid;

/**
 * @property-read Localization\Translator $translator
 * @property-read Models\Devices\DevicesRepository $devicesRepository
 */
trait TDevice
{

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function findDevice(string $id): Entities\Devices\Device
	{
		try {
			$findQuery = new Queries\FindDevices();
			$findQuery->byId(Uuid\Uuid::fromString($id));

			$device = $this->devicesRepository->findOneBy($findQuery);

			if ($device === null) {
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

		return $device;
	}

}
