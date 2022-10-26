<?php declare(strict_types = 1);

/**
 * Role.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Schemas
 * @since          0.1.0
 *
 * @date           26.05.20
 */

namespace FastyBird\Module\Accounts\Schemas\Roles;

use Exception;
use FastyBird\JsonApi\Schemas as JsonApis;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Router;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
use function count;

/**
 * Role entity schema
 *
 * @extends  JsonApis\JsonApi<Entities\Roles\Role>
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Role extends JsonApis\JsonApi
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ModuleSource::SOURCE_MODULE_ACCOUNTS . '/role';

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_PARENT = 'parent';

	public const RELATIONSHIPS_CHILDREN = 'children';

	public function __construct(
		private readonly Models\Roles\RolesRepository $rolesRepository,
		private readonly Routing\IRouter $router,
	)
	{
	}

	public function getEntityClass(): string
	{
		return Entities\Roles\Role::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @phpstan-param Entities\Roles\Role $resource
	 *
	 * @return iterable<string, string|bool>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			'name' => $resource->getName(),
			'comment' => $resource->getComment(),

			'anonymous' => $resource->isAnonymous(),
			'authenticated' => $resource->isAuthenticated(),
			'administrator' => $resource->isAdministrator(),
		];
	}

	/**
	 * @phpstan-param Entities\Roles\Role $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($resource): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				Accounts\Constants::ROUTE_NAME_ROLE,
				[
					Router\Routes::URL_ITEM_ID => $resource->getPlainId(),
				],
			),
			false,
		);
	}

	/**
	 * @phpstan-param Entities\Roles\Role $resource
	 *
	 * @phpstan-return iterable<string, Array<int, (Entities\Roles\Role|array<Entities\Roles\Role>|bool)>>
	 *
	 * @throws Exception
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		$relationships = [
			self::RELATIONSHIPS_CHILDREN => [
				self::RELATIONSHIP_DATA => $this->getChildren($resource),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];

		if ($resource->getParent() !== null) {
			$relationships[self::RELATIONSHIPS_PARENT] = [
				self::RELATIONSHIP_DATA => $resource->getParent(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			];
		}

		return $relationships;
	}

	/**
	 * @phpstan-param Entities\Roles\Role $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_PARENT && $resource->getParent() !== null) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Accounts\Constants::ROUTE_NAME_ROLE,
					[
						Router\Routes::URL_ITEM_ID => $resource->getPlainId(),
					],
				),
				false,
			);
		} elseif ($name === self::RELATIONSHIPS_CHILDREN) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Accounts\Constants::ROUTE_NAME_ROLE_CHILDREN,
					[
						Router\Routes::URL_ITEM_ID => $resource->getPlainId(),
					],
				),
				true,
				[
					'count' => count($resource->getChildren()),
				],
			);
		}

		return parent::getRelationshipRelatedLink($resource, $name);
	}

	/**
	 * @phpstan-param Entities\Roles\Role $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipSelfLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if (
			$name === self::RELATIONSHIPS_CHILDREN
			|| ($name === self::RELATIONSHIPS_PARENT && $resource->getParent() !== null)
		) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Accounts\Constants::ROUTE_NAME_ROLE_RELATIONSHIP,
					[
						Router\Routes::URL_ITEM_ID => $resource->getPlainId(),
						Router\Routes::RELATION_ENTITY => $name,

					],
				),
				false,
			);
		}

		return parent::getRelationshipSelfLink($resource, $name);
	}

	/**
	 * @phpstan-return Array<Entities\Roles\Role>
	 *
	 * @throws Exception
	 */
	private function getChildren(Entities\Roles\Role $resource): array
	{
		$findQuery = new Queries\FindRoles();
		$findQuery->forParent($resource);

		return $this->rolesRepository->findAllBy($findQuery);
	}

}
