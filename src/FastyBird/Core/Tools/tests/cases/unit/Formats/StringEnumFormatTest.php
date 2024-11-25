<?php declare(strict_types = 1);

namespace FastyBird\Core\Tools\Tests\Cases\Unit\Formats;

use FastyBird\Core\Tools\Formats;
use PHPUnit\Framework\TestCase;
use function strval;

final class StringEnumFormatTest extends TestCase
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
