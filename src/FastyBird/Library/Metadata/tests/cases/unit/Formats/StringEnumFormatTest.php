<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\Formats;

use FastyBird\Library\Metadata\Formats;
use FastyBird\Library\Metadata\Tests;
use function strval;

final class StringEnumFormatTest extends Tests\Cases\Unit\BaseTestCase
{

	public function testFromString(): void
	{
		$valueObject = new Formats\StringEnum('one,two,three');

		self::assertCount(3, $valueObject->toArray());
		self::assertEquals(['one', 'two', 'three'], $valueObject->toArray());
		self::assertEquals('one,two,three', strval($valueObject));

		$valueObject = new Formats\StringEnum('one,two,,three');

		self::assertCount(3, $valueObject->toArray());
		self::assertEquals(['one', 'two', 'three'], $valueObject->toArray());
		self::assertEquals('one,two,three', strval($valueObject));
	}

	public function testFromArray(): void
	{
		$valueObject = new Formats\StringEnum(['one', 'two', 'three']);

		self::assertCount(3, $valueObject->toArray());
		self::assertEquals(['one', 'two', 'three'], $valueObject->toArray());
		self::assertEquals('one,two,three', strval($valueObject));
	}

}
