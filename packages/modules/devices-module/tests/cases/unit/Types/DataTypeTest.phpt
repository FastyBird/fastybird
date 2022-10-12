<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\Metadata\Types as MetadataTypes;
use Ninjify\Nunjuck\TestCase\BaseTestCase;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class DataTypeTest extends BaseTestCase
{

	public function testCreateDatatype(): void
	{
		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_INT);

		Assert::type(MetadataTypes\DataType::class, $datatype);

		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT);

		Assert::type(MetadataTypes\DataType::class, $datatype);

		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN);

		Assert::type(MetadataTypes\DataType::class, $datatype);

		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING);

		Assert::type(MetadataTypes\DataType::class, $datatype);

		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM);

		Assert::type(MetadataTypes\DataType::class, $datatype);

		$datatype = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_COLOR);

		Assert::type(MetadataTypes\DataType::class, $datatype);
	}

	/**
	 * @throws Consistence\Enum\InvalidEnumValueException
	 */
	public function testInvalidDatatype(): void
	{
		MetadataTypes\DataType::get('invalidtype');
	}

}

$test_case = new DataTypeTest();
$test_case->run();
