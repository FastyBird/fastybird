<?php declare(strict_types = 1);

/**
 * Dynamic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           08.02.22
 */

namespace FastyBird\Module\Devices\Schemas\Connectors\Properties;

use DateTimeInterface;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Schemas;
use FastyBird\Module\Devices\Utilities;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
use function array_merge;
use function is_bool;

/**
 * Connector property entity schema
 *
 * @template T of Entities\Connectors\Properties\Dynamic
 * @extends Property<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Dynamic extends Property
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ModuleSource::DEVICES . '/property/connector/' . MetadataTypes\PropertyType::DYNAMIC;

	public function __construct(
		Routing\IRouter $router,
		private readonly Utilities\ConnectorPropertiesStates $connectorPropertiesStates,
	)
	{
		parent::__construct($router);
	}

	public function getEntityClass(): string
	{
		return Entities\Connectors\Properties\Dynamic::class;
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		$state = $this->connectorPropertiesStates->readValue($resource);

		return array_merge((array) parent::getAttributes($resource, $context), [
			'settable' => $resource->isSettable(),
			'queryable' => $resource->isQueryable(),
			'actual_value' => MetadataUtilities\Value::flattenValue($state?->getActualValue()),
			'expected_value' => MetadataUtilities\Value::flattenValue($state?->getExpectedValue()),
			'pending' => $state !== null
				? (
					is_bool($state->getPending())
						? $state->getPending()
						: $state->getPending()->format(DateTimeInterface::ATOM)
				)
				: false,
			'is_valid' => $state !== null && $state->isValid(),
		]);
	}

}
