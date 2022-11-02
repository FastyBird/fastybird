<?php declare(strict_types = 1);

/**
 * ActionsV1Controller.php
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

namespace FastyBird\Module\Triggers\Controllers;

use Doctrine;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Triggers\Controllers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Queries;
use FastyBird\Module\Triggers\Router;
use FastyBird\Module\Triggers\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette\Utils;
use Psr\Http\Message;
use Throwable;

/**
 * API triggers controller
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Controllers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured
 * @Secured\User(loggedIn)
 */
final class TriggersV1Controller extends BaseV1Controller
{

	use Controllers\Finders\TTriggerFinder;

	/** @var Models\Triggers\ITriggersRepository */
	private Models\Triggers\ITriggersRepository $triggersRepository;

	/** @var Models\Triggers\ITriggersManager */
	private Models\Triggers\ITriggersManager $triggersManager;

	public function __construct(
		Models\Triggers\ITriggersRepository $triggersRepository,
		Models\Triggers\ITriggersManager $triggersManager
	) {
		$this->triggersRepository = $triggersRepository;
		$this->triggersManager = $triggersManager;
	}

	/**
	 * @param Message\ServerRequestInterface $request
	 * @param Message\ResponseInterface $response
	 *
	 * @return Message\ResponseInterface
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response
	): Message\ResponseInterface {
		$findQuery = new Queries\FindTriggers();

		$triggers = $this->triggersRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $triggers);
	}

	/**
	 * @param Message\ServerRequestInterface $request
	 * @param Message\ResponseInterface $response
	 *
	 * @return Message\ResponseInterface
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response
	): Message\ResponseInterface {
		$trigger = $this->findTrigger($request->getAttribute(Router\Routes::URL_ITEM_ID));

		return $this->buildResponse($request, $response, $trigger);
	}

	/**
	 * @param Message\ServerRequestInterface $request
	 * @param Message\ResponseInterface $response
	 *
	 * @return Message\ResponseInterface
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 * @throws Doctrine\DBAL\ConnectionException
	 *
	 * @Secured
	 * @Secured\Role(manager,administrator)
	 */
	public function create(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response
	): Message\ResponseInterface {
		$document = $this->createDocument($request);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$trigger = $this->triggersManager->create($hydrator->hydrate($document));

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (JsonApiExceptions\IJsonApiException $ex) {
				throw $ex;

			} catch (DoctrineCrudExceptions\MissingRequiredFieldException $ex) {
				throw new JsonApiExceptions\JsonApiErrorException(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate('//triggers-module.base.messages.missingAttribute.heading'),
					$this->translator->translate('//triggers-module.base.messages.missingAttribute.message'),
					[
						'pointer' => 'data/attributes/' . $ex->getField(),
					]
				);

			} catch (DoctrineCrudExceptions\EntityCreationException $ex) {
				throw new JsonApiExceptions\JsonApiErrorException(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate('//triggers-module.base.messages.missingAttribute.heading'),
					$this->translator->translate('//triggers-module.base.messages.missingAttribute.message'),
					[
						'pointer' => 'data/attributes/' . $ex->getField(),
					]
				);

			} catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
				if (preg_match("%PRIMARY'%", $ex->getMessage(), $match) === 1) {
					throw new JsonApiExceptions\JsonApiErrorException(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						$this->translator->translate('//triggers-module.base.messages.uniqueIdentifier.heading'),
						$this->translator->translate('//triggers-module.base.messages.uniqueIdentifier.message'),
						[
							'pointer' => '/data/id',
						]
					);

				} elseif (preg_match("%key '(?P<key>.+)_unique'%", $ex->getMessage(), $match) === 1) {
					$columnParts = explode('.', $match['key']);
					$columnKey = end($columnParts);

					if (is_string($columnKey) && Utils\Strings::startsWith($columnKey, 'trigger_')) {
						throw new JsonApiExceptions\JsonApiErrorException(
							StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
							$this->translator->translate('//triggers-module.base.messages.uniqueAttribute.heading'),
							$this->translator->translate('//triggers-module.base.messages.uniqueAttribute.message'),
							[
								'pointer' => '/data/attributes/' . Utils\Strings::substring($columnKey, 8),
							]
						);
					}
				}

				throw new JsonApiExceptions\JsonApiErrorException(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate('//triggers-module.base.messages.uniqueAttribute.heading'),
					$this->translator->translate('//triggers-module.base.messages.uniqueAttribute.message')
				);

			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error('An unhandled error occurred', [
					'source'    => 'triggers-module-triggers-controller',
					'type'      => 'create',
					'exception' => [
						'message' => $ex->getMessage(),
						'code'    => $ex->getCode(),
					],
				]);

				throw new JsonApiExceptions\JsonApiErrorException(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate('//triggers-module.base.messages.notCreated.heading'),
					$this->translator->translate('//triggers-module.base.messages.notCreated.message')
				);

			} finally {
				// Revert all changes when error occur
				if ($this->getOrmConnection()->isTransactionActive()) {
					$this->getOrmConnection()->rollBack();
				}
			}

