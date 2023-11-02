<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           07.08.20
 */

namespace FastyBird\Module\Devices\Entities;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Utilities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;
use function array_map;
use function array_merge;
use function assert;
use function floatval;
use function implode;
use function in_array;
use function intval;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function preg_match;
use function preg_replace;
use function sprintf;
use function strtolower;
use function strval;

abstract class Property implements Entity,
	EntityParams,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated
{

	use TEntity;
	use TEntityParams;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private const MATCH_IP_ADDRESS = '/^((?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])[.]){3}(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/';

	private const MATCH_MAC_ADDRESS = '/^([0-9a-fA-F][0-9a-fA-F]){1}(:([0-9a-fA-F][0-9a-fA-F])){5,7}$/';

	/**
	 * @ORM\Id
	 * @ORM\Column(type="uuid_binary", name="property_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @var MetadataTypes\PropertyCategory
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	 *
	 * @Enum(class=MetadataTypes\PropertyCategory::class)
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string_enum", name="property_category", length=100, nullable=true, options={"default": "generic"})
	 */
	protected $category;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="string", name="property_identifier", length=50, nullable=false)
	 */
	protected string $identifier;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string", name="property_name", nullable=true, options={"default": null})
	 */
	protected string|null $name = null;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="boolean", name="property_settable", nullable=false, options={"default": false})
	 */
	protected bool $settable = false;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="boolean", name="property_queryable", nullable=false, options={"default": false})
	 */
	protected bool $queryable = false;

	/**
	 * @var MetadataTypes\DataType
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	 *
	 * @Enum(class=MetadataTypes\DataType::class)
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string_enum", name="property_data_type", length=100, nullable=true, options={"default": "unknown"})
	 */
	protected $dataType;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string", name="property_unit", length=20, nullable=true, options={"default": null})
	 */
	protected string|null $unit = null;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="text", name="property_format", nullable=true, options={"default": null})
	 */
	protected string|null $format = null;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string", name="property_invalid", nullable=true, options={"default": null})
	 */
	protected string|null $invalid = null;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="integer", name="property_scale", nullable=true, options={"default": null})
	 */
	protected int|null $scale = null;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="float", name="property_step", nullable=true, options={"default": null})
	 */
	protected float|null $step = null;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string", name="property_value", nullable=true, options={"default": null})
	 */
	protected string|null $value = null;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string", name="property_default", nullable=true, options={"default": null})
	 */
	protected string|null $default = null;

	public function __construct(
		string $identifier,
		Uuid\UuidInterface|null $id = null,
	)
	{
		// @phpstan-ignore-next-line
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->identifier = $identifier;

		$this->category = MetadataTypes\PropertyCategory::get(MetadataTypes\PropertyCategory::CATEGORY_GENERIC);
		$this->dataType = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UNKNOWN);

		// Static property can not be set or read from device/channel property
		if ($this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_VARIABLE)) {
			$this->settable = false;
			$this->queryable = false;
		}
	}

	abstract public function getType(): MetadataTypes\PropertyType;

	public function getCategory(): MetadataTypes\PropertyCategory
	{
		return $this->category;
	}

	public function setCategory(MetadataTypes\PropertyCategory $category): void
	{
		$this->category = $category;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function setName(string|null $name): void
	{
		$this->name = $name;
	}

	public function isSettable(): bool
	{
		return $this->settable;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function setSettable(bool $settable): void
	{
		if ($settable && $this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_VARIABLE)) {
			throw new Exceptions\InvalidArgument('Variable type property can not be settable');
		}

		$this->settable = $settable;
	}

	public function isQueryable(): bool
	{
		return $this->queryable;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function setQueryable(bool $queryable): void
	{
		if ($queryable && $this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_VARIABLE)) {
			throw new Exceptions\InvalidArgument('Variable type property can not be queryable');
		}

		$this->queryable = $queryable;
	}

	public function getDataType(): MetadataTypes\DataType
	{
		return $this->dataType;
	}

	public function setDataType(MetadataTypes\DataType $dataType): void
	{
		$this->dataType = $dataType;
	}

	public function getUnit(): string|null
	{
		return $this->unit;
	}

	public function setUnit(string|null $unit): void
	{
		$this->unit = $unit;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 */
	public function getFormat(): MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|MetadataValueObjects\EquationFormat|null
	{
		return $this->buildFormat($this->format);
	}

	/**
	 * @param string|array<int, string>|array<int, bool|string|int|float|array<int, bool|string|int|float>|Utils\ArrayHash|null>|array<int, array<int, string|array<int, string|int|float|bool>|Utils\ArrayHash|null>>|null $format
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 */
	public function setFormat(array|string|null $format): void
	{
		if (is_string($format)) {
			if ($this->buildFormat($format) === null) {
				throw new Exceptions\InvalidArgument('Provided property format is not valid');
			}

			$this->format = $format;

			return;
		} elseif (is_array($format)) {
			if (
				in_array($this->dataType->getValue(), [
					MetadataTypes\DataType::DATA_TYPE_CHAR,
					MetadataTypes\DataType::DATA_TYPE_UCHAR,
					MetadataTypes\DataType::DATA_TYPE_SHORT,
					MetadataTypes\DataType::DATA_TYPE_USHORT,
					MetadataTypes\DataType::DATA_TYPE_INT,
					MetadataTypes\DataType::DATA_TYPE_UINT,
					MetadataTypes\DataType::DATA_TYPE_FLOAT,
				], true)
			) {
				$plainFormat = implode(':', array_map(static function ($item): string {
					if (is_array($item) || $item instanceof Utils\ArrayHash) {
						return implode(
							'|',
							array_map(
								static fn ($part): string|int|float => is_array($part) ? implode($part) : $part,
								(array) $item,
							),
						);
					}

					return strval($item);
				}, $format));

				if (preg_match(Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE, $plainFormat) === 1) {
					$this->format = $plainFormat;

					return;
				}

				throw new Exceptions\InvalidArgument('Provided property format is not valid');
			} elseif (
				in_array($this->dataType->getValue(), [
					MetadataTypes\DataType::DATA_TYPE_ENUM,
					MetadataTypes\DataType::DATA_TYPE_BUTTON,
					MetadataTypes\DataType::DATA_TYPE_SWITCH,
					MetadataTypes\DataType::DATA_TYPE_COVER,
				], true)
			) {
				$plainFormat = implode(',', array_map(static function ($item): string {
					if (is_array($item) || $item instanceof Utils\ArrayHash) {
						return implode(
							':',
							array_map(
								static fn (string|array|int|float|bool|Utils\ArrayHash|null $part): string => is_array(
									$part,
								) || $part instanceof Utils\ArrayHash
									? implode('|', (array) $part)
									: ($part !== null ? strval(
										$part,
									) : ''),
								(array) $item,
							),
						);
					}

					return strval($item);
				}, $format));

				if (
					preg_match(Metadata\Constants::VALUE_FORMAT_STRING_ENUM, $plainFormat) === 1
					|| preg_match(Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM, $plainFormat) === 1
				) {
					$this->format = $plainFormat;

					return;
				}

				throw new Exceptions\InvalidArgument('Provided property format is not valid');
			}
		}

		$this->format = null;
	}

	public function getInvalid(): float|int|string|null
	{
		if ($this->invalid === null) {
			return null;
		}

		if (
			$this->dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_CHAR)
			|| $this->dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UCHAR)
			|| $this->dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SHORT)
			|| $this->dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_USHORT)
			|| $this->dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_INT)
			|| $this->dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UINT)
		) {
			if (is_numeric($this->invalid)) {
				return intval($this->invalid);
			}

			return null;
		} elseif ($this->dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)) {
			if (is_numeric($this->invalid)) {
				return floatval($this->invalid);
			}

			return null;
		}

		return strval($this->invalid);
	}

	public function setInvalid(string|null $invalid): void
	{
		$this->invalid = $invalid;
	}

	public function getScale(): int|null
	{
		return $this->scale;
	}

	public function setScale(int|null $scale): void
	{
		$this->scale = $scale;
	}

	public function getStep(): float|null
	{
		return $this->step;
	}

	public function setStep(float|null $step): void
	{
		$this->step = $step;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getValue(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		if (!$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_VARIABLE)) {
			throw new Exceptions\InvalidState(
				sprintf('Reading value is not allowed for property type: %s', strval($this->getType()->getValue())),
			);
		}

		if ($this->value === null) {
			return null;
		}

		try {
			return Utilities\ValueHelper::normalizeValue(
				$this->getDataType(),
				$this->value,
				$this->getFormat(),
				$this->getInvalid(),
			);
		} catch (Exceptions\InvalidState) {
			return null;
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 */
	public function setValue(
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
	): void
	{
		if (!$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_VARIABLE)) {
			throw new Exceptions\InvalidState(
				sprintf(
					'Writing value is not allowed for property type: %s:%s',
					strval($this->getType()->getValue()),
					$this->getIdentifier(),
				),
			);
		}

		$value = Utilities\ValueHelper::flattenValue($value);

		if ($this->getIdentifier() === MetadataTypes\PropertyIdentifier::IDENTIFIER_IP_ADDRESS) {
			if (!is_string($value)) {
				throw new Exceptions\InvalidArgument(
					'Provided property value is not valid value for IP address property',
				);
			}

			if (preg_match(self::MATCH_IP_ADDRESS, $value) === 1) {
				$this->value = $value;
			} else {
				throw new Exceptions\InvalidArgument(
					'Provided property value is not valid value for IP address property',
				);
			}
		} elseif ($this->getIdentifier() === MetadataTypes\PropertyIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS) {
			if (!is_string($value)) {
				throw new Exceptions\InvalidArgument(
					'Provided property value is not valid value for MAC address property',
				);
			}

			$value = preg_replace('/[^a-zA-Z0-9]+/', '', $value);
			assert(is_string($value));
			$value = preg_replace('~(..)(?!$)\.?~', '\1:', $value);
			assert(is_string($value));

			if (preg_match(self::MATCH_MAC_ADDRESS, $value) === 1) {
				$this->value = strtolower($value);
			} else {
				throw new Exceptions\InvalidArgument(
					'Provided property value is not valid value for MAC address property',
				);
			}
		} else {
			if (is_bool($value)) {
				$this->value = $value ? '1' : '0';

			} elseif ($value !== null) {
				$this->value = strval($value);

			} else {
				$this->value = null;
			}
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getDefault(): bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null
	{
		if (!$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_VARIABLE)) {
			throw new Exceptions\InvalidState(
				sprintf(
					'Reading default value is not allowed for property type: %s',
					strval($this->getType()->getValue()),
				),
			);
		}

		if ($this->default === null) {
			return null;
		}

		try {
			return Utilities\ValueHelper::normalizeValue(
				$this->getDataType(),
				$this->default,
				$this->getFormat(),
				$this->getInvalid(),
			);
		} catch (Exceptions\InvalidState) {
			return null;
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setDefault(string|null $default): void
	{
		if (!$this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_VARIABLE)) {
			throw new Exceptions\InvalidState(
				sprintf(
					'Writing default value is not allowed for property type: %s',
					strval($this->getType()->getValue()),
				),
			);
		}

		$this->default = $default;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function toArray(): array
	{
		$data = [
			'id' => $this->getPlainId(),
			'type' => $this->getType()->getValue(),
			'category' => $this->getCategory()->getValue(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'settable' => $this->isSettable(),
			'queryable' => $this->isQueryable(),
			'data_type' => $this->getDataType()->getValue(),
			'unit' => $this->getUnit(),
			'format' => $this->getFormat()?->getValue(),
			'invalid' => $this->getInvalid(),
			'scale' => $this->getScale(),
			'step' => $this->getStep(),
		];

		if ($this->getType()->equalsValue(MetadataTypes\PropertyType::TYPE_VARIABLE)) {
			return array_merge($data, [
				'default' => Utilities\ValueHelper::flattenValue($this->getDefault()),
				'value' => Utilities\ValueHelper::flattenValue($this->getValue()),
			]);
		}

		return $data;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 */
	private function buildFormat(
		string|null $format,
	): MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|MetadataValueObjects\EquationFormat|null
	{
		if ($format === null) {
			return null;
		}

		if (
			in_array($this->dataType->getValue(), [
				MetadataTypes\DataType::DATA_TYPE_CHAR,
				MetadataTypes\DataType::DATA_TYPE_UCHAR,
				MetadataTypes\DataType::DATA_TYPE_SHORT,
				MetadataTypes\DataType::DATA_TYPE_USHORT,
				MetadataTypes\DataType::DATA_TYPE_INT,
				MetadataTypes\DataType::DATA_TYPE_UINT,
				MetadataTypes\DataType::DATA_TYPE_FLOAT,
			], true)
		) {
			if (preg_match(Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE, $format) === 1) {
				return new MetadataValueObjects\NumberRangeFormat($format);
			} elseif (preg_match(Metadata\Constants::VALUE_FORMAT_EQUATION, $format) === 1) {
				return new MetadataValueObjects\EquationFormat($format);
			}
		} elseif (
			in_array($this->dataType->getValue(), [
				MetadataTypes\DataType::DATA_TYPE_ENUM,
				MetadataTypes\DataType::DATA_TYPE_BUTTON,
				MetadataTypes\DataType::DATA_TYPE_SWITCH,
			], true)
		) {
			if (preg_match(Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM, $format) === 1) {
				return new MetadataValueObjects\CombinedEnumFormat($format);
			} elseif (preg_match(Metadata\Constants::VALUE_FORMAT_STRING_ENUM, $format) === 1) {
				return new MetadataValueObjects\StringEnumFormat($format);
			}
		}

		return null;
	}

	public function getSource(): MetadataTypes\ModuleSource
	{
		return MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES);
	}

}
