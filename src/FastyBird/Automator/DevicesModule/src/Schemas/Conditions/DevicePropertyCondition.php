<?php declare(strict_types = 1);

/**
 * DevicePropertyCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Schemas
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Automator\DevicesModule\Schemas\Conditions;

use FastyBird\Automator\DevicesModule\Entities;
use FastyBird\Library\Metadata\Types\ModuleSource;
use FastyBird\Module\Triggers\Schemas as TriggersSchemas;
use Neomerx\JsonApi;
use function array_merge;
use function strval;

/**
 * Device property condition entity schema
 *
 * @extends TriggersSchemas\Conditions\Condition<Entities\Conditions\DevicePropertyCondition>
 *
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicePropertyCondition extends TriggersSchemas\Conditions\Condition
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = ModuleSource::SOURCE_MODULE_TRIGGERS . '/condition/device-property';

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Conditions\DevicePropertyCondition::class;
	}

	/**
	 * @return iterable<string, string|bool|array<int>|null>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes(
		$resource,
		JsonApi\Contracts\Schema\ContextInterface $context,
	): iterable
	{
		return array_merge((array) parent::getAttributes($resource, $context), [
			'device' => $resource->getDevice()->toString(),
			'property' => $resource->getProperty()->toString(),
			'operator' => strval($resource->getOperator()->getValue()),
			'operand' => strval($resource->getOperand()),
		]);
	}

}
