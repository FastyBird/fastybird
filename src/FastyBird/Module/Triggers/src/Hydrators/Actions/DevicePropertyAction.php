<?php declare(strict_types = 1);

/**
 * DevicePropertyActionHydrator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @since          0.6.0
 *
 * @date           08.01.22
 */

namespace FastyBird\Module\Triggers\Hydrators\Actions;

use FastyBird\Module\Triggers\Entities;

/**
 * Device property action entity hydrator
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Hydrators
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends PropertyActionHydrator<Entities\Actions\IDevicePropertyAction>
 */
final class DevicePropertyActionHydrator extends PropertyActionHydrator
{

	/** @var string[] */
	protected array $attributes = [
		'device',
		'property',
		'value',
		'enabled',
	];

	/**
	 * {@inheritDoc}
	 */
	public function getEntityName(): string
	{
		return Entities\Actions\DevicePropertyAction::class;
	}

}
