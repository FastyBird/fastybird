<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @since          0.31.0
 *
 * @date           08.02.22
 */

namespace FastyBird\DevicesModule\Schemas\Connectors\Properties;

use FastyBird\DevicesModule;
use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Router;
use FastyBird\DevicesModule\Schemas;
use FastyBird\JsonApi\Schemas as JsonApiSchemas;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
use function strval;

/**
 * Connector property entity schema
 *
 * @template T of Entities\Connectors\Properties\Property
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
	public const RELATIONSHIPS_CONNECTOR = 'connector';

	public function __construct(private readonly Routing\IRouter $router)
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
	public function getSelfLink(
		$property,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				DevicesModule\Constants::ROUTE_NAME_CONNECTOR_PROPERTY,
				[
					Router\Routes::URL_CONNECTOR_ID => $property->getConnector()->getPlainId(),
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
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships(
		$property,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			self::RELATIONSHIPS_CONNECTOR => [
				self::RELATIONSHIP_DATA => $property->getConnector(),
				self::RELATIONSHIP_LINKS_SELF => false,
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
		if ($name === self::RELATIONSHIPS_CONNECTOR) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					DevicesModule\Constants::ROUTE_NAME_CONNECTOR,
					[
						Router\Routes::URL_ITEM_ID => $property->getConnector()->getPlainId(),
					],
				),
				false,
			);
		}

		return parent::getRelationshipRelatedLink($property, $name);
	}

}
