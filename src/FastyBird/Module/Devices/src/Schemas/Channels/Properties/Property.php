<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @since          0.1.0
 *
 * @date           13.04.19
 */

namespace FastyBird\Module\Devices\Schemas\Channels\Properties;

use Exception;
use FastyBird\JsonApi\Schemas as JsonApiSchemas;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Schemas;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
use function count;
use function strval;

/**
 * Channel property entity schema
 *
 * @template T of Entities\Channels\Properties\Property
 * @extends  JsonApiSchemas\JsonApi<T>
 *
 * @package         FastyBird:DevicesModule!
 * @subpackage      Schemas
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Property extends JsonApiSchemas\JsonApi
{

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_CHANNEL = 'channel';

	public const RELATIONSHIPS_PARENT = 'parent';

	public const RELATIONSHIPS_CHILDREN = 'children';

	public function __construct(
		private readonly Routing\IRouter $router,
		private readonly Models\Channels\Properties\PropertiesRepository $propertiesRepository,
	)
	{
	}

	/**
	 * @phpstan-param T $resource
	 *
	 * @phpstan-return iterable<string, (string|bool|int|float|array<string>|array<int, (int|float|array<int, (string|int|float|null)>|null)>|array<int, array<int, (string|array<int, (string|int|float|bool)>|null)>>|null)>
	 *
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			'identifier' => $resource->getIdentifier(),
			'name' => $resource->getName(),
			'settable' => $resource->isSettable(),
			'queryable' => $resource->isQueryable(),
			'data_type' => strval($resource->getDataType()->getValue()),
			'unit' => $resource->getUnit(),
			'format' => $resource->getFormat()?->toArray(),
			'invalid' => $resource->getInvalid(),
			'number_of_decimals' => $resource->getNumberOfDecimals(),
		];
	}

	/**
	 * @phpstan-param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($resource): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTY,
				[
					Router\Routes::URL_DEVICE_ID => $resource->getChannel()->getDevice()->getPlainId(),
					Router\Routes::URL_CHANNEL_ID => $resource->getChannel()->getPlainId(),
					Router\Routes::URL_ITEM_ID => $resource->getPlainId(),
				],
			),
			false,
		);
	}

	/**
	 * @phpstan-param T $resource
	 *
	 * @phpstan-return iterable<string, mixed>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			self::RELATIONSHIPS_CHANNEL => [
				self::RELATIONSHIP_DATA => $resource->getChannel(),
				self::RELATIONSHIP_LINKS_SELF => false,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_PARENT => [
				self::RELATIONSHIP_DATA => $resource->getParent(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => $resource->getParent() !== null,
			],
			self::RELATIONSHIPS_CHILDREN => [
				self::RELATIONSHIP_DATA => $this->getChildren($resource),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @phpstan-param T $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_CHANNEL) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_CHANNEL,
					[
						Router\Routes::URL_DEVICE_ID => $resource->getChannel()->getDevice()->getPlainId(),
						Router\Routes::URL_ITEM_ID => $resource->getChannel()->getPlainId(),
					],
				),
				false,
			);
		} elseif ($name === self::RELATIONSHIPS_PARENT && $resource->getParent() !== null) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTY,
					[
						Router\Routes::URL_DEVICE_ID => $resource->getChannel()->getDevice()->getPlainId(),
						Router\Routes::URL_CHANNEL_ID => $resource->getChannel()->getPlainId(),
						Router\Routes::URL_ITEM_ID => $resource->getPlainId(),
					],
				),
				false,
			);
		} elseif ($name === self::RELATIONSHIPS_CHILDREN) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTY_CHILDREN,
					[
						Router\Routes::URL_DEVICE_ID => $resource->getChannel()->getDevice()->getPlainId(),
						Router\Routes::URL_CHANNEL_ID => $resource->getChannel()->getPlainId(),
						Router\Routes::URL_PROPERTY_ID => $resource->getPlainId(),
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
	 * @phpstan-param T $resource
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
			|| $name === self::RELATIONSHIPS_PARENT
		) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTY_RELATIONSHIP,
					[
						Router\Routes::URL_DEVICE_ID => $resource->getChannel()->getDevice()->getPlainId(),
						Router\Routes::URL_CHANNEL_ID => $resource->getChannel()->getPlainId(),
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
	 * @phpstan-return array<Entities\Channels\Properties\Property>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	private function getChildren(Entities\Channels\Properties\Property $property): array
	{
		$findQuery = new Queries\FindChannelProperties();
		$findQuery->forParent($property);

		return $this->propertiesRepository->findAllBy($findQuery);
	}

}
