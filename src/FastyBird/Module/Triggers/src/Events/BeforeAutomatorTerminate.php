<?php declare(strict_types = 1);

/**
 * BeforeAutomatorTerminate.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           22.06.22
 */

namespace FastyBird\Module\Triggers\Events;

use FastyBird\Module\Triggers\Entities;
use Symfony\Contracts\EventDispatcher;

/**
 * Event fired before connector has been terminated
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class BeforeAutomatorTerminate extends EventDispatcher\Event
{

	public function __construct(private readonly Entities\Triggers\Trigger $trigger)
	{
	}

	public function getTrigger(): Entities\Triggers\Trigger
	{
		return $this->trigger;
	}

}
