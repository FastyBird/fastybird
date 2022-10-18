<?php declare(strict_types = 1);

/**
 * Dynamic.php
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

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Exceptions;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\Schemas;
use FastyBird\DevicesModule\Utilities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
use function array_merge;

/**
 * Connector property entity schema
 *
 * @extends Property<Entities\Connectors\Properties\Dynamic>
 *
 * @package         FastyBird:DevicesModule!
 * @subpackage      Schemas
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Dynamic extends Property
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES . '/property/connector/' . MetadataTypes\PropertyType::TYPE_DYNAMIC;

	public function __construct(
		Routing\IRouter $router,
		private readonly Models\States\ConnectorPropertiesRepository $propertiesStatesRepository,
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
	 * @phpstan-param Entities\Connectors\Properties\Dynamic $resource
	 *
	 * @phpstan-return iterable<string, (string|bool|int|float|Array<string>|Array<int, (int|float|Array<int, (string|int|float|null)>|null)>|Array<int, Array<int, (string|Array<int, (string|int|float|bool)>|null)>>|null)>
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
		try {
			$state = $this->propertiesStatesRepository->findOne($resource);

		} catch (Exceptions\NotImplemented) {
			$state = null;
		}

		$actualValue = $state !== null
			? Utilities\ValueHelper::normalizeValue(
				$resource->getDataType(),
				$state->getActualValue(),
				$resource->getFormat(),
				$resource->getInvalid(),
			)
			: null;
		$expectedValue = $state !== null
			? Utilities\ValueHelper::normalizeValue(
				$resource->getDataType(),
				$state->getExpectedValue(),
				$resource->getFormat(),
				$resource->getInvalid(),
			)
			: null;

		return array_merge((array) parent::getAttributes($resource, $context), [
			'actual_value' => Utilities\ValueHelper::flattenValue($actualValue),
			'expected_value' => Utilities\ValueHelper::flattenValue($expectedValue),
			'pending' => $state !== null && $state->isPending(),
		]);
	}

}
