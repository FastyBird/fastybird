<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\Utilities;

use DateTimeInterface;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Types;
use FastyBird\Library\Metadata\Utilities;
use FastyBird\Library\Metadata\ValueObjects;
use PHPUnit\Framework\TestCase;

final class ValueTest extends TestCase
{

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\InvalidValue
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
		bool $throwError = false,
	): void
	{
		if ($throwError) {
			self::expectException(Exceptions\InvalidValue::class);
		}

		$normalized = Utilities\Value::normalizeValue($value, $dataType, $format);

		if (!$throwError) {
			self::assertSame($expected, $normalized);
		}
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
				Types\DataType::get(Types\DataType::CHAR),
				'10',
				null,
				null,
				null,
				10,
				false,
			],
			'integer_2' => [
				Types\DataType::get(Types\DataType::CHAR),
				'9',
				new ValueObjects\NumberRangeFormat([10, 20]),
				null,
				null,
				null,
				true,
			],
			'integer_3' => [
				Types\DataType::get(Types\DataType::CHAR),
				'30',
				new ValueObjects\NumberRangeFormat([10, 20]),
				null,
				null,
				null,
				true,
			],
			'float_1' => [
				Types\DataType::get(Types\DataType::FLOAT),
				'30.3',
				null,
				null,
				null,
				30.3,
			],
		];
	}

}
