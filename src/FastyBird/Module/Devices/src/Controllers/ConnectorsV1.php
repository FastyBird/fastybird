<?php declare(strict_types = 1);

/**
 * ConnectorsV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           17.01.21
 */

namespace FastyBird\Module\Devices\Controllers;

use Doctrine;
use Exception;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use Nette\Utils;
use Psr\Http\Message;
use Ramsey\Uuid;
use Throwable;
use function strval;

/**
 * API connectors controller
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured\User(loggedIn)
 */
class ConnectorsV1 extends BaseV1
{

	public function __construct(
		private readonly Models\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly Models\Entities\Connectors\Properties\PropertiesRepository $propertiesRepository,
		private readonly Models\Entities\Connectors\Controls\ControlsRepository $controlsRepository,
		private readonly Models\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly Models\Entities\Connectors\ConnectorsManager $connectorsManager,
	)
	{
	}

	/**
	 * @throws Exception
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$findQuery = new Queries\Entities\FindConnectors();

		$connectors = $this->connectorsRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $connectors);
	}

	/**
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$connector = $this->findConnector(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));

		return $this->buildResponse($request, $response, $connector);
	}

	/**
	 * @throws Doctrine\DBAL\Exception
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 * @throws JsonApiExceptions\JsonApiError
	 *
	 * @Secured\Role(manager,administrator)
	 */
	public function update(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$connector = $this->findConnector(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));

		$document = $this->createDocument($request);

		$this->validateIdentifier($request, $document);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$connector = $this->connectorsManager->update($connector, $hydrator->hydrate($document, $connector));

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (JsonApiExceptions\JsonApi $ex) {
				throw $ex;
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => MetadataTypes\Sources\Module::DEVICES->value,
						'type' => 'connectors-controller',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
					],
				);

				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//devices-module.base.messages.notUpdated.heading')),
					strval($this->translator->translate('//devices-module.base.messages.notUpdated.message')),
				);
			} finally {
				// Revert all changes when error occur
				if ($this->getOrmConnection()->isTransactionActive()) {
					$this->getOrmConnection()->rollBack();
				}
			}

			return $this->buildResponse($request, $response, $connector);
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate('//devices-module.base.messages.invalidType.heading')),
			strval($this->translator->translate('//devices-module.base.messages.invalidType.message')),
			[
				'pointer' => '/data/type',
			],
		);
	}

	/**
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$connector = $this->findConnector(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));

		$relationEntity = Utils\Strings::lower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($relationEntity === Schemas\Connectors\Connector::RELATIONSHIPS_DEVICES) {
			$findDevicesQuery = new Queries\Entities\FindDevices();
			$findDevicesQuery->forConnector($connector);

			return $this->buildResponse($request, $response, $this->devicesRepository->findAllBy($findDevicesQuery));
		} elseif ($relationEntity === Schemas\Connectors\Connector::RELATIONSHIPS_PROPERTIES) {
			$findConnectorPropertiesQuery = new Queries\Entities\FindConnectorProperties();
			$findConnectorPropertiesQuery->forConnector($connector);

			return $this->buildResponse(
				$request,
				$response,
				$this->propertiesRepository->findAllBy($findConnectorPropertiesQuery),
			);
		} elseif ($relationEntity === Schemas\Connectors\Connector::RELATIONSHIPS_CONTROLS) {
			$findConnectorControlsQuery = new Queries\Entities\FindConnectorControls();
			$findConnectorControlsQuery->forConnector($connector);

			return $this->buildResponse(
				$request,
				$response,
				$this->controlsRepository->findAllBy($findConnectorControlsQuery),
			);
		}

		return parent::readRelationship($request, $response);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function findConnector(string $id): Entities\Connectors\Connector
	{
		try {
			$connector = $this->connectorsRepository->find(Uuid\Uuid::fromString($id));

			if ($connector === null) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_NOT_FOUND,
					strval($this->translator->translate('//devices-module.base.messages.notFound.heading')),
					strval($this->translator->translate('//devices-module.base.messages.notFound.message')),
				);
			}
		} catch (Uuid\Exception\InvalidUuidStringException) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				strval($this->translator->translate('//devices-module.base.messages.notFound.heading')),
				strval($this->translator->translate('//devices-module.base.messages.notFound.message')),
			);
		}

		return $connector;
	}

}
