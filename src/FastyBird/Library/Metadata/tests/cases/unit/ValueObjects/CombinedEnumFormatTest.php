<?php declare(strict_types = 1);

namespace FastyBird\Metadata\Tests\Cases\Unit\ValueObjects;

use FastyBird\Metadata\Exceptions;
use FastyBird\Metadata\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Metadata\Types;
use FastyBird\Metadata\ValueObjects;
use function strval;

final class CombinedEnumFormatTest extends BaseTestCase
{

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function testFromString(): void
	{
		$valueObject = new ValueObjects\CombinedEnumFormat('one,sw|switch_on:1000:s|on,sw|switch_off:2000:s|off');

		$items = $valueObject->getItems();

		self::assertCount(3, $valueObject->toArray());
		self::assertEquals([
			['one', null, null],
			[['sw', 'switch_on'], '1000', ['s', 'on']],
			[['sw', 'switch_off'], '2000', ['s', 'off']],
		], $valueObject->toArray());
		self::assertCount(3, $items);
		self::assertTrue($items[1][0] instanceof ValueObjects\CombinedEnumFormatItem);
		self::assertTrue($items[1][0]->getDataType() instanceof Types\DataTypeShort);
		self::assertSame(Types\DataTypeShort::DATA_TYPE_SWITCH, $items[1][0]->getDataType()->getValue());
		self::assertTrue($items[1][0]->getValue() instanceof Types\SwitchPayload);
		self::assertSame(Types\SwitchPayload::PAYLOAD_ON, $items[1][0]->getValue()->getValue());
		self::assertEquals('one::,sw|switch_on:1000:s|on,sw|switch_off:2000:s|off', strval($valueObject));

		$valueObject = new ValueObjects\CombinedEnumFormat('sw|switch_on:1000:s|on,sw|switch_off:2000:s|off');

		self::assertCount(2, $valueObject->toArray());
		self::assertEquals([
			[['sw', 'switch_on'], '1000', ['s', 'on']],
			[['sw', 'switch_off'], '2000', ['s', 'off']],
		], $valueObject->toArray());
		self::assertEquals('sw|switch_on:1000:s|on,sw|switch_off:2000:s|off', strval($valueObject));
	}

	public function testFromArray(): void
	{
		$valueObject = new ValueObjects\CombinedEnumFormat([
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
