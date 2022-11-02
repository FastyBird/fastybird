<?php declare(strict_types = 1);

/**
 * ManualTriggerHydrator.php
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

namespace FastyBird\Module\Triggers\Hydrators\Triggers;

use FastyBird\Module\Triggers\Entities;

/**
 * Manual trigger entity hydrator
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Hydrators
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends TriggerHydrator<Entities\Triggers\IManualTrigger>
 */
final class ManualTriggerHydrator extends TriggerHydrator
{

	/**
	 * {@inheritDoc}
	 */
	public function getEntityName(): string
	{
		return Entities\Triggers\ManualTrigger::class;
	}

}
