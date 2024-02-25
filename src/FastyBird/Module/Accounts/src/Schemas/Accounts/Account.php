<?php declare(strict_types = 1);

/**
 * Account.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           19.08.20
 */

namespace FastyBird\Module\Accounts\Schemas\Accounts;

use DateTimeInterface;
use FastyBird\JsonApi\Schemas as JsonApis;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Accounts;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Router;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
use function count;
use function intval;
use function strval;

/**
 * Account entity schema
 *
 * @template T of Entities\Accounts\Account
 * @extends JsonApis\JsonApi<T>
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Account extends JsonApis\JsonApi
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\Sources\Module::ACCOUNTS->value . '/account';

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_IDENTITIES = 'identities';

	public const RELATIONSHIPS_ROLES = 'roles';

	public const RELATIONSHIPS_EMAILS = 'emails';

	public function __construct(protected readonly Routing\IRouter $router)
	{
	}

	public function getEntityClass(): string
	{
		return Entities\Accounts\Account::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, (int|string|array<string, string|null>|null)>
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

			'details' => [
				'first_name' => $resource->getDetails()->getFirstName(),
				'last_name' => $resource->getDetails()->getLastName(),
				'middle_name' => $resource->getDetails()->getMiddleName(),
			],

			'language' => $resource->getLanguage(),

			'week_start' => intval($resource->getParam('datetime.week_start', 1)),
			'datetime' => [
				'timezone' => strval($resource->getParam('datetime.zone', 'Europe/London')),
				'date_format' => strval($resource->getParam('datetime.format.date', 'DD.MM.YYYY')),
				'time_format' => strval($resource->getParam('datetime.format.time', 'HH:mm')),
			],

			'state' => $resource->getState()->value,

			'last_visit' => $resource->getLastVisit()?->format(DateTimeInterface::ATOM),
			'registered' => $resource->getCreatedAt()?->format(DateTimeInterface::ATOM),
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
				Accounts\Constants::ROUTE_NAME_ACCOUNT,
				[
					Router\ApiRoutes::URL_ITEM_ID => $resource->getPlainId(),
				],
			),
			false,
		);
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, array<int, (array<Entities\Identities\Identity>|array<Entities\Roles\Role>|array<Entities\Emails\Email>|bool)>>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			self::RELATIONSHIPS_IDENTITIES => [
				self::RELATIONSHIP_DATA => $resource->getIdentities(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_ROLES => [
				self::RELATIONSHIP_DATA => $resource->getRoles(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => false,
			],
			self::RELATIONSHIPS_EMAILS => [
				self::RELATIONSHIP_DATA => $resource->getEmails(),
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
		if ($name === self::RELATIONSHIPS_IDENTITIES) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Accounts\Constants::ROUTE_NAME_ACCOUNT_IDENTITIES,
					[
						Router\ApiRoutes::URL_ACCOUNT_ID => $resource->getPlainId(),
					],
				),
				true,
				[
					'count' => count($resource->getIdentities()),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_EMAILS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Accounts\Constants::ROUTE_NAME_ACCOUNT_EMAILS,
					[
						Router\ApiRoutes::URL_ACCOUNT_ID => $resource->getPlainId(),
					],
				),
				true,
				[
					'count' => count($resource->getEmails()),
				],
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
		if (
			$name === self::RELATIONSHIPS_IDENTITIES
			|| $name === self::RELATIONSHIPS_ROLES
		) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Accounts\Constants::ROUTE_NAME_ACCOUNT_RELATIONSHIP,
					[
						Router\ApiRoutes::URL_ITEM_ID => $resource->getPlainId(),
						Router\ApiRoutes::RELATION_ENTITY => $name,
					],
				),
				false,
			);
		} elseif ($name === self::RELATIONSHIPS_EMAILS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Accounts\Constants::ROUTE_NAME_ACCOUNT_RELATIONSHIP,
					[
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
