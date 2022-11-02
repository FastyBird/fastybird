<?php declare(strict_types = 1);

namespace Tests\Cases;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use Ninjify\Nunjuck\TestCase\BaseTestCase;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class ConditionOperatorTypeTest extends BaseTestCase
{

	public function testCreateOperator(): void
	{
		$operatorType = MetadataTypes\TriggerConditionOperatorType::get(MetadataTypes\TriggerConditionOperatorType::OPERATOR_VALUE_EQUAL);

		Assert::type(MetadataTypes\TriggerConditionOperatorType::class, $operatorType);

		$operatorType = MetadataTypes\TriggerConditionOperatorType::get(MetadataTypes\TriggerConditionOperatorType::OPERATOR_VALUE_ABOVE);

		Assert::type(MetadataTypes\TriggerConditionOperatorType::class, $operatorType);

		$operatorType = MetadataTypes\TriggerConditionOperatorType::get(MetadataTypes\TriggerConditionOperatorType::OPERATOR_VALUE_BELOW);

		Assert::type(MetadataTypes\TriggerConditionOperatorType::class, $operatorType);
	}

	/**
	 * @throws Consistence\Enum\InvalidEnumValueException
	 */
	public function testInvalidOperator(): void
	{
		MetadataTypes\TriggerConditionOperatorType::get('invalidtype');
	}

}

$test_case = new ConditionOperatorTypeTest();
$test_case->run();
