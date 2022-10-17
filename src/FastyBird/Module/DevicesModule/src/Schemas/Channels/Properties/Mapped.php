<?php declare(strict_types = 1);

/**
 * Mapped.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Schemas
 * @since          0.47.0
 *
 * @date           02.04.22
 */

namespace FastyBird\DevicesModule\Schemas\Channels\Properties;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Exceptions;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\Schemas;
use FastyBird\DevicesModule\Utilities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Metadata\Types as MetadataTypes;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
use function array_merge;

/**
 * Channel property entity schema
 *
 * @extends Property<Entities\Channels\Properties\Mapped>
 *
 * @package         FastyBird:DevicesModule!
 * @subpackage      Schemas
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Mapped extends Property
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES . '/property/channel/' . MetadataTypes\PropertyType::TYPE_MAPPED;

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
		return Entities\Channels\Properties\Mapped::class;
	}

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @phpstan-param Entities\Channels\Properties\Mapped $resource
	 *
	 * @phpstan-return iterable<string, (string|bool|int|float|Array<string>|Array<int, (int|float|Array<int, (string|int|float|null)>|null)>|Array<int, Array<int, (string|Array<int, (string|int|float|bool)>|null)>>|null)>
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

		if ($resource->getParent() instanceof Entities\Channels\Properties\Dynamic) {
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
		} else {
			return array_merge((array) parent::getAttributes($resource, $context), [
				'value' => Utilities\ValueHelper::flattenValue($resource->getValue()),
				'default' => Utilities\ValueHelper::flattenValue($resource->getDefault()),
			]);
		}
	}

}
