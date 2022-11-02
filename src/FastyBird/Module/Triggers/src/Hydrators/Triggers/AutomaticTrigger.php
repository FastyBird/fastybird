<?php declare(strict_types = 1);

/**
 * AutomaticTriggerHydrator.php
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
 * @package         FastyBird:TriggersModule!
 * @subpackage      Hydrators
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends TriggerHydrator<Entities\Triggers\IAutomaticTrigger>
 */
final class AutomaticTriggerHydrator extends TriggerHydrator
{

	/** @var string[] */
	protected array $relationships = [
		Schemas\Triggers\AutomaticTriggerSchema::RELATIONSHIPS_CONDITIONS,
		Schemas\Triggers\AutomaticTriggerSchema::RELATIONSHIPS_ACTIONS,
		Schemas\Triggers\AutomaticTriggerSchema::RELATIONSHIPS_NOTIFICATIONS,
	];

	/**
	 * {@inheritDoc}
	 */
	public function getEntityName(): string
	{
		return Entities\Triggers\AutomaticTrigger::class;
	}

}
