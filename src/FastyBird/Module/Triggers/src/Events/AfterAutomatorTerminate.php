<?php declare(strict_types = 1);

/**
 * AfterAutomatorTerminate.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Events
 * @since          0.65.0
 *
 * @date           22.06.22
 */

namespace FastyBird\Module\Triggers\Events;

use FastyBird\Module\Triggers\Entities;
use Symfony\Contracts\EventDispatcher;

/**
 * Event fired after automator has been terminated
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class AfterAutomatorTerminate extends EventDispatcher\Event
{

	public function __construct(private readonly Entities\Triggers\Trigger $trigger)
	{
	}

	public function getTrigger(): Entities\Triggers\Trigger
	{
		return $this->trigger;
	}

}
