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

namespace FastyBird\DevicesModule\Schemas\Devices\Properties;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Exceptions;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\Schemas;
use FastyBird\DevicesModule\Utilities;
use FastyBird\Metadata\Types as MetadataTypes;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;
use function array_merge;

/**
 * Device property entity schema
 *
 * @extends Property<Entities\Devices\Properties\Mapped>
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
	public const SCHEMA_TYPE = MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES . '/property/device/' . MetadataTypes\PropertyType::TYPE_MAPPED;

	public function __construct(
		Routing\IRouter $router,
		Models\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly Models\States\DevicePropertiesRepository $propertiesStatesRepository,
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
	 * @return iterable<string, (string|bool|int|float|Array<string>|Array<int, (int|float|Array<int, (string|int|float|null)>|null)>|Array<int, Array<int, (string|Array<int, (string|int|float|bool)>|null)>>|null)>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$property,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		try {
			$state = $this->propertiesStatesRepository->findOne($property);

		} catch (Exceptions\NotImplemented) {
			$state = null;
		}

		if ($property->getParent() instanceof Entities\Devices\Properties\Dynamic) {
			$actualValue = $state !== null
				? Utilities\ValueHelper::normalizeValue(
					$property->getDataType(),
					$state->getActualValue(),
					$property->getFormat(),
					$property->getInvalid(),
				)
				: null;
			$expectedValue = $state !== null
				? Utilities\ValueHelper::normalizeValue(
					$property->getDataType(),
					$state->getExpectedValue(),
					$property->getFormat(),
					$property->getInvalid(),
				)
				: null;

			return array_merge((array) parent::getAttributes($property, $context), [
				'actual_value' => Utilities\ValueHelper::flattenValue($actualValue),
				'expected_value' => Utilities\ValueHelper::flattenValue($expectedValue),
				'pending' => $state !== null && $state->isPending(),
			]);
		} else {
			return array_merge((array) parent::getAttributes($property, $context), [
				'value' => Utilities\ValueHelper::flattenValue($property->getValue()),
				'default' => Utilities\ValueHelper::flattenValue($property->getDefault()),
			]);
		}
	}

}
