<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Utilities;

use DateTimeInterface;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Utilities;
use PHPUnit\Framework\TestCase;

final class ValueHelperTest extends TestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 *
	 * @dataProvider normalizeValue
	 */
	public function testNormalizeValue(
		MetadataTypes\DataType $dataType,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|MetadataValueObjects\EquationFormat|null $format = null,
		float|int|string|null $invalid = null,
		float|int|string|null $expected = null,
	): void
	{
		$normalized = Utilities\ValueHelper::normalizeValue($dataType, $value, $format, $invalid);

		self::assertSame($expected, $normalized);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 *
	 * @dataProvider normalizeReadValue
	 */
	public function testNormalizeReadValue(
		MetadataTypes\DataType $dataType,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|MetadataValueObjects\EquationFormat|null $format = null,
		int|null $scale,
		float|int|string|null $invalid = null,
		float|int|string|null $expected = null,
	): void
	{
		$normalized = Utilities\ValueHelper::normalizeReadValue($dataType, $value, $format, $scale, $invalid);

		self::assertSame($expected, $normalized);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 *
	 * @dataProvider normalizeWriteValue
	 */
	public function testNormalizeWriteValue(
		MetadataTypes\DataType $dataType,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataValueObjects\StringEnumFormat|MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\CombinedEnumFormat|MetadataValueObjects\EquationFormat|null $format = null,
		int|null $scale,
		float|int|string|null $invalid = null,
		float|int|string|null $expected = null,
	): void
	{
		$normalized = Utilities\ValueHelper::normalizeWriteValue($dataType, $value, $format, $scale, $invalid);

		self::assertSame($expected, $normalized);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 *
	 * @return array<string, array<mixed>>
	 */
	public static function normalizeValue(): array
	{
		return [
			'integer_1' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
				'10',
				null,
				null,
				10,
			],
			'integer_2' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
				'9',
				new MetadataValueObjects\NumberRangeFormat([10, 20]),
				null,
				10,
			],
			'integer_3' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
				'30',
				new MetadataValueObjects\NumberRangeFormat([10, 20]),
				null,
				20,
			],
			'float_1' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				'30.3',
				null,
				null,
				30.3,
			],
		];
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 *
	 * @return array<string, array<mixed>>
	 */
	public static function normalizeReadValue(): array
	{
		return [
			'integer_1' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
				'10',
				null,
				1,
				null,
				1.0,
			],
			'integer_2' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
				'230',
				null,
				1,
				null,
				23.0,
			],
			'integer_3' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
				'20',
				new MetadataValueObjects\NumberRangeFormat([10, 20]),
				1,
				null,
				2.0,
			],
			'float_1' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				'303',
				null,
				1,
				null,
				30.3,
			],
			'float_2' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				'303',
				null,
				2,
				null,
				3.03,
			],
			'equation_1' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
				'10',
				new MetadataValueObjects\EquationFormat('equation:x=2y + 10'),
				null,
				null,
				30,
			],
			'equation_2' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				'10',
				new MetadataValueObjects\EquationFormat('equation:x=2y + 10'),
				null,
				null,
				30.0,
			],
			'equation_3' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				'10',
				new MetadataValueObjects\EquationFormat('equation:x=2y * 10'),
				null,
				null,
				200.0,
			],
			'equation_4' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				'10',
				new MetadataValueObjects\EquationFormat('equation:x=2y / 10'),
				null,
				null,
				2.0,
			],
		];
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 *
	 * @return array<string, array<mixed>>
	 */
	public static function normalizeWriteValue(): array
	{
		return [
			'integer_1' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
				1.0,
				null,
				1,
				null,
				10,
			],
			'integer_2' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
				23.0,
				null,
				1,
				null,
				230,
			],
			'integer_3' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
				1.5,
				new MetadataValueObjects\NumberRangeFormat([10, 20]),
				1,
				null,
				15,
			],
			'float_1' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				30.3,
				null,
				1,
				null,
				303.0,
			],
			'float_2' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				3.03,
				null,
				2,
				null,
				303.0,
			],
			'equation_1' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
				30,
				new MetadataValueObjects\EquationFormat('equation:x=2y + 10:y=(x - 10) / 2'),
				null,
				null,
				10,
			],
			'equation_2' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				30,
				new MetadataValueObjects\EquationFormat('equation:x=2y + 10:y=(x - 10) / 2'),
				null,
				null,
				10.0,
			],
			'equation_3' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				200,
				new MetadataValueObjects\EquationFormat('equation:x=2y * 10:y=x / (10 * 2)'),
				null,
				null,
				10.0,
			],
			'equation_4' => [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				2.0,
				new MetadataValueObjects\EquationFormat('equation:x=2y / 10:y=10x / 2'),
				null,
				null,
				10.0,
			],
		];
	}

}
