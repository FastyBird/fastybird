<?php declare(strict_types = 1);

/**
 * Variable.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           02.06.22
 */

namespace FastyBird\Module\Devices\Documents\Devices\Properties;

use DateTimeInterface;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Types;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function array_map;
use function array_merge;

/**
 * Device variable property document
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Documents
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
#[DOC\Document(entity: Entities\Devices\Properties\Variable::class)]
final class Variable extends Property
{

	/**
	 * @param string|array<int, string>|array<int, int>|array<int, float>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null $format
	 * @param array<int, Uuid\UuidInterface> $children
	 */
	public function __construct(
		Uuid\UuidInterface $id,
		Uuid\UuidInterface $device,
		Types\PropertyCategory $category,
		string $identifier,
		string|null $name,
		MetadataTypes\DataType $dataType,
		string|null $unit = null,
		string|array|null $format = null,
		float|int|string|null $invalid = null,
		int|null $scale = null,
		int|float|null $step = null,
		Uuid\UuidInterface|string|null $valueTransformer = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly bool|float|int|string|null $value = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly bool|float|int|string|null $default = null,
		#[ObjectMapper\Rules\ArrayOf(
			new ApplicationObjectMapper\Rules\UuidValue(),
		)]
		private readonly array $children = [],
		Uuid\UuidInterface|null $owner = null,
		DateTimeInterface|null $createdAt = null,
		DateTimeInterface|null $updatedAt = null,
	)
	{
		parent::__construct(
			$id,
			$device,
			$category,
			$identifier,
			$name,
			$dataType,
			$unit,
			$format,
			$invalid,
			$scale,
			$step,
			$valueTransformer,
			$owner,
			$createdAt,
			$updatedAt,
		);
	}

	public static function getType(): string
	{
		return Entities\Devices\Properties\Variable::getType();
	}

	/**
	 * @return array<Uuid\UuidInterface>
	 */
	public function getChildren(): array
	{
		return $this->children;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		try {
			return MetadataUtilities\Value::normalizeValue(
				$this->value,
				$this->getDataType(),
				$this->getFormat(),
			);
		} catch (MetadataExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getDefault(): bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null
	{
		try {
			return MetadataUtilities\Value::normalizeValue(
				$this->default,
				$this->getDataType(),
				$this->getFormat(),
			);
		} catch (MetadataExceptions\InvalidValue) {
			return null;
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'value' => MetadataUtilities\Value::flattenValue($this->getValue()),
			'default' => MetadataUtilities\Value::flattenValue($this->getDefault()),

			'children' => array_map(
				static fn (Uuid\UuidInterface $id): string => $id->toString(),
				$this->getChildren(),
			),
		]);
	}

}
