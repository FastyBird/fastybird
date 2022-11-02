<?php declare(strict_types = 1);

/**
 * ConditionsV1Controller.php
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
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Queries;
use FastyBird\Module\Triggers\Router;
use FastyBird\Module\Triggers\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette\Utils;
use Psr\Http\Message;
use Ramsey\Uuid;
use Throwable;

/**
 * Triggers conditions controller
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Controllers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured
 * @Secured\User(loggedIn)
 */
final class ConditionsV1Controller extends BaseV1Controller
{

	use Controllers\Finders\TTriggerFinder;

	/** @var Models\Triggers\ITriggersRepository */
	protected Models\Triggers\ITriggersRepository $triggersRepository;

	/** @var Models\Conditions\IConditionRepository */
	private Models\Conditions\IConditionRepository $conditionsRepository;

	/** @var Models\Conditions\IConditionsManager */
	private Models\Conditions\IConditionsManager $conditionsManager;

	public function __construct(
		Models\Triggers\ITriggersRepository $triggersRepository,
		Models\Conditions\IConditionRepository $conditionsRepository,
		Models\Conditions\IConditionsManager $conditionsManager
	) {
		$this->triggersRepository = $triggersRepository;
		$this->conditionsRepository = $conditionsRepository;
		$this->conditionsManager = $conditionsManager;
	}

