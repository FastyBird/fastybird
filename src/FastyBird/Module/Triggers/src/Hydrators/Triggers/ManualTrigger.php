<?php declare(strict_types = 1);

/**
 * ManualTrigger.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Hydrators\Triggers;

use FastyBird\Module\Triggers\Entities;

/**
 * Manual trigger entity hydrator
 *
 * @extends Trigger<Entities\Triggers\ManualTrigger>
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ManualTrigger extends Trigger
{

	public function getEntityName(): string
	{
		return Entities\Triggers\ManualTrigger::class;
	}

}
