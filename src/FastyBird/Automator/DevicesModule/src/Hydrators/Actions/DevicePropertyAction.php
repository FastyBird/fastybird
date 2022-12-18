<?php declare(strict_types = 1);

/**
 * DevicePropertyAction.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Hydrators
 * @since          0.6.0
 *
 * @date           08.01.22
 */

namespace FastyBird\Automator\DevicesModule\Hydrators\Actions;

use FastyBird\Automator\DevicesModule\Entities;

/**
 * Device property action entity hydrator
 *
 * @extends PropertyAction<Entities\Actions\DevicePropertyAction>
 *
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DevicePropertyAction extends PropertyAction
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		'device',
		'property',
		'value',
		'enabled',
	];

	public function getEntityName(): string
	{
		return Entities\Actions\DevicePropertyAction::class;
	}

}
