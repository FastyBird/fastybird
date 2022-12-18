<?php declare(strict_types = 1);

/**
 * AutomaticTrigger.php
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
use FastyBird\Module\Triggers\Schemas;

/**
 * Automatic trigger entity hydrator
 *
 * @extends Trigger<Entities\Triggers\AutomaticTrigger>
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class AutomaticTrigger extends Trigger
{

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Triggers\AutomaticTrigger::RELATIONSHIPS_CONDITIONS,
		Schemas\Triggers\AutomaticTrigger::RELATIONSHIPS_ACTIONS,
		Schemas\Triggers\AutomaticTrigger::RELATIONSHIPS_NOTIFICATIONS,
	];

	public function getEntityName(): string
	{
		return Entities\Triggers\AutomaticTrigger::class;
	}

}
