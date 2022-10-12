<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\Metadata\Types;
use FastyBird\Metadata\ValueObjects;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../BaseTestCase.php';

/**
 * @testCase
 */
final class CombinedEnumFormatTest extends BaseTestCase
{

	public function testFromString(): void
	{
		$valueObject = new ValueObjects\CombinedEnumFormat('one,sw|switch_on:1000:s|on,sw|switch_off:2000:s|off');

		$items = $valueObject->getItems();

		Assert::count(3, $valueObject->toArray());
		Assert::equal([
			['one', null, null],
			[['sw', 'switch_on'], '1000', ['s', 'on']],
			[['sw', 'switch_off'], '2000', ['s', 'off']],
		], $valueObject->toArray());
		Assert::count(3, $items);
		Assert::type(Types\DataTypeShort::class, $items[1][0]->getDataType());
		Assert::same(Types\DataTypeShort::DATA_TYPE_SWITCH, $items[1][0]->getDataType()->getValue());
		Assert::type(Types\SwitchPayload::class, $items[1][0]->getValue());
		Assert::same(Types\SwitchPayload::PAYLOAD_ON, $items[1][0]->getValue()->getValue());
		Assert::equal('one::,sw|switch_on:1000:s|on,sw|switch_off:2000:s|off', strval($valueObject));

		$valueObject = new ValueObjects\CombinedEnumFormat('sw|switch_on:1000:s|on,sw|switch_off:2000:s|off');

		Assert::count(2, $valueObject->toArray());
		Assert::equal([
			[['sw', 'switch_on'], '1000', ['s', 'on']],
			[['sw', 'switch_off'], '2000', ['s', 'off']],
		], $valueObject->toArray());
		Assert::equal('sw|switch_on:1000:s|on,sw|switch_off:2000:s|off', strval($valueObject));
	}

	public function testFromArray(): void
	{
		$valueObject = new ValueObjects\CombinedEnumFormat([
			['one', null, null],
			[['sw', 'switch_on'], '1000', ['s', 'on']],
			[['sw', 'switch_off'], '2000', ['s', 'off']],
		]);

		Assert::count(3, $valueObject->toArray());
		Assert::equal([
			['one', null, null],
			[['sw', 'switch_on'], '1000', ['s', 'on']],
			[['sw', 'switch_off'], '2000', ['s', 'off']],
		], $valueObject->toArray());
		Assert::equal('one::,sw|switch_on:1000:s|on,sw|switch_off:2000:s|off', strval($valueObject));
	}

}

$test_case = new CombinedEnumFormatTest();
$test_case->run();
