<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\Utilities;

use DateTimeInterface;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use FastyBird\Library\Metadata\Utilities;
use FastyBird\Library\Metadata\ValueObjects;
use PHPUnit\Framework\TestCase;

final class ValueHelperTest extends TestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 *
	 * @dataProvider normalizeValue
	 */
	public function testNormalizeValue(
		Types\DataType $dataType,
		bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null $value,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|null $format = null,
		float|int|string|null $invalid = null,
		ValueObjects\EquationTransformer|null $transformer = null,
		float|int|string|null $expected = null,
	): void
	{
		$normalized = Utilities\ValueHelper::normalizeValue($dataType, $value, $format);

		self::assertSame($expected, $normalized);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 *
	 * @dataProvider normalizeReadValue
	 */
	public function testNormalizeReadValue(
		Types\DataType $dataType,
		bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null $value,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|null $format = null,
		int|null $scale,
		float|int|string|null $invalid = null,
		ValueObjects\EquationTransformer|null $transformer = null,
		float|int|string|null $expected = null,
	): void
	{
		$normalized = Utilities\ValueHelper::normalizeValue(
			$dataType,
			$value,
			$format,
		);

		$transformed = Utilities\ValueHelper::transformReadValue(
			$dataType,
			$normalized,
			$transformer,
			$scale,
		);

		self::assertSame($expected, $transformed);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 *
	 * @dataProvider normalizeWriteValue
	 */
	public function testNormalizeWriteValue(
		Types\DataType $dataType,
		bool|float|int|string|DateTimeInterface|Types\ButtonPayload|Types\SwitchPayload|Types\CoverPayload|null $value,
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		ValueObjects\StringEnumFormat|ValueObjects\NumberRangeFormat|ValueObjects\CombinedEnumFormat|null $format = null,
		int|null $scale,
		float|int|string|null $invalid = null,
		ValueObjects\EquationTransformer|null $transformer = null,
		float|int|string|null $expected = null,
	): void
	{
		$normalized = Utilities\ValueHelper::normalizeValue(
			$dataType,
			$value,
			$format,
		);

		$normalized = Utilities\ValueHelper::transformWriteValue(
			$dataType,
			$normalized,
			$transformer,
			$scale,
		);

		self::assertSame($expected, $normalized);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 *
	 * @return array<string, array<mixed>>
	 */
	public static function normalizeValue(): array
	{
		return [
			'integer_1' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_CHAR),
				'10',
				null,
				null,
				null,
				10,
			],
			'integer_2' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_CHAR),
				'9',
				new ValueObjects\NumberRangeFormat([10, 20]),
				null,
				null,
				10,
			],
			'integer_3' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_CHAR),
				'30',
				new ValueObjects\NumberRangeFormat([10, 20]),
				null,
				null,
				20,
			],
			'float_1' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_FLOAT),
				'30.3',
				null,
				null,
				null,
				30.3,
			],
		];
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 *
	 * @return array<string, array<mixed>>
	 */
	public static function normalizeReadValue(): array
	{
		return [
			'integer_1' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_CHAR),
				'10',
				null,
				1,
				null,
				null,
				1.0,
			],
			'integer_2' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_CHAR),
				'230',
				null,
				1,
				null,
				null,
				23.0,
			],
			'integer_3' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_CHAR),
				'20',
				new ValueObjects\NumberRangeFormat([10, 20]),
				1,
				null,
				null,
				2.0,
			],
			'float_1' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_FLOAT),
				'303',
				null,
				1,
				null,
				null,
				30.3,
			],
			'float_2' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_FLOAT),
				'303',
				null,
				2,
				null,
				null,
				3.03,
			],
			'equation_1' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_CHAR),
				'10',
				null,
				null,
				null,
				new ValueObjects\EquationTransformer('equation:x=2y + 10'),
				30,
			],
			'equation_2' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_FLOAT),
				'10',
				null,
				null,
				null,
				new ValueObjects\EquationTransformer('equation:x=2y + 10'),
				30.0,
			],
			'equation_3' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_FLOAT),
				'10',
				null,
				null,
				null,
				new ValueObjects\EquationTransformer('equation:x=2y * 10'),
				200.0,
			],
			'equation_4' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_FLOAT),
				'10',
				null,
				null,
				null,
				new ValueObjects\EquationTransformer('equation:x=2y / 10'),
				2.0,
			],
		];
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 *
	 * @return array<string, array<mixed>>
	 */
	public static function normalizeWriteValue(): array
	{
		return [
			'integer_1' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_CHAR),
				1.0,
				null,
				1,
				null,
				null,
				10,
			],
			'integer_2' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_CHAR),
				23.0,
				null,
				1,
				null,
				null,
				230,
			],
			'integer_3' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_CHAR),
				1.5,
				new ValueObjects\NumberRangeFormat([10, 20]),
				1,
				null,
				null,
				15,
			],
			'float_1' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_FLOAT),
				30.3,
				null,
				1,
				null,
				null,
				303.0,
			],
			'float_2' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_FLOAT),
				3.03,
				null,
				2,
				null,
				null,
				303.0,
			],
			'equation_1' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_CHAR),
				30,
				null,
				null,
				null,
				new ValueObjects\EquationTransformer('equation:x=2y + 10:y=(x - 10) / 2'),
				10,
			],
			'equation_2' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_FLOAT),
				30,
				null,
				null,
				null,
				new ValueObjects\EquationTransformer('equation:x=2y + 10:y=(x - 10) / 2'),
				10.0,
			],
			'equation_3' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_FLOAT),
				200,
				null,
				null,
				null,
				new ValueObjects\EquationTransformer('equation:x=2y * 10:y=x / (10 * 2)'),
				10.0,
			],
			'equation_4' => [
				Types\DataType::get(Types\DataType::DATA_TYPE_FLOAT),
				2.0,
				null,
				null,
				null,
				new ValueObjects\EquationTransformer('equation:x=2y / 10:y=10x / 2'),
				10.0,
			],
		];
	}

}
