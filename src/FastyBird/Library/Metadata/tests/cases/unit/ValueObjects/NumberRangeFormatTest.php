<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\ValueObjects;

use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Library\Metadata\ValueObjects;
use function strval;

final class NumberRangeFormatTest extends BaseTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function testFromString(): void
	{
		$valueObject = new ValueObjects\NumberRangeFormat('10:20');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([10.0, 20.0], $valueObject->toArray());
		self::assertEquals('10:20', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat('u8|10:20');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([['u8', 10], 20.0], $valueObject->toArray());
		self::assertEquals('u8|10:20', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat('u8|10.4:f|20.5');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([['u8', 10], ['f', 20.5]], $valueObject->toArray());
		self::assertEquals('u8|10:f|20.5', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat('10:');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([10.0, null], $valueObject->toArray());
		self::assertEquals('10:', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat(':20');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([null, 20.0], $valueObject->toArray());
		self::assertEquals(':20', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat('u8|10.4:');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([['u8', 10], null], $valueObject->toArray());
		self::assertEquals('u8|10:', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat('u8|10abc:20');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([['u8', 10], 20.0], $valueObject->toArray());
		self::assertEquals('u8|10:20', strval($valueObject));
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 */
	public function testFromArray(): void
	{
		$valueObject = new ValueObjects\NumberRangeFormat(['10', '20']);

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([10.0, 20.0], $valueObject->toArray());
		self::assertEquals('10:20', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat([['u8', 10], ['f', 20.5]]);

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([['u8', 10], ['f', 20.5]], $valueObject->toArray());
		self::assertEquals('u8|10:f|20.5', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat([null, '20']);

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([null, 20.0], $valueObject->toArray());
		self::assertEquals(':20', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat([['u8', 10], null]);

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([['u8', 10], null], $valueObject->toArray());
		self::assertEquals('u8|10:', strval($valueObject));
	}

}
