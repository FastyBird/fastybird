<?php declare(strict_types = 1);

/**
 * Channel.php
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

namespace FastyBird\Module\Devices\Schemas\Channels;

use FastyBird\JsonApi\Schemas as JsonApiSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Schemas;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
use function count;

/**
 * Channel entity schema
 *
 * @extends JsonApiSchemas\JsonApi<Entities\Channels\Channel>
 *
 * @package         FastyBird:DevicesModule!
 * @subpackage      Schemas
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Channel extends JsonApiSchemas\JsonApi
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES . '/channel';

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_DEVICE = 'device';

	public const RELATIONSHIPS_PROPERTIES = 'properties';

	public const RELATIONSHIPS_CONTROLS = 'controls';

	public function __construct(private readonly Routing\IRouter $router)
	{
	}

	public function getEntityClass(): string
	{
		return Entities\Channels\Channel::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @phpstan-param Entities\Channels\Channel $resource
	 *
	 * @phpstan-return iterable<string, (string|array<string>|null)>
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
			'comment' => $resource->getComment(),
		];
	}

	/**
	 * @phpstan-param Entities\Channels\Channel $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($resource): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				Devices\Constants::ROUTE_NAME_CHANNEL,
				[
					Router\Routes::URL_DEVICE_ID => $resource->getDevice()->getPlainId(),
					Router\Routes::URL_ITEM_ID => $resource->getPlainId(),
				],
			),
			false,
		);
	}

	/**
	 * @phpstan-param Entities\Channels\Channel $resource
	 *
	 * @phpstan-return iterable<string, mixed>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return [
			self::RELATIONSHIPS_DEVICE => [
				self::RELATIONSHIP_DATA => $resource->getDevice(),
				self::RELATIONSHIP_LINKS_SELF => false,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_PROPERTIES => [
				self::RELATIONSHIP_DATA => $resource->getProperties(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
			self::RELATIONSHIPS_CONTROLS => [
				self::RELATIONSHIP_DATA => $resource->getControls(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @phpstan-param Entities\Channels\Channel $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink(
		$resource,
		string $name,
	): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_PROPERTIES) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTIES,
					[
						Router\Routes::URL_DEVICE_ID => $resource->getDevice()->getPlainId(),
						Router\Routes::URL_CHANNEL_ID => $resource->getPlainId(),
					],
				),
				true,
				[
					'count' => count($resource->getProperties()),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_CONTROLS) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_CHANNEL_CONTROLS,
					[
						Router\Routes::URL_DEVICE_ID => $resource->getDevice()->getPlainId(),
						Router\Routes::URL_CHANNEL_ID => $resource->getPlainId(),
					],
				),
				true,
				[
					'count' => count($resource->getControls()),
				],
			);
		} elseif ($name === self::RELATIONSHIPS_DEVICE) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE,
					[
						Router\Routes::URL_ITEM_ID => $resource->getDevice()->getPlainId(),
					],
				),
				false,
			);
		}

		return parent::getRelationshipRelatedLink($resource, $name);
	}

	/**
	 * @phpstan-param Entities\Channels\Channel $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipSelfLink(
		$resource,
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
					Devices\Constants::ROUTE_NAME_CHANNEL_RELATIONSHIP,
					[
						Router\Routes::URL_DEVICE_ID => $resource->getDevice()->getPlainId(),
						Router\Routes::URL_ITEM_ID => $resource->getPlainId(),
						Router\Routes::RELATION_ENTITY => $name,
					],
				),
				false,
			);
		}

		return parent::getRelationshipSelfLink($resource, $name);
	}

}
