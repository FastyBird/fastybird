<?php declare(strict_types = 1);

/**
 * AccountIdentitiesV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 * @since          0.1.0
 *
 * @date           31.03.20
 */

namespace FastyBird\Module\Accounts\Controllers;

use Doctrine;
use Exception;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Module\Accounts\Controllers;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Router;
use FastyBird\Module\Accounts\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use IPub\JsonAPIDocument\Objects as JsonAPIDocumentObjects;
use Nette\Utils;
use Psr\Http\Message;
use RuntimeException;
use Throwable;
use function is_scalar;
use function strtolower;
use function strval;

/**
 * Account identity controller
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AccountIdentitiesV1 extends BaseV1
{

	use Controllers\Finders\TIdentity;

	public function __construct(
		protected readonly Models\Identities\IdentitiesRepository $identitiesRepository,
		private readonly Models\Identities\IdentitiesManager $identitiesManager,
	)
	{
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\QueryException
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
		$findQuery = new Queries\FindIdentities();
		$findQuery->forAccount($this->findAccount());

		$identities = $this->identitiesRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $identities);
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
		// Find identity
		$identity = $this->findIdentity($request, $this->findAccount());

		return $this->buildResponse($request, $response, $identity);
	}

	/**
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
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

		$identity = $this->findIdentity($request, $this->findAccount());

		$this->validateIdentifier($request, $document);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if ($document->getResource()->getType() === Schemas\Identities\Identity::SCHEMA_TYPE) {
				$attributes = $document->getResource()->getAttributes();

				$passwordAttribute = $attributes->get('password');

				if (
					!$passwordAttribute instanceof JsonAPIDocumentObjects\IStandardObject
					|| !$passwordAttribute->has('current')
					|| !is_scalar($passwordAttribute->get('current'))
				) {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						$this->translator->translate('//accounts-module.base.messages.missingAttribute.heading'),
						$this->translator->translate('//accounts-module.base.messages.missingAttribute.message'),
						[
							'pointer' => '/data/attributes/password/current',
						],
					);
				}

				if (!$passwordAttribute->has('new') || !is_scalar($passwordAttribute->get('new'))) {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						$this->translator->translate('//accounts-module.base.messages.missingAttribute.heading'),
						$this->translator->translate('//accounts-module.base.messages.missingAttribute.message'),
						[
							'pointer' => '/data/attributes/password/new',
						],
					);
				}

				if (!$identity->verifyPassword((string) $passwordAttribute->get('current'))) {
					throw new JsonApiExceptions\JsonApiError(
						StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
						$this->translator->translate('//accounts-module.base.messages.invalidAttribute.heading'),
						$this->translator->translate('//accounts-module.base.messages.invalidAttribute.message'),
						[
							'pointer' => '/data/attributes/password/current',
						],
					);
				}

				$update = new Utils\ArrayHash();
				$update->offsetSet('password', (string) $passwordAttribute->get('new'));

				// Update item in database
				$this->identitiesManager->update($identity, $update);

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
				'source' => 'accounts-module-account-identities-controller',
				'type' => 'update',
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
		$identity = $this->findIdentity($request, $this->findAccount());

		// & relation entity name
		$relationEntity = strtolower(strval($request->getAttribute(Router\Routes::RELATION_ENTITY)));

		if ($relationEntity === Schemas\Identities\Identity::RELATIONSHIPS_ACCOUNT) {
			return $this->buildResponse($request, $response, $identity->getAccount());
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
