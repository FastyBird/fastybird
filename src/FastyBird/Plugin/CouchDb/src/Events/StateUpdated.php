<?php declare(strict_types = 1);

/**
 * StateUpdated.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbPlugin!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           25.03.23
 */

namespace FastyBird\Plugin\CouchDb\Events;

use FastyBird\Plugin\CouchDb\States;
use Symfony\Contracts\EventDispatcher;

/**
 * After state is updated event
 *
 * @package        FastyBird:CouchDbPlugin!
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
