<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\ValueObjects;

use FastyBird\Library\Metadata\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Library\Metadata\ValueObjects;
use function strval;

final class StringEnumFormatTest extends BaseTestCase
{

	public function XtestFromString(): void
	{
		$valueObject = new ValueObjects\StringEnumFormat('one,two,three');

		self::assertCount(3, $valueObject->toArray());
		self::assertEquals(['one', 'two', 'three'], $valueObject->toArray());
		self::assertEquals('one,two,three', strval($valueObject));

		$valueObject = new ValueObjects\StringEnumFormat('one,two,,three');

		self::assertCount(3, $valueObject->toArray());
		self::assertEquals(['one', 'two', 'three'], $valueObject->toArray());
		self::assertEquals('one,two,three', strval($valueObject));
	}

	public function XtestFromArray(): void
	{
		$valueObject = new ValueObjects\StringEnumFormat(['one', 'two', 'three']);

		self::assertCount(3, $valueObject->toArray());
		self::assertEquals(['one', 'two', 'three'], $valueObject->toArray());
		self::assertEquals('one,two,three', strval($valueObject));
	}

}
