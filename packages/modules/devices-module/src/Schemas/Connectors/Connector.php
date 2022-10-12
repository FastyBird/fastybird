<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @since          0.1.0
 *
 * @date           17.01.21
 */

namespace FastyBird\DevicesModule\Schemas\Connectors;

use FastyBird\DevicesModule;
use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Router;
use FastyBird\DevicesModule\Schemas;
use FastyBird\JsonApi\Schemas as JsonApiSchemas;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
use function count;

/**
 * Connector entity schema
 *
 * @template T of Entities\Connectors\Connector
 * @extends  JsonApiSchemas\JsonApi<T>
 *
 * @package         FastyBird:DevicesModule!
 * @subpackage      Schemas
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Connector extends JsonApiSchemas\JsonApi
{

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_DEVICES = 'devices';

	public const RELATIONSHIPS_PROPERTIES = 'properties';

	public const RELATIONSHIPS_CONTROLS = 'controls';

	public function __construct(private readonly Routing\IRouter $router)
	{
	}

	/**
	 * @return iterable<string, (string|Array<string>|bool|null)>
	 *
	 * @phpstan-param T $connector
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$connector,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			'identifier' => $connector->getIdentifier(),
			'name' => $connector->getName(),
			'comment' => $connector->getComment(),

			'enabled' => $connector->isEnabled(),
		];
	}

	/**
	 * @phpstan-param T $connector
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($connector): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				DevicesModule\Constants::ROUTE_NAME_CONNECTOR,
				[
					Router\Routes::URL_ITEM_ID => $connector->getPlainId(),
				],
			),
			false,
		);
	}

	/**
	 * @return iterable<string, mixed>
	 *
	 * @phpstan-param T $connector
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships(
		$connector,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			self::RELATIONSHIPS_DEVICES => [
				self::RELATIONSHIP_DATA => $connector->getDevices(),
				self::RELATIONSHIP_LINKS_SELF => false,
				self::RELATIONSHIP_LINKS_RELATED => false,
			],
			self::RELATIONSHIPS_PROPERTIES => [
				self::RELATIONSHIP_DATA => $connector->getProperties(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_CONTROLS => [
				self::RELATIONSHIP_DATA => $connector->getControls(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @phpstan-param T $connector
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink(
		$connector,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_PROPERTIES) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					DevicesModule\Constants::ROUTE_NAME_CONNECTOR_PROPERTIES,
					[
						Router\Routes::URL_CONNECTOR_ID => $connector->getPlainId(),
					],
				),
				true,
				[
					'count' => count($connector->getProperties()),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_CONTROLS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					DevicesModule\Constants::ROUTE_NAME_CONNECTOR_CONTROLS,
					[
						Router\Routes::URL_CONNECTOR_ID => $connector->getPlainId(),
					],
				),
				true,
				[
					'count' => count($connector->getControls()),
				],
			);
		}

		return parent::getRelationshipRelatedLink($connector, $name);
	}

	/**
	 * @phpstan-param T $connector
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipSelfLink(
		$connector,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if (
			$name === self::RELATIONSHIPS_PROPERTIES
			|| $name === self::RELATIONSHIPS_CONTROLS
		) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					DevicesModule\Constants::ROUTE_NAME_CONNECTOR_RELATIONSHIP,
					[
						Router\Routes::URL_ITEM_ID => $connector->getPlainId(),
						Router\Routes::RELATION_ENTITY => $name,

					],
				),
				false,
			);
		}

		return parent::getRelationshipSelfLink($connector, $name);
	}

}