	/**
	 * @param Message\ServerRequestInterface $request
	 * @param Message\ResponseInterface $response
	 *
	 * @return Message\ResponseInterface
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response
	): Message\ResponseInterface {
		// At first, try to load trigger
		$trigger = $this->findTrigger($request->getAttribute(Router\Routes::URL_TRIGGER_ID));

		if (!$trigger instanceof Entities\Triggers\IAutomaticTrigger) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_NOT_FOUND,
				$this->translator->translate('//triggers-module.base.messages.notFound.heading'),
				$this->translator->translate('//triggers-module.base.messages.notFound.message')
			);
		}

		$findQuery = new Queries\FindConditions();
		$findQuery->forTrigger($trigger);

		$rows = $this->conditionsRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $rows);
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
		// At first, try to load trigger
		$trigger = $this->findTrigger($request->getAttribute(Router\Routes::URL_TRIGGER_ID));

		if (!$trigger instanceof Entities\Triggers\IAutomaticTrigger) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_NOT_FOUND,
				$this->translator->translate('//triggers-module.base.messages.notFound.heading'),
				$this->translator->translate('//triggers-module.base.messages.notFound.message')
			);
		}

		// & condition
		$condition = $this->findCondition($request->getAttribute(Router\Routes::URL_ITEM_ID), $trigger);

		return $this->buildResponse($request, $response, $condition);
	}

	/**
	 * @param string $id
	 * @param Entities\Triggers\ITrigger $trigger
	 *
	 * @return Entities\Conditions\ICondition
	 *
	 * @throws JsonApiExceptions\IJsonApiException
	 */
	protected function findCondition(
		string $id,
		Entities\Triggers\ITrigger $trigger
	): Entities\Conditions\ICondition {
		try {
			$findQuery = new Queries\FindConditions();
			$findQuery->byId(Uuid\Uuid::fromString($id));
			$findQuery->forTrigger($trigger);

			$condition = $this->conditionsRepository->findOneBy($findQuery);

			if ($condition === null) {
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

		return $condition;
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
		// At first, try to load trigger
		$trigger = $this->findTrigger($request->getAttribute(Router\Routes::URL_TRIGGER_ID));

		if ($trigger instanceof Entities\Triggers\IAutomaticTrigger) {
			$document = $this->createDocument($request);

			$hydrator = $this->hydratorsContainer->findHydrator($document);

			if ($hydrator !== null) {
				try {
					// Start transaction connection to the database
					$this->getOrmConnection()->beginTransaction();

					$condition = $this->conditionsManager->create($hydrator->hydrate($document));

					// Commit all changes into database
					$this->getOrmConnection()->commit();

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

				} catch (JsonApiExceptions\IJsonApiException $ex) {
					throw $ex;

				} catch (Exceptions\UniqueConditionConstraint $ex) {
					throw new JsonApiExceptions\JsonApiErrorException(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						$this->translator->translate('//triggers-module.conditions.messages.propertyNotUnique.heading'),
						$this->translator->translate('//triggers-module.conditions.messages.propertyNotUnique.message'),
						[
							'pointer' => '/data/relationships/property',
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

						if (is_string($columnKey) && Utils\Strings::startsWith($columnKey, 'condition_')) {
							throw new JsonApiExceptions\JsonApiErrorException(
								StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
								$this->translator->translate('//triggers-module.base.messages.uniqueAttribute.heading'),
								$this->translator->translate('//triggers-module.base.messages.uniqueAttribute.message'),
								[
									'pointer' => '/data/attributes/' . Utils\Strings::substring($columnKey, 10),
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
						'source'    => 'triggers-module-conditions-controller',
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

				$response = $this->buildResponse($request, $response, $condition);
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

		throw new JsonApiExceptions\JsonApiErrorException(
			StatusCodeInterface::STATUS_NOT_FOUND,
			$this->translator->translate('//triggers-module.base.messages.notFound.heading'),
			$this->translator->translate('//triggers-module.base.messages.notFound.message')
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
		$trigger = $this->findTrigger($request->getAttribute(Router\Routes::URL_TRIGGER_ID));

		if (!$trigger instanceof Entities\Triggers\IAutomaticTrigger) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_NOT_FOUND,
				$this->translator->translate('//triggers-module.base.messages.notFound.heading'),
				$this->translator->translate('//triggers-module.base.messages.notFound.message')
			);
		}

		// & condition
		$condition = $this->findCondition($request->getAttribute(Router\Routes::URL_ITEM_ID), $trigger);

		$document = $this->createDocument($request);

		$this->validateIdentifier($request, $document);

		$hydrator = $this->hydratorsContainer->findHydrator($document);

		if ($hydrator !== null) {
			try {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$condition = $this->conditionsManager->update($condition, $hydrator->hydrate($document, $condition));

				// Commit all changes into database
				$this->getOrmConnection()->commit();

			} catch (JsonApiExceptions\IJsonApiException $ex) {
				throw $ex;

			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error('An unhandled error occurred', [
					'source'    => 'triggers-module-conditions-controller',
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

			return $this->buildResponse($request, $response, $condition);
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
		$trigger = $this->findTrigger($request->getAttribute(Router\Routes::URL_TRIGGER_ID));

		if (!$trigger instanceof Entities\Triggers\IAutomaticTrigger) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_NOT_FOUND,
				$this->translator->translate('//triggers-module.base.messages.notFound.heading'),
				$this->translator->translate('//triggers-module.base.messages.notFound.message')
			);
		}

		// & condition
		$condition = $this->findCondition($request->getAttribute(Router\Routes::URL_ITEM_ID), $trigger);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->conditionsManager->delete($condition);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('An unhandled error occurred', [
				'source'    => 'triggers-module-conditions-controller',
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
		$trigger = $this->findTrigger($request->getAttribute(Router\Routes::URL_TRIGGER_ID));

		if (!$trigger instanceof Entities\Triggers\IAutomaticTrigger) {
			throw new JsonApiExceptions\JsonApiErrorException(
				StatusCodeInterface::STATUS_NOT_FOUND,
				$this->translator->translate('//triggers-module.base.messages.notFound.heading'),
				$this->translator->translate('//triggers-module.base.messages.notFound.message')
			);
		}

		// & condition
		$condition = $this->findCondition($request->getAttribute(Router\Routes::URL_ITEM_ID), $trigger);

		$relationEntity = strtolower($request->getAttribute(Router\Routes::RELATION_ENTITY));

		if ($relationEntity === Schemas\Conditions\Condition::RELATIONSHIPS_TRIGGER) {
			return $this->buildResponse($request, $response, $condition->getTrigger());
		}

		return parent::readRelationship($request, $response);
	}

}
