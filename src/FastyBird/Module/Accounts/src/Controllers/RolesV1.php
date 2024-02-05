<?php declare(strict_types = 1);

/**
 * RolesV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           02.04.20
 */

namespace FastyBird\Module\Accounts\Controllers;

use Doctrine;
use Exception;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Accounts\Controllers;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Hydrators;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Router;
use FastyBird\Module\Accounts\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Psr\Http\Message;
use Throwable;
use function strtolower;
use function strval;

/**
 * ACL roles controller
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured
 * @Secured\User(loggedIn)
 */
final class RolesV1 extends BaseV1
{

	use Controllers\Finders\TRole;

	public function __construct(
		private readonly Models\Entities\Roles\RolesRepository $rolesRepository,
		private readonly Models\Entities\Roles\RolesManager $rolesManager,
		private readonly Hydrators\Roles\Role $roleHydrator,
	)
	{
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exception
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$findQuery = new Queries\Entities\FindRoles();

		$roles = $this->rolesRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $roles);
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
		$role = $this->findRole($request);

		return $this->buildResponse($request, $response, $role);
	}

	/**
	 * @throws Doctrine\DBAL\ConnectionException
	 * @throws Doctrine\DBAL\Exception
	 * @throws Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 * @throws JsonApiExceptions\JsonApi
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

		$role = $this->findRole($request);

		$this->validateIdentifier($request, $document);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if ($document->getResource()->getType() === Schemas\Roles\Role::SCHEMA_TYPE) {
				$updateRoleData = $this->roleHydrator->hydrate($document, $role);

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

			$role = $this->rolesManager->update($role, $updateRoleData);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

		} catch (JsonApiExceptions\JsonApi $ex) {
			throw $ex;
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Module::ACCOUNTS,
					'type' => 'roles-controller',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

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

		return $this->buildResponse($request, $response, $role);
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exception
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function readRelationship(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		$role = $this->findRole($request);

		$relationEntity = strtolower(strval($request->getAttribute(Router\ApiRoutes::RELATION_ENTITY)));

		if ($relationEntity === Schemas\Roles\Role::RELATIONSHIPS_PARENT) {
			return $this->buildResponse($request, $response, $role->getParent());
		} elseif ($relationEntity === Schemas\Roles\Role::RELATIONSHIPS_CHILDREN) {
			return $this->buildResponse($request, $response, $role->getChildren());
		}

		return parent::readRelationship($request, $response);
	}

}
