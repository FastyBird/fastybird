<?php declare(strict_types = 1);

/**
 * ChannelProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           04.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\Schemas\Widgets\DataSources;

use FastyBird\Bridge\DevicesModuleUiModule\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Router as DevicesRouter;
use FastyBird\Module\Ui\Schemas as UiSchemas;
use Neomerx\JsonApi;
use function array_merge;

/**
 * Channel property data source entity schema
 *
 * @template T of Entities\Widgets\DataSources\ChannelProperty
 * @extends  UiSchemas\Widgets\DataSources\DataSource<T>
 *
 * @package          FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage       Schemas
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelProperty extends UiSchemas\Widgets\DataSources\DataSource
{

	/**
	 * Define entity schema type string
	 */
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const SCHEMA_TYPE = MetadataTypes\Sources\Bridge::DEVICES_MODULE_UI_MODULE->value . '/data-source/' . Entities\Widgets\DataSources\ChannelProperty::TYPE;

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_PROPERTY = 'property';

	public function getEntityClass(): string
	{
		return Entities\Widgets\DataSources\ChannelProperty::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, mixed>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getRelationships($resource, $context), [
			self::RELATIONSHIPS_PROPERTY => [
				self::RELATIONSHIP_DATA => $resource->getProperty(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => false,
			],
		]);
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
		if ($name === self::RELATIONSHIPS_PROPERTY) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_CHANNEL_PROPERTY,
					[
						DevicesRouter\ApiRoutes::URL_DEVICE_ID => $resource->getProperty()->getChannel()->getDevice()->getId()->toString(),
						DevicesRouter\ApiRoutes::URL_CHANNEL_ID => $resource->getProperty()->getChannel()->getId()->toString(),
						DevicesRouter\ApiRoutes::URL_ITEM_ID => $resource->getProperty()->getId()->toString(),
					],
				),
				false,
			);
		}

		return parent::getRelationshipSelfLink($resource, $name);
	}

}
