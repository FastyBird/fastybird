<?php declare(strict_types = 1);

/**
 * StateDeleted.php
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

namespace FastyBird\Plugin\RedisDb\Events;

use FastyBird\Plugin\RedisDb\States;
use Symfony\Contracts\EventDispatcher;

/**
 * After state is deleted event
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class StateDeleted extends EventDispatcher\Event
{

	public function __construct(private readonly States\State $state)
	{
	}

	public function getState(): States\State
	{
		return $this->state;
	}

}
