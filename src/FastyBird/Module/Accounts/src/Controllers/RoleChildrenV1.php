<?php declare(strict_types = 1);

/**
 * RoleChildrenV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           03.06.20
 */

namespace FastyBird\Module\Accounts\Controllers;

use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Accounts\Controllers;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Psr\Http\Message;

/**
 * Role children API controller
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @Secured
 * @Secured\User(loggedIn)
 */
final class RoleChildrenV1 extends BaseV1
{

	use Controllers\Finders\TRole;

	public function __construct(private readonly Models\Entities\Roles\RolesRepository $rolesRepository)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApi
	 */
	public function index(
		Message\ServerRequestInterface $request,
		Message\ResponseInterface $response,
	): Message\ResponseInterface
	{
		// At first, try to load role
		$role = $this->findRole($request);

		$findQuery = new Queries\Entities\FindRoles();
		$findQuery->forParent($role);

		$children = $this->rolesRepository->getResultSet($findQuery);

		// @phpstan-ignore-next-line
		return $this->buildResponse($request, $response, $children);
	}

}
