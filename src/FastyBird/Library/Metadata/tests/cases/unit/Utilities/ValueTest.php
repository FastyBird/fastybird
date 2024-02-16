<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\Utilities;

use DateTimeInterface;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Formats;
use FastyBird\Library\Metadata\Types;
use FastyBird\Library\Metadata\Utilities;
use FastyBird\Library\Tools\Transformers as ToolsTransformers;
use PHPUnit\Framework\TestCase;
use TypeError;
use ValueError;

final class ValueTest extends TestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\InvalidValue
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @dataProvider normalizeValue
	 */
	public function testNormalizeValue(
		Types\DataType $dataType,
		bool|float|int|string|DateTimeInterface|Types\Payloads\Button|Types\Payloads\Switcher|Types\Payloads\Cover|null $value,
		Formats\StringEnum|Formats\NumberRange|Formats\CombinedEnum|null $format = null,
		float|int|string|null $invalid = null,
		ToolsTransformers\EquationTransformer|null $transformer = null,
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
	 * @return array<string, array<mixed>>
	 * @throws TypeError
	 * @throws ValueError
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	public static function normalizeValue(): array
	{
		return [
			'integer_1' => [
				Types\DataType::CHAR,
				'10',
				null,
				null,
				null,
				10,
				false,
			],
			'integer_2' => [
				Types\DataType::CHAR,
				'9',
				new Formats\NumberRange([10, 20]),
				null,
				null,
				null,
				true,
			],
			'integer_3' => [
				Types\DataType::CHAR,
				'30',
				new Formats\NumberRange([10, 20]),
				null,
				null,
				null,
				true,
			],
			'float_1' => [
				Types\DataType::FLOAT,
				'30.3',
				null,
				null,
				null,
				30.3,
			],
		];
	}

}
