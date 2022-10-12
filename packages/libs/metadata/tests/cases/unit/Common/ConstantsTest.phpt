<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\Metadata;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../BaseTestCase.php';

/**
 * @testCase
 */
final class ConstantsTest extends BaseTestCase
{

	public function testValueFormatStringEnum(): void
	{
		// Valid
		Assert::same(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_STRING_ENUM,
			'one,two,three'
		));
		Assert::same(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_STRING_ENUM,
			'one,two_v1,three'
		));
		Assert::same(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_STRING_ENUM,
			'one,two-v1,three'
		));

		// Invalid
		Assert::same(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_STRING_ENUM,
			'1one,two,three'
		));
		Assert::same(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_STRING_ENUM,
			'one,1two,three'
		));
		Assert::same(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_STRING_ENUM,
			'one,two__,three'
		));
		Assert::same(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_STRING_ENUM,
			'1,two,three'
		));
	}

	public function testValueFormatNumberRange(): void
	{
		// Valid
		Assert::same(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'10:20'
		));
		Assert::same(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'u8|10:20'
		));
		Assert::same(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'10:f|20'
		));
		Assert::same(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'f|10:i16|20'
		));
		Assert::same(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			':i16|20'
		));
		Assert::same(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'f|10:'
		));
		Assert::same(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'10:'
		));
		Assert::same(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			':20'
		));

		// Invalid
		Assert::same(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'one'
		));
		Assert::same(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'one,two'
		));
		Assert::same(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'one:10'
		));
		Assert::same(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_NUMBER_RANGE,
			'i10|10:'
		));
	}

	public function testValueFormatCombinedEnum(): void
	{
		// Valid
		Assert::same(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'one::,sw|switch_on:1000:s|on,sw|switch_off:2000:s|off'
		));
		Assert::same(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'one:one:one,two:two:two,three:three:three'
		));
		Assert::same(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'sw|switch_on:1000:s|on,sw|switch_off:2000:s|off'
		));
		Assert::same(1, preg_match(
			Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'sw|switch_on:u8|10:s|on,sw|switch_off:u8|20:s|off'
		));

		// Invalid
		Assert::same(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'sw|switch_on:u10|10:s|on,sw|switch_off:u8|20:s|off'
		));
		Assert::same(0, preg_match(
			Metadata\Constants::VALUE_FORMAT_COMBINED_ENUM,
			'sw,sw|switch_off:u8|20:s|off'
		));
	}

}

$test_case = new ConstantsTest();
$test_case->run();
