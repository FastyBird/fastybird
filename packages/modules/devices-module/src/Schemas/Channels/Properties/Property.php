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

namespace FastyBird\DevicesModule\Schemas\Channels\Properties;

use FastyBird\DevicesModule;
use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\Queries;
use FastyBird\DevicesModule\Router;
use FastyBird\DevicesModule\Schemas;
use FastyBird\JsonApi\Schemas as JsonApiSchemas;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
use Throwable;
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
	 * @return iterable<string, (string|bool|int|float|Array<string>|Array<int, (int|float|Array<int, (string|int|float|null)>|null)>|Array<int, Array<int, (string|Array<int, (string|int|float|bool)>|null)>>|null)>
	 *
	 * @phpstan-param T $property
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$property,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			'identifier' => $property->getIdentifier(),
			'name' => $property->getName(),
			'settable' => $property->isSettable(),
			'queryable' => $property->isQueryable(),
			'data_type' => strval($property->getDataType()->getValue()),
			'unit' => $property->getUnit(),
			'format' => $property->getFormat()?->toArray(),
			'invalid' => $property->getInvalid(),
			'number_of_decimals' => $property->getNumberOfDecimals(),
		];
	}

	/**
	 * @phpstan-param T $property
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($property): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				DevicesModule\Constants::ROUTE_NAME_CHANNEL_PROPERTY,
				[
					Router\Routes::URL_DEVICE_ID => $property->getChannel()->getDevice()->getPlainId(),
					Router\Routes::URL_CHANNEL_ID => $property->getChannel()->getPlainId(),
					Router\Routes::URL_ITEM_ID => $property->getPlainId(),
				],
			),
			false,
		);
	}

	/**
	 * @return iterable<string, mixed>
	 *
	 * @phpstan-param T $property
	 *
	 * @throws Throwable
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships(
		$property,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			self::RELATIONSHIPS_CHANNEL => [
				self::RELATIONSHIP_DATA => $property->getChannel(),
				self::RELATIONSHIP_LINKS_SELF => false,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_PARENT => [
				self::RELATIONSHIP_DATA => $property->getParent(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => $property->getParent() !== null,
			],
			self::RELATIONSHIPS_CHILDREN => [
				self::RELATIONSHIP_DATA => $this->getChildren($property),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @phpstan-param T $property
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink(
		$property,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_CHANNEL) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					DevicesModule\Constants::ROUTE_NAME_CHANNEL,
					[
						Router\Routes::URL_DEVICE_ID => $property->getChannel()->getDevice()->getPlainId(),
						Router\Routes::URL_ITEM_ID => $property->getChannel()->getPlainId(),
					],
				),
				false,
			);
		} elseif ($name === self::RELATIONSHIPS_PARENT && $property->getParent() !== null) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					DevicesModule\Constants::ROUTE_NAME_CHANNEL_PROPERTY,
					[
						Router\Routes::URL_DEVICE_ID => $property->getChannel()->getDevice()->getPlainId(),
						Router\Routes::URL_CHANNEL_ID => $property->getChannel()->getPlainId(),
						Router\Routes::URL_ITEM_ID => $property->getPlainId(),
					],
				),
				false,
			);
		} elseif ($name === self::RELATIONSHIPS_CHILDREN) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					DevicesModule\Constants::ROUTE_NAME_CHANNEL_PROPERTY_CHILDREN,
					[
						Router\Routes::URL_DEVICE_ID => $property->getChannel()->getDevice()->getPlainId(),
						Router\Routes::URL_CHANNEL_ID => $property->getChannel()->getPlainId(),
						Router\Routes::URL_PROPERTY_ID => $property->getPlainId(),
					],
				),
				true,
				[
					'count' => count($property->getChildren()),
				],
			);
		}

		return parent::getRelationshipRelatedLink($property, $name);
	}

	/**
	 * @phpstan-param T $property
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipSelfLink(
		$property,
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
					DevicesModule\Constants::ROUTE_NAME_CHANNEL_PROPERTY_RELATIONSHIP,
					[
						Router\Routes::URL_DEVICE_ID => $property->getChannel()->getDevice()->getPlainId(),
						Router\Routes::URL_CHANNEL_ID => $property->getChannel()->getPlainId(),
						Router\Routes::URL_ITEM_ID => $property->getPlainId(),
						Router\Routes::RELATION_ENTITY => $name,

					],
				),
				false,
			);
		}

		return parent::getRelationshipSelfLink($property, $name);
	}

	/**
	 * @return Array<Entities\Channels\Properties\Property>
	 *
	 * @throws Throwable
	 */
	private function getChildren(Entities\Channels\Properties\Property $property): array
	{
		$findQuery = new Queries\FindChannelProperties();
		$findQuery->forParent($property);

		return $this->propertiesRepository->findAllBy($findQuery);
	}

}
