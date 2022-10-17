<?php declare(strict_types = 1);

/**
 * StateUpdated.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 * @since          0.61.0
 *
 * @date           13.10.22
 */

namespace FastyBird\RedisDbPlugin\Events;

use FastyBird\RedisDbPlugin\States;
use Symfony\Contracts\EventDispatcher;

/**
 * After state is updated event
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StateUpdated extends EventDispatcher\Event
{

	public function __construct(
		private readonly States\State $newState,
		private readonly States\State $previousState,
	)
	{
	}

	public function getNewState(): States\State
	{
		return $this->newState;
	}

	public function getPreviousState(): States\State
	{
		return $this->previousState;
	}

}
