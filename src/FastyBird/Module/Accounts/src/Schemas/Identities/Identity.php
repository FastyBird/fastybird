<?php declare(strict_types = 1);

/**
 * Identity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           03.04.20
 */

namespace FastyBird\Module\Accounts\Schemas\Identities;

use FastyBird\JsonApi\Schemas as JsonApis;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Router;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;

/**
 * Identity entity schema
 *
 * @template T of Entities\Identities\Identity
 * @extends JsonApis\JsonApi<T>
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Identity extends JsonApis\JsonApi
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Module::ACCOUNTS->value . '/identity';

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_ACCOUNT = 'account';

	public function __construct(private readonly Routing\IRouter $router)
	{
	}

	public function getEntityClass(): string
	{
		return Entities\Identities\Identity::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, string>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			'uid' => $resource->getUid(),
			'state' => $resource->getState()->value,
		];
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($resource): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				Accounts\Constants::ROUTE_NAME_ACCOUNT_IDENTITY,
				[
					Router\ApiRoutes::URL_ACCOUNT_ID => $resource->getAccount()->getPlainId(),
					Router\ApiRoutes::URL_ITEM_ID => $resource->getPlainId(),
				],
			),
			false,
		);
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, array<int, (Entities\Accounts\Account|bool)>>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			self::RELATIONSHIPS_ACCOUNT => [
				self::RELATIONSHIP_DATA => $resource->getAccount(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_ACCOUNT) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Accounts\Constants::ROUTE_NAME_ACCOUNT,
					[
						Router\ApiRoutes::URL_ITEM_ID => $resource->getAccount()->getPlainId(),
					],
				),
				false,
			);
		}

		return parent::getRelationshipRelatedLink($resource, $name);
	}

	/**
	 * @param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipSelfLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_ACCOUNT) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Accounts\Constants::ROUTE_NAME_ACCOUNT_IDENTITY_RELATIONSHIP,
					[
						Router\ApiRoutes::URL_ACCOUNT_ID => $resource->getAccount()->getPlainId(),
						Router\ApiRoutes::URL_ITEM_ID => $resource->getPlainId(),
						Router\ApiRoutes::RELATION_ENTITY => $name,
					],
				),
				false,
			);
		}

		return parent::getRelationshipSelfLink($resource, $name);
	}

}
