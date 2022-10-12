<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\Metadata\ValueObjects;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../BaseTestCase.php';

/**
 * @testCase
 */
final class StringEnumFormatTest extends BaseTestCase
{

	public function testFromString(): void
	{
		$valueObject = new ValueObjects\StringEnumFormat('one,two,three');

		Assert::count(3, $valueObject->toArray());
		Assert::equal(['one', 'two', 'three'], $valueObject->toArray());
		Assert::equal('one,two,three', strval($valueObject));

		$valueObject = new ValueObjects\StringEnumFormat('one,two,,three');

		Assert::count(3, $valueObject->toArray());
		Assert::equal(['one', 'two', 'three'], $valueObject->toArray());
		Assert::equal('one,two,three', strval($valueObject));
	}

	public function testFromArray(): void
	{
		$valueObject = new ValueObjects\StringEnumFormat(['one', 'two', 'three']);

		Assert::count(3, $valueObject->toArray());
		Assert::equal(['one', 'two', 'three'], $valueObject->toArray());
		Assert::equal('one,two,three', strval($valueObject));
	}

}

$test_case = new StringEnumFormatTest();
$test_case->run();
