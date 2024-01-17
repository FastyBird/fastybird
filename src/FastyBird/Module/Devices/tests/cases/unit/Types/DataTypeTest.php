<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Types;

use Consistence\Enum\InvalidEnumValueException;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use PHPUnit\Framework\TestCase;

final class DataTypeTest extends TestCase
{

	public function testCreateDatatype(): void
	{
		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::INT);

		self::assertSame(MetadataTypes\DataType::INT, $datatype->getValue());

		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::FLOAT);

		self::assertSame(MetadataTypes\DataType::FLOAT, $datatype->getValue());

		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::BOOLEAN);

		self::assertSame(MetadataTypes\DataType::BOOLEAN, $datatype->getValue());

		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::STRING);

		self::assertSame(MetadataTypes\DataType::STRING, $datatype->getValue());

		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::ENUM);

		self::assertSame(MetadataTypes\DataType::ENUM, $datatype->getValue());

		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::COLOR);

		self::assertSame(MetadataTypes\DataType::COLOR, $datatype->getValue());
	}

	public function testInvalidDatatype(): void
	{
		$this->expectException(InvalidEnumValueException::class);

		MetadataTypes\DataType::get('invalidtype');
	}

}
