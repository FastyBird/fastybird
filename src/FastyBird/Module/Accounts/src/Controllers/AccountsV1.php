<?php declare(strict_types = 1);

/**
 * AccountsV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 * @since          0.1.0
 *
 * @date           21.06.20
 */

namespace FastyBird\Module\Accounts\Controllers;

use Doctrine;
use Exception;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Hydrators;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Router;
use FastyBird\Module\Accounts\Schemas;
use FastyBird\Module\Accounts\Utilities;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette\Utils;
use Psr\Http\Message;
use Ramsey\Uuid;
use Throwable;
use function end;
use function explode;
use function preg_match;
use function strtolower;
use function strval;

/**
 * Accounts controller
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured
 * @Secured\Role(manager,administrator)
 */
final class AccountsV1 extends BaseV1
{

	public function __construct(
		private readonly Hydrators\Accounts\Account $accountHydrator,
		private readonly Models\Accounts\AccountsRepository $accountsRepository,
		private readonly Models\Accounts\AccountsManager $accountsManager,
		private readonly Models\Identities\IdentitiesManager $identitiesManager,
	)
	{
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$findQuery = new Queries\FindAccounts();

		$accounts = $this->accountsRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $accounts);
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// Find account
		$account = $this->findAccount($request);

		return $this->buildResponse($request, $response, $account);
	}

	/**
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function create(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$document = $this->createDocument($request);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if ($document->getResource()->getType() === Schemas\Accounts\Account::SCHEMA_TYPE) {
				$createData = $this->accountHydrator->hydrate($document);

				// Store item into database
				$account = $this->accountsManager->create($createData);

			} else {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate('//accounts-module.base.messages.invalidType.heading'),
					$this->translator->translate('//accounts-module.base.messages.invalidType.message'),
					[
						'pointer' => '/data/type',
					],
				);
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (JsonApiExceptions\JsonApi $ex) {
			throw $ex;
		} catch (Exceptions\AccountRoleInvalid) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.invalidRelation.heading'),
				$this->translator->translate('//accounts-module.base.messages.invalidRelation.message'),
				[
					'pointer' => '/data/relationships/roles/data/id',
				],
			);
		} catch (DoctrineCrudExceptions\EntityCreationException $ex) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.missingAttribute.heading'),
				$this->translator->translate('//accounts-module.base.messages.missingAttribute.message'),
				[
					'pointer' => '/data/attributes/' . $ex->getField(),
				],
			);
		} catch (Doctrine\DBAL\Exception\UniqueConstraintViolationException $ex) {
			if (preg_match("%PRIMARY'%", $ex->getMessage(), $match) === 1) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate('//accounts-module.base.messages.uniqueIdentifier.heading'),
					$this->translator->translate('//accounts-module.base.messages.uniqueIdentifier.message'),
					[
						'pointer' => '/data/id',
					],
				);
			} elseif (preg_match("%key '(?P<key>.+)_unique'%", $ex->getMessage(), $match) === 1) {
				$columnParts = explode('.', $match['key']);
				$columnKey = end($columnParts);

				if (Utils\Strings::startsWith($columnKey, 'account_')) {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						$this->translator->translate('//accounts-module.base.messages.uniqueAttribute.heading'),
						$this->translator->translate('//accounts-module.base.messages.uniqueAttribute.message'),
						[
							'pointer' => '/data/attributes/' . Utilities\Api::fieldToJsonApi(
								Utils\Strings::substring($columnKey, 8),
							),
						],
					);
				}
			}

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.uniqueAttribute.heading'),
				$this->translator->translate('//accounts-module.base.messages.uniqueAttribute.message'),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('An unhandled error occurred', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_ACCOUNTS,
				'type' => 'accounts-controller',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.notCreated.heading'),
				$this->translator->translate('//accounts-module.base.messages.notCreated.message'),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		$response = $this->buildResponse($request, $response, $account);

		return $response->withStatus(StatusCodeInterface::STATUS_CREATED);
	}

	/**
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function update(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$document = $this->createDocument($request);

		$account = $this->findAccount($request);

		$this->validateIdentifier($request, $document);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if ($document->getResource()->getType() === Schemas\Accounts\Account::SCHEMA_TYPE) {
				$updateAccountData = $this->accountHydrator->hydrate($document, $account);

				$account = $this->accountsManager->update($account, $updateAccountData);

			} else {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					$this->translator->translate('//accounts-module.base.messages.invalidType.heading'),
					$this->translator->translate('//accounts-module.base.messages.invalidType.message'),
					[
						'pointer' => '/data/type',
					],
				);
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (JsonApiExceptions\JsonApi $ex) {
			throw $ex;
		} catch (Exceptions\AccountRoleInvalid) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.invalidRelation.heading'),
				$this->translator->translate('//accounts-module.base.messages.invalidRelation.message'),
				[
					'pointer' => '/data/relationships/roles/data/id',
				],
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('An unhandled error occurred', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_ACCOUNTS,
				'type' => 'accounts-controller',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.notUpdated.heading'),
				$this->translator->translate('//accounts-module.base.messages.notUpdated.message'),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		return $this->buildResponse($request, $response, $account);
	}

	/**
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function delete(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$account = $this->findAccount($request);

		if (
			$this->user->getAccount() !== null
			&& $account->getId()->equals($this->user->getAccount()->getId())
		) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.accounts.messages.selfNotDeletable.heading'),
				$this->translator->translate('//accounts-module.accounts.messages.selfNotDeletable.message'),
			);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$updateData = Utils\ArrayHash::from([
				'state' => MetadataTypes\AccountState::get(MetadataTypes\AccountState::STATE_DELETED),
			]);

			$this->accountsManager->update($account, $updateData);

			foreach ($account->getIdentities() as $identity) {
				$updateIdentity = Utils\ArrayHash::from([
					'state' => MetadataTypes\IdentityState::get(MetadataTypes\IdentityState::STATE_DELETED),
				]);

				$this->identitiesManager->update($identity, $updateIdentity);
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('An unhandled error occurred', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_ACCOUNTS,
				'type' => 'accounts-controller',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.notDeleted.heading'),
				$this->translator->translate('//accounts-module.base.messages.notDeleted.message'),
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
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load account
		$account = $this->findAccount($request);

		// & relation entity name
		$relationEntity = strtolower(strval($request->getAttribute(Router\Routes::RELATION_ENTITY)));

		if ($relationEntity === Schemas\Accounts\Account::RELATIONSHIPS_IDENTITIES) {
			return $this->buildResponse($request, $response, $account->getIdentities());
		} elseif ($relationEntity === Schemas\Accounts\Account::RELATIONSHIPS_ROLES) {
			return $this->buildResponse($request, $response, $account->getRoles());
		}

		if ($relationEntity === Schemas\Accounts\Account::RELATIONSHIPS_EMAILS) {
			return $this->buildResponse($request, $response, $account->getEmails());
		}

		return parent::readRelationship($request, $response);
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws JsonApiExceptions\JsonApi
	 */
	private function findAccount(
		Message\ServerRequestInterface $request,
	): Entities\Accounts\Account
	{
		if (!Uuid\Uuid::isValid(strval($request->getAttribute(Router\Routes::URL_ITEM_ID)))) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				$this->translator->translate('//accounts-module.base.messages.notFound.heading'),
				$this->translator->translate('//accounts-module.base.messages.notFound.message'),
			);
		}

		$findQuery = new Queries\FindAccounts();
		$findQuery->byId(Uuid\Uuid::fromString(strval($request->getAttribute(Router\Routes::URL_ITEM_ID))));

		$account = $this->accountsRepository->findOneBy($findQuery);

		if ($account === null) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_NOT_FOUND,
				$this->translator->translate('//accounts-module.base.messages.notFound.heading'),
				$this->translator->translate('//accounts-module.base.messages.notFound.message'),
			);
		}

		return $account;
	}

}