			$response = $this->buildResponse($request, $response, $trigger);
			return $response->withStatus(StatusCodeInterface::STATUS_CREATED);
		}

		throw new JsonApiExceptions\JsonApiErrorException(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			$this->translator->translate('//triggers-module.base.messages.invalidType.heading'),
			$this->translator->translate('//triggers-module.base.messages.invalidType.message'),
			[
				'pointer' => '/data/type',
			]
		);
	}

	/**
	 * @param Message\ServerRequestInterface $request
	 * @param Message\ResponseInterface $response
	 *
	 * @return Message\ResponseInterface
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 * @throws Doctrine\DBAL\ConnectionException
	 *
	 * @Secured
	 * @Secured\Role(manager,administrator)
	 */
	public function update(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response
	): Message\ResponseInterface {
		// At first, try to load trigger
		$trigger = $this->findTrigger($request->getAttribute(Router\Routes::URL_ITEM_ID));

		$document = $this->createDocument($request);

		$this->validateIdentifier($request, $document);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$trigger = $this->triggersManager->update($trigger, $hydrator->hydrate($document, $trigger));

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (JsonApiExceptions\IJsonApiException $ex) {
				throw $ex;

			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error('An unhandled error occurred', [
					'source'    => 'triggers-module-triggers-controller',
					'type'      => 'update',
					'exception' => [
						'message' => $ex->getMessage(),
						'code'    => $ex->getCode(),
					],
				]);

				throw new JsonApiExceptions\JsonApiErrorException(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate('//triggers-module.base.messages.notUpdated.heading'),
					$this->translator->translate('//triggers-module.base.messages.notUpdated.message')
				);

			} finally {
				// Revert all changes when error occur
				if ($this->getOrmConnection()->isTransactionActive()) {
					$this->getOrmConnection()->rollBack();
				}
			}

			return $this->buildResponse($request, $response, $trigger);
		}

		throw new JsonApiExceptions\JsonApiErrorException(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			$this->translator->translate('//triggers-module.base.messages.invalidType.heading'),
			$this->translator->translate('//triggers-module.base.messages.invalidType.message'),
			[
				'pointer' => '/data/type',
			]
		);
	}

	/**
	 * @param Message\ServerRequestInterface $request
	 * @param Message\ResponseInterface $response
	 *
	 * @return Message\ResponseInterface
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 * @throws Doctrine\DBAL\ConnectionException
	 *
	 * @Secured
	 * @Secured\Role(manager,administrator)
	 */
	public function delete(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response
	): Message\ResponseInterface {
		// At first, try to load trigger
		$trigger = $this->findTrigger($request->getAttribute(Router\Routes::URL_ITEM_ID));

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->triggersManager->delete($trigger);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('An unhandled error occurred', [
				'source'    => 'triggers-module-triggers-controller',
				'type'      => 'delete',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//triggers-module.base.messages.notUpdated.heading'),
				$this->translator->translate('//triggers-module.base.messages.notDeleted.message')
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
	 * @param Message\ServerRequestInterface $request
	 * @param Message\ResponseInterface $response
	 *
	 * @return Message\ResponseInterface
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response
	): Message\ResponseInterface {
		// At first, try to load trigger
		$trigger = $this->findTrigger($request->getAttribute(Router\Routes::URL_ITEM_ID));

		$relationEntity = strtolower($request->getAttribute(Router\Routes::RELATION_ENTITY));

		if ($relationEntity === Schemas\Triggers\Trigger::RELATIONSHIPS_ACTIONS) {
			return $this->buildResponse($request, $response, $trigger->getActions());

		} elseif ($relationEntity === Schemas\Triggers\Trigger::RELATIONSHIPS_NOTIFICATIONS) {
			return $this->buildResponse($request, $response, $trigger->getNotifications());

		} elseif (
			$relationEntity === Schemas\Triggers\AutomaticTrigger::RELATIONSHIPS_CONDITIONS
			&& $trigger instanceof Entities\Triggers\AutomaticTrigger
		) {
			return $this->buildResponse($request, $response, $trigger->getConditions());

		} elseif (
			$relationEntity === Schemas\Triggers\ManualTrigger::RELATIONSHIPS_CONTROLS
			&& $trigger instanceof Entities\Triggers\ManualTrigger
		) {
			return $this->buildResponse($request, $response, $trigger->getControls());
		}

		return parent::readRelationship($request, $response);
	}

}
