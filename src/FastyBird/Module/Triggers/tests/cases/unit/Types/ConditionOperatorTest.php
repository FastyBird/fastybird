<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Types;

use Consistence\Enum\InvalidEnumValueException;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use PHPUnit\Framework\TestCase;

final class ConditionOperatorTest extends TestCase
{

	public function testCreateOperator(): void
	{
		$operatorType = MetadataTypes\TriggerConditionOperator::get(
			MetadataTypes\TriggerConditionOperator::EQUAL,
		);

		self::assertSame(MetadataTypes\TriggerConditionOperator::EQUAL, $operatorType->getValue());

		$operatorType = MetadataTypes\TriggerConditionOperator::get(
			MetadataTypes\TriggerConditionOperator::ABOVE,
		);

		self::assertSame(MetadataTypes\TriggerConditionOperator::ABOVE, $operatorType->getValue());

		$operatorType = MetadataTypes\TriggerConditionOperator::get(
			MetadataTypes\TriggerConditionOperator::BELOW,
		);

		self::assertSame(MetadataTypes\TriggerConditionOperator::BELOW, $operatorType->getValue());
	}

	public function testInvalidOperator(): void
	{
		$this->expectException(InvalidEnumValueException::class);

		MetadataTypes\TriggerConditionOperator::get('invalidtype');
	}

}
