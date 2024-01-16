<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\ValueObjects;

use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Library\Metadata\ValueObjects;
use function strval;

final class EquationTransformerTest extends BaseTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function testFromString(): void
	{
		$valueObject = new ValueObjects\EquationTransformer('equation:x=10y + 2');

		self::assertEquals('equation:x=10y+2', $valueObject->getValue());
		self::assertEquals('equation:x=10y+2', strval($valueObject));

		$valueObject = new ValueObjects\EquationTransformer('equation:x=(10y + 2) * 10');

		self::assertEquals('equation:x=(10y+2)*10', $valueObject->getValue());
		self::assertEquals('equation:x=(10y+2)*10', strval($valueObject));

		$valueObject = new ValueObjects\EquationTransformer('equation:x=(10y + 2) * 10|y=10x - 50');

		self::assertEquals('equation:x=(10y+2)*10|y=10x-50', $valueObject->getValue());
		self::assertEquals('equation:x=(10y+2)*10|y=10x-50', strval($valueObject));
	}

}
