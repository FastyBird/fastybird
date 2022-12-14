<?php declare(strict_types = 1);

/**
 * Control.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @since          0.4.0
 *
 * @date           29.09.21
 */

namespace FastyBird\Module\Devices\Schemas\Channels\Controls;

use FastyBird\JsonApi\Schemas as JsonApiSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Schemas;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;

/**
 * Channel control entity schema
 *
 * @extends JsonApiSchemas\JsonApi<Entities\Channels\Controls\Control>
 *
 * @package         FastyBird:DevicesModule!
 * @subpackage      Schemas
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Control extends JsonApiSchemas\JsonApi
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES . '/control/channel';

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_CHANNEL = 'channel';

	public function __construct(private readonly Routing\IRouter $router)
	{
	}

	public function getEntityClass(): string
	{
		return Entities\Channels\Controls\Control::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @phpstan-param Entities\Channels\Controls\Control $resource
	 *
	 * @phpstan-return iterable<string, string|bool|null>
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
		];
	}

	/**
	 * @phpstan-param Entities\Channels\Controls\Control $resource
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($resource): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				Devices\Constants::ROUTE_NAME_CHANNEL_CONTROL,
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
	 * @phpstan-param Entities\Channels\Controls\Control $resource
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
			self::RELATIONSHIPS_CHANNEL => [
				self::RELATIONSHIP_DATA => $resource->getChannel(),
				self::RELATIONSHIP_LINKS_SELF => false,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @phpstan-param Entities\Channels\Controls\Control $resource
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
		}

		return parent::getRelationshipRelatedLink($resource, $name);
	}

}
