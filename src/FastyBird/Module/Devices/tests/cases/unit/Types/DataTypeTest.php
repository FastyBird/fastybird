<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Types;

use Consistence\Enum\InvalidEnumValueException;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use PHPUnit\Framework\TestCase;

final class DataTypeTest extends TestCase
{

	public function XtestCreateDatatype(): void
	{
		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_INT);

		self::assertSame(MetadataTypes\DataType::DATA_TYPE_INT, $datatype->getValue());

		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT);

		self::assertSame(MetadataTypes\DataType::DATA_TYPE_FLOAT, $datatype->getValue());

		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN);

		self::assertSame(MetadataTypes\DataType::DATA_TYPE_BOOLEAN, $datatype->getValue());

		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING);

		self::assertSame(MetadataTypes\DataType::DATA_TYPE_STRING, $datatype->getValue());

		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM);

		self::assertSame(MetadataTypes\DataType::DATA_TYPE_ENUM, $datatype->getValue());

		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_COLOR);

		self::assertSame(MetadataTypes\DataType::DATA_TYPE_COLOR, $datatype->getValue());
	}

	public function XtestInvalidDatatype(): void
	{
		$this->expectException(InvalidEnumValueException::class);

		MetadataTypes\DataType::get('invalidtype');
	}

}
