<?php declare(strict_types = 1);

/**
 * Dynamic.php
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

namespace FastyBird\Module\Devices\Schemas\Channels\Properties;

use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Schemas;
use FastyBird\Module\Devices\Utilities;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
use function array_merge;

/**
 * Channel property entity schema
 *
 * @extends Property<Entities\Channels\Properties\Dynamic>
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
	public const SCHEMA_TYPE = MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES . '/property/channel/' . MetadataTypes\PropertyType::TYPE_DYNAMIC;

	public function __construct(
		Routing\IRouter $router,
		Models\Channels\Properties\PropertiesRepository $propertiesRepository,
		private readonly Models\States\ChannelPropertiesRepository $propertiesStatesRepository,
	)
	{
		parent::__construct($router, $propertiesRepository);
	}

	public function getEntityClass(): string
	{
		return Entities\Channels\Properties\Dynamic::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @phpstan-param Entities\Channels\Properties\Dynamic $resource
	 *
	 * @phpstan-return iterable<string, (string|bool|int|float|array<string>|array<int, (int|float|array<int, (string|int|float|null)>|null)>|array<int, array<int, (string|array<int, (string|int|float|bool)>|null)>>|null)>
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
