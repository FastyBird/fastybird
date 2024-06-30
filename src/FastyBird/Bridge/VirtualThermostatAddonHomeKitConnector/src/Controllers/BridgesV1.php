<?php declare(strict_types = 1);

/**
 * BridgesV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           04.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Controllers;

use Doctrine;
use Exception;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Builders;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Exceptions;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Hydrators;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Queries;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Router;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Schemas;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette\Utils;
use Psr\Http\Message;
use Ramsey\Uuid;
use Throwable;
use function assert;
use function end;
use function explode;
use function preg_match;
use function str_starts_with;
use function strval;

/**
 * API bridges controller
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured
 * @Secured\User(loggedIn)
 */
class BridgesV1 extends BaseV1
{

	public function __construct(
		private readonly Builders\Builder $bridgeBuilder,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
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
		$findQuery = new Queries\Entities\FindThermostatDevices();

		$devices = $this->devicesRepository->getResultSet(
			$findQuery,
			Entities\Devices\Thermostat::class,
		);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $devices);
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
		$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));

		return $this->buildResponse($request, $response, $device);
	}

	/**
	 * @throws Doctrine\DBAL\Exception
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 * @throws JsonApiExceptions\JsonApiError
	 *
	 * @Secured
	 * @Secured\Role(manager,administrator)
	 */
	public function create(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$document = $this->createDocument($request);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator instanceof Hydrators\Devices\Thermostat) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$device = $this->devicesManager->create($hydrator->hydrate($document));
				assert($device instanceof Entities\Devices\Thermostat);

				$device = $this->bridgeBuilder->build(
					$device->getParent(),
					$device->getConnector(),
				);

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (JsonApiExceptions\JsonApi $ex) {
				throw $ex;
			} catch (DoctrineCrudExceptions\MissingRequiredFieldException $ex) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.missingAttribute.heading',
					),
					$this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.missingAttribute.message',
					),
					[
						'pointer' => '/data/attributes/' . DevicesUtilities\Api::fieldToJsonApi($ex->getField()),
					],
				);
			} catch (DoctrineCrudExceptions\EntityCreationException $ex) {
				if ($ex->getField() === Schemas\Devices\Thermostat::RELATIONSHIPS_PARENTS) {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						$this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.missingRelation.heading',
						),
						$this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.missingRelation.message',
						),
						[
							'pointer' => '/data/relationships/' . Schemas\Devices\Thermostat::RELATIONSHIPS_PARENTS . '/data/id',
						],
					);
				} else {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						$this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.missingAttribute.heading',
						),
						$this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.missingAttribute.message',
						),
						[
							'pointer' => '/data/attributes/' . DevicesUtilities\Api::fieldToJsonApi($ex->getField()),
						],
					);
				}
			} catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
				if (preg_match("%PRIMARY'%", $ex->getMessage(), $match) === 1) {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						$this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueIdentifier.heading',
						),
						$this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueIdentifier.message',
						),
						[
							'pointer' => '/data/id',
						],
					);
				} elseif (preg_match("%key '(?P<key>.+)_unique'%", $ex->getMessage(), $match) === 1) {
					$columnParts = explode('.', $match['key']);
					$columnKey = end($columnParts);

					if (str_starts_with($columnKey, 'device_')) {
						throw new JsonApiExceptions\JsonApiError(
							StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
							$this->translator->translate(
								'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.heading',
							),
							$this->translator->translate(
								'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.message',
							),
							[
								'pointer' => '/data/attributes/' . DevicesUtilities\Api::fieldToJsonApi(
									Utils\Strings::substring($columnKey, 7),
								),
							],
						);
					}
				}

				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.heading',
					),
					$this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.message',
					),
				);
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => MetadataTypes\Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value,
						'type' => 'bridges-controller',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
					],
				);

				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notCreated.heading',
					),
					$this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notCreated.message',
					),
				);
			} finally {
				// Revert all changes when error occur
				if ($this->getOrmConnection()->isTransactionActive()) {
					$this->getOrmConnection()->rollBack();
				}
			}

			$response = $this->buildResponse($request, $response, $device);

			return $response->withStatus(StatusCodeInterface::STATUS_CREATED);
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.invalidType.heading',
			),
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.invalidType.message',
			),
			[
				'pointer' => '/data/type',
			],
		);
	}

	/**
	 * @throws Doctrine\DBAL\Exception
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 * @throws JsonApiExceptions\JsonApiError
	 *
	 * @Secured
	 * @Secured\Role(manager,administrator)
	 */
	public function update(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$document = $this->createDocument($request);

		$this->validateIdentifier($request, $document);

		$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator instanceof Hydrators\Devices\Thermostat) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$device = $this->devicesManager->update($device, $hydrator->hydrate($document));
				assert($device instanceof Entities\Devices\Thermostat);

				$device = $this->bridgeBuilder->build(
					$device->getParent(),
					$device->getConnector(),
				);

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (JsonApiExceptions\JsonApi $ex) {
				throw $ex;
			} catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
				if (preg_match("%key '(?P<key>.+)_unique'%", $ex->getMessage(), $match) !== false) {
					$columnParts = explode('.', $match['key']);
					$columnKey = end($columnParts);

					if (str_starts_with($columnKey, 'device_')) {
						throw new JsonApiExceptions\JsonApiError(
							StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
							$this->translator->translate(
								'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.heading',
							),
							$this->translator->translate(
								'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.message',
							),
							[
								'pointer' => '/data/attributes/' . DevicesUtilities\Api::fieldToJsonApi(
									Utils\Strings::substring($columnKey, 7),
								),
							],
						);
					}
				}

				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.heading',
					),
					$this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.uniqueAttribute.message',
					),
				);
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => MetadataTypes\Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value,
						'type' => 'bridges-controller',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
					],
				);

				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notUpdated.heading',
					),
					$this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notUpdated.message',
					),
				);
			} finally {
				// Revert all changes when error occur
				if ($this->getOrmConnection()->isTransactionActive()) {
					$this->getOrmConnection()->rollBack();
				}
			}

			return $this->buildResponse($request, $response, $device);
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.invalidType.heading',
			),
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.invalidType.message',
			),
			[
				'pointer' => '/data/type',
			],
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Doctrine\DBAL\Exception
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 * @throws JsonApiExceptions\JsonApiError
	 *
	 * @Secured
	 * @Secured\Role(manager,administrator)
	 */
	public function delete(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$device = $this->findDevice(strval($request->getAttribute(Router\ApiRoutes::URL_ITEM_ID)));

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$findChannelsQuery = new DevicesQueries\Entities\FindChannels();
			$findChannelsQuery->forDevice($device);

			foreach ($this->channelsRepository->findAllBy($findChannelsQuery) as $channel) {
				$this->channelsManager->delete($channel);
			}

			// Move device back into warehouse
			$this->devicesManager->delete($device);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Bridge::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR->value,
					'type' => 'bridges-controller',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notDeleted.heading',
				),
				$this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notDeleted.message',
				),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		return $response->withStatus(StatusCodeInterface::STATUS_NO_CONTENT);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 */
	protected function findDevice(string $id): Entities\Devices\Thermostat
	{
		try {
			$device = $this->devicesRepository->find(
				Uuid\Uuid::fromString($id),
				Entities\Devices\Thermostat::class,
			);

			if ($device === null) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_NOT_FOUND,
					$this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notFound.heading',
					),
					$this->translator->translate(
						'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notFound.message',
					),
				);
			}
		} catch (Uuid\Exception\InvalidUuidStringException) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				$this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notFound.heading',
				),
				$this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.base.messages.notFound.message',
				),
			);
		}

		return $device;
	}

}
