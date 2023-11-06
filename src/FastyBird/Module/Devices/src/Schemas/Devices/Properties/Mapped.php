<?php declare(strict_types = 1);

/**
 * Mapped.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           02.04.22
 */

namespace FastyBird\Module\Devices\Schemas\Devices\Properties;

use DateTimeInterface;
use Exception;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Schemas;
use FastyBird\Module\Devices\Utilities;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
use function array_merge;
use function is_bool;

/**
 * Device property entity schema
 *
 * @template T of Entities\Devices\Properties\Mapped
 * @extends Property<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Mapped extends Property
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES . '/property/device/' . MetadataTypes\PropertyType::TYPE_MAPPED;

	public function __construct(
		Routing\IRouter $router,
		Models\Entities\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly Utilities\DevicePropertiesStates $devicePropertiesStates,
	)
	{
		parent::__construct($router, $propertiesRepository);
	}

	public function getEntityClass(): string
	{
		return Entities\Devices\Properties\Mapped::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, (string|bool|int|float|array<string>|array<int, (int|float|array<int, (string|int|float|null)>|null)>|array<int, array<int, (string|array<int, (string|int|float|bool)>|null)>>|null)>
	 *
	 * @throws Exceptions\InvalidState
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
		$state = $this->devicePropertiesStates->readValue($resource);

		return $resource->getParent() instanceof Entities\Devices\Properties\Dynamic ? array_merge(
			(array) parent::getAttributes($resource, $context),
			[
				'settable' => $resource->isSettable(),
				'queryable' => $resource->isQueryable(),
				'actual_value' => Utilities\ValueHelper::flattenValue($state?->getActualValue()),
				'expected_value' => Utilities\ValueHelper::flattenValue($state?->getExpectedValue()),
				'pending' => $state !== null ? (is_bool($state->getPending())
					? $state->getPending() : $state->getPending()->format(DateTimeInterface::ATOM))
					: null,
				'is_valid' => $state !== null && $state->isValid(),
			],
		) : array_merge((array) parent::getAttributes($resource, $context), [
			'value' => Utilities\ValueHelper::flattenValue($resource->getValue()),
			'default' => Utilities\ValueHelper::flattenValue($resource->getDefault()),
		]);
	}

	/**
	 * @param T $resource
	 *
	 * @return iterable<string, mixed>
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
		return array_merge((array) parent::getRelationships($resource, $context), [
			self::RELATIONSHIPS_PARENT => [
				self::RELATIONSHIP_DATA => $resource->getParent(),
				self::RELATIONSHIP_LINKS_SELF => true,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		]);
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
		if ($name === self::RELATIONSHIPS_PARENT) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE_PROPERTY,
					[
						Router\ApiRoutes::URL_DEVICE_ID => $resource->getDevice()->getPlainId(),
						Router\ApiRoutes::URL_ITEM_ID => $resource->getPlainId(),
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
		if ($name === self::RELATIONSHIPS_PARENT) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					Devices\Constants::ROUTE_NAME_DEVICE_PROPERTY_RELATIONSHIP,
					[
						Router\ApiRoutes::URL_DEVICE_ID => $resource->getDevice()->getPlainId(),
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
