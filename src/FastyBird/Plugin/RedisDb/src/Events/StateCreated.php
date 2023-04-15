<?php declare(strict_types = 1);

/**
 * StateCreated.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           13.10.22
 */

namespace FastyBird\Plugin\RedisDb\Events;

use FastyBird\Plugin\RedisDb\States;
use Symfony\Contracts\EventDispatcher;

/**
 * After state is created event
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StateCreated extends EventDispatcher\Event
{

	public function __construct(private readonly States\State $state)
	{
	}

	public function getState(): States\State
	{
		return $this->state;
	}

}
