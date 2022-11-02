<?php declare(strict_types = 1);

/**
 * DevicePropertyCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Hydrators\Conditions;

use FastyBird\Module\Triggers\Entities;

/**
 * Device property condition entity hydrator
 *
 * @extends PropertyCondition<Entities\Conditions\DevicePropertyCondition>
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Hydrators
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicePropertyCondition extends PropertyCondition
{

	/** @var Array<int|string, string> */
	protected array $attributes = [
		'device',
		'property',
		'operator',
		'operand',
		'enabled',
	];

	public function getEntityName(): string
	{
		return Entities\Conditions\DevicePropertyCondition::class;
	}

}
