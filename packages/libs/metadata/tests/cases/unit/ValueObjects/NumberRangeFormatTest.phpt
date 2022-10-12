<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\Metadata\ValueObjects;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../BaseTestCase.php';

/**
 * @testCase
 */
final class NumberRangeFormatTest extends BaseTestCase
{

	public function testFromString(): void
	{
		$valueObject = new ValueObjects\NumberRangeFormat('10:20');

		Assert::count(2, $valueObject->toArray());
		Assert::equal([10.0, 20.0], $valueObject->toArray());
		Assert::equal('10:20', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat('u8|10:20');

		Assert::count(2, $valueObject->toArray());
		Assert::equal([['u8', 10], 20.0], $valueObject->toArray());
		Assert::equal('u8|10:20', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat('u8|10.4:f|20.5');

		Assert::count(2, $valueObject->toArray());
		Assert::equal([['u8', 10], ['f', 20.5]], $valueObject->toArray());
		Assert::equal('u8|10:f|20.5', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat('10:');

		Assert::count(2, $valueObject->toArray());
		Assert::equal([10.0, null], $valueObject->toArray());
		Assert::equal('10:', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat(':20');

		Assert::count(2, $valueObject->toArray());
		Assert::equal([null, 20.0], $valueObject->toArray());
		Assert::equal(':20', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat('u8|10.4:');

		Assert::count(2, $valueObject->toArray());
		Assert::equal([['u8', 10], null], $valueObject->toArray());
		Assert::equal('u8|10:', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat('u8|10abc:20');

		Assert::count(2, $valueObject->toArray());
		Assert::equal([['u8', 10], 20.0], $valueObject->toArray());
		Assert::equal('u8|10:20', strval($valueObject));
	}

	public function testFromArray(): void
	{
		$valueObject = new ValueObjects\NumberRangeFormat(['10', '20']);

		Assert::count(2, $valueObject->toArray());
		Assert::equal([10.0, 20.0], $valueObject->toArray());
		Assert::equal('10:20', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat([['u8', 10], ['f', 20.5]]);

		Assert::count(2, $valueObject->toArray());
		Assert::equal([['u8', 10], ['f', 20.5]], $valueObject->toArray());
		Assert::equal('u8|10:f|20.5', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat([null, '20']);

		Assert::count(2, $valueObject->toArray());
		Assert::equal([null, 20.0], $valueObject->toArray());
		Assert::equal(':20', strval($valueObject));

		$valueObject = new ValueObjects\NumberRangeFormat([['u8', 10], null]);

		Assert::count(2, $valueObject->toArray());
		Assert::equal([['u8', 10], null], $valueObject->toArray());
		Assert::equal('u8|10:', strval($valueObject));
	}

}

$test_case = new NumberRangeFormatTest();
$test_case->run();
