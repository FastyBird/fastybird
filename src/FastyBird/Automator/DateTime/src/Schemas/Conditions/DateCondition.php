<?php declare(strict_types = 1);

/**
 * DateCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DateTimeAutomator!
 * @subpackage     Schemas
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Automator\DateTime\Schemas\Conditions;

use DateTimeInterface;
use FastyBird\Automator\DateTime\Entities;
use FastyBird\Library\Metadata\Types\ModuleSource;
use FastyBird\Module\Triggers\Schemas as TriggersSchemas;
use Neomerx\JsonApi;
use function array_merge;

/**
 * Date condition entity schema
 *
 * @extends TriggersSchemas\Conditions\Condition<Entities\Conditions\DateCondition>
 *
 * @package        FastyBird:DateTimeAutomator!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DateCondition extends TriggersSchemas\Conditions\Condition
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = ModuleSource::SOURCE_MODULE_TRIGGERS . '/condition/date';

	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	public function getEntityClass(): string
	{
		return Entities\Conditions\DateCondition::class;
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
			'date' => $resource->getDate()->format(DateTimeInterface::ATOM),
		]);
	}

}
