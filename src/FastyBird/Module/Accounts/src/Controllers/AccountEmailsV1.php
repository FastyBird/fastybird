<?php declare(strict_types = 1);

/**
 * AccountEmailsV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           31.03.20
 */

namespace FastyBird\Module\Accounts\Controllers;

use Doctrine;
use Exception;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Accounts\Controllers;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Helpers;
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
use RuntimeException;
use Throwable;
use function end;
use function explode;
use function preg_match;
use function strtolower;
use function strval;

/**
 * Emails controller
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AccountEmailsV1 extends BaseV1
{

	use Controllers\Finders\TEmail;

	public function __construct(
		private readonly Hydrators\Emails\ProfileEmail $emailHydrator,
		protected readonly Models\Entities\Emails\EmailsRepository $emailsRepository,
		private readonly Models\Entities\Emails\EmailsManager $emailsManager,
		private readonly Helpers\SecurityHash $securityHash,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 *
	 * @Secured
	 * @Secured\User(loggedIn)
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$findQuery = new Queries\Entities\FindEmails();
		$findQuery->forAccount($this->findAccount());

		$emails = $this->emailsRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $emails);
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 *
	 * @Secured
	 * @Secured\User(loggedIn)
	 */
	public function read(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// Find email
		$email = $this->findEmail($request, $this->findAccount());

		return $this->buildResponse($request, $response, $email);
	}

	/**
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exception
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 * @throws RuntimeException
	 *
	 * @Secured
	 * @Secured\User(loggedIn)
	 */
	public function create(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// Get user profile account or url defined account
		$account = $this->findAccount();

		$document = $this->createDocument($request);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if ($document->getResource()->getType() === Schemas\Emails\Email::SCHEMA_TYPE) {
				$createData = $this->emailHydrator->hydrate($document);
				$createData->offsetSet('account', $account);
				$createData->offsetSet('verificationHash', $this->securityHash->createKey());
				$createData->offsetSet('verificationCreated', $this->dateFactory->getNow());

				// Store item into database
				$email = $this->emailsManager->create($createData);

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

		} catch (Exceptions\EmailIsNotValid) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.invalidAttribute.heading'),
				$this->translator->translate('//accounts-module.base.messages.invalidAttribute.message'),
				[
					'pointer' => '/data/attributes/address',
				],
			);
		} catch (Exceptions\EmailAlreadyTaken) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.emails.messages.taken.heading'),
				$this->translator->translate('//accounts-module.emails.messages.taken.message'),
				[
					'pointer' => '/data/attributes/address',
				],
			);
		} catch (DoctrineCrudExceptions\EntityCreationException $ex) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.base.messages.missingAttribute.heading'),
				$this->translator->translate('//accounts-module.base.messages.missingAttribute.message'),
				[
					'pointer' => '/data/attributes/' . Utilities\Api::fieldToJsonApi($ex->getField()),
				],
			);
		} catch (JsonApiExceptions\JsonApi $ex) {
			throw $ex;
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

				if (Utils\Strings::startsWith($columnKey, 'email_')) {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						$this->translator->translate('//accounts-module.base.messages.uniqueAttribute.heading'),
						$this->translator->translate('//accounts-module.base.messages.uniqueAttribute.message'),
						[
							'pointer' => '/data/attributes/' . Utilities\Api::fieldToJsonApi(
								Utils\Strings::substring($columnKey, 6),
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
				'type' => 'account-email-controller',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
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

		$response = $this->buildResponse($request, $response, $email);

		return $response->withStatus(StatusCodeInterface::STATUS_CREATED);
	}

	/**
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exception
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 * @throws RuntimeException
	 *
	 * @Secured
	 * @Secured\User(loggedIn)
	 */
	public function update(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$document = $this->createDocument($request);

		$email = $this->findEmail($request, $this->findAccount());

		$this->validateIdentifier($request, $document);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if ($document->getResource()->getType() === Schemas\Emails\Email::SCHEMA_TYPE) {
				$updateEmailData = $this->emailHydrator->hydrate($document, $email);

				$email = $this->emailsManager->update($email, $updateEmailData);

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
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('An unhandled error occurred', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_ACCOUNTS,
				'type' => 'account-email-controller',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
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

		return $this->buildResponse($request, $response, $email);
	}

	/**
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
	 *
	 * @Secured
	 * @Secured\User(loggedIn)
	 */
	public function delete(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$email = $this->findEmail($request, $this->findAccount());

		if ($email->isDefault()) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				$this->translator->translate('//accounts-module.emails.messages.defaultNotDeletable.heading'),
				$this->translator->translate('//accounts-module.emails.messages.defaultNotDeletable.message'),
			);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->emailsManager->delete($email);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('An unhandled error occurred', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_ACCOUNTS,
				'type' => 'account-email-controller',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
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
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 *
	 * @Secured
	 * @Secured\User(loggedIn)
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load email
		$email = $this->findEmail($request, $this->findAccount());

		// & relation entity name
		$relationEntity = strtolower(strval($request->getAttribute(Router\Routes::RELATION_ENTITY)));

		if ($relationEntity === Schemas\Emails\Email::RELATIONSHIPS_ACCOUNT) {
			return $this->buildResponse($request, $response, $email->getAccount());
		}

		return parent::readRelationship($request, $response);
	}

	/**
	 * @throws JsonApiExceptions\JsonApiError
	 */
	private function findAccount(): Entities\Accounts\Account
	{
		if ($this->user->getAccount() === null) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_FORBIDDEN,
				$this->translator->translate('//accounts-module.base.messages.forbidden.heading'),
				$this->translator->translate('//accounts-module.base.messages.forbidden.message'),
			);
		}

		return $this->user->getAccount();
	}

}
