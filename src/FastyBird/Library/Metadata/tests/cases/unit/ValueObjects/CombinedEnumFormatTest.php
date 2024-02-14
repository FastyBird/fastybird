<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\ValueObjects;

use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Formats;
use FastyBird\Library\Metadata\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Library\Metadata\Types;
use function strval;

final class CombinedEnumFormatTest extends BaseTestCase
{

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function testFromString(): void
	{
		$valueObject = new Formats\CombinedEnum('one,sw|switch_on:1000:s|on,sw|switch_off:2000:s|off');

		$items = $valueObject->getItems();

		self::assertCount(3, $valueObject->toArray());
		self::assertEquals([
			['one', null, null],
			[['sw', 'switch_on'], '1000', ['s', 'on']],
			[['sw', 'switch_off'], '2000', ['s', 'off']],
		], $valueObject->toArray());
		self::assertCount(3, $items);
		self::assertTrue($items[1][0] instanceof Formats\CombinedEnumItem);
		self::assertTrue($items[1][0]->getDataType() instanceof Types\DataTypeShort);
		self::assertSame(Types\DataTypeShort::SWITCH, $items[1][0]->getDataType()->getValue());
		self::assertTrue($items[1][0]->getValue() instanceof Types\Payloads\Switcher);
		self::assertSame(Types\Payloads\Switcher::ON, $items[1][0]->getValue());
		self::assertEquals('one::,sw|switch_on:1000:s|on,sw|switch_off:2000:s|off', strval($valueObject));

		$valueObject = new Formats\CombinedEnum('sw|switch_on:1000:s|on,sw|switch_off:2000:s|off');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([
			[['sw', 'switch_on'], '1000', ['s', 'on']],
			[['sw', 'switch_off'], '2000', ['s', 'off']],
		], $valueObject->toArray());
		self::assertEquals('sw|switch_on:1000:s|on,sw|switch_off:2000:s|off', strval($valueObject));
	}

	public function testFromArray(): void
	{
		$valueObject = new Formats\CombinedEnum([
			['one', null, null],
			[['sw', 'switch_on'], '1000', ['s', 'on']],
			[['sw', 'switch_off'], '2000', ['s', 'off']],
		]);

		self::assertCount(3, $valueObject->toArray());
		self::assertEquals([
			['one', null, null],
			[['sw', 'switch_on'], '1000', ['s', 'on']],
			[['sw', 'switch_off'], '2000', ['s', 'off']],
		], $valueObject->toArray());
		self::assertEquals('one::,sw|switch_on:1000:s|on,sw|switch_off:2000:s|off', strval($valueObject));
	}

}
