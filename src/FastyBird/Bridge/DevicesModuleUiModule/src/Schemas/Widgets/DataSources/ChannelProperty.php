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
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Router as DevicesRouter;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
use TypeError;
use ValueError;
use function array_merge;

/**
 * Channel property data source entity schema
 *
 * @template T of Entities\Widgets\DataSources\ChannelProperty
 * @extends  Property<T>
 *
 * @package          FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage       Schemas
 * @author           Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelProperty extends Property
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

	public function __construct(
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesRepository,
		private readonly DevicesModels\States\ChannelPropertiesManager $channelPropertiesManager,
		Routing\IRouter $router,
	)
	{
		parent::__construct($router);
	}

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
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws MetadataExceptions\Mapping
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		$attributes = parent::getAttributes($resource, $context);

		if ($resource->getProperty() instanceof DevicesEntities\Channels\Properties\Dynamic) {
			$property = $this->channelsPropertiesRepository->find(
				$resource->getProperty()->getId(),
				DevicesDocuments\Channels\Properties\Dynamic::class,
			);

			$state = $property !== null ? $this->channelPropertiesManager->readState($property) : null;

			return array_merge(
				(array) $attributes,
				[
					'value' => $state !== null && $state->isValid() ? MetadataUtilities\Value::flattenValue(
						$state->getRead()->getExpectedValue() ?? $state->getRead()->getActualValue(),
					) : null,
				],
			);
		} elseif ($resource->getProperty() instanceof DevicesEntities\Channels\Properties\Mapped) {
			if ($resource->getProperty()->getParent() instanceof DevicesEntities\Channels\Properties\Dynamic) {
				$property = $this->channelsPropertiesRepository->find(
					$resource->getProperty()->getId(),
					DevicesDocuments\Channels\Properties\Mapped::class,
				);

				$state = $property !== null ? $this->channelPropertiesManager->readState($property) : null;

				return array_merge(
					(array) $attributes,
					[
						'value' => $state !== null && $state->isValid() ? MetadataUtilities\Value::flattenValue(
							$state->getRead()->getExpectedValue() ?? $state->getRead()->getActualValue(),
						) : null,
					],
				);
			} else {
				return array_merge(
					(array) $attributes,
					[
						'value' => MetadataUtilities\Value::flattenValue($resource->getProperty()->getValue()),
					],
				);
			}
		} elseif ($resource->getProperty() instanceof DevicesEntities\Channels\Properties\Variable) {
			return array_merge(
				(array) $attributes,
				[
					'value' => MetadataUtilities\Value::flattenValue($resource->getProperty()->getValue()),
				],
			);
		}

		return $attributes;
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
