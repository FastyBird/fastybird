<?php declare(strict_types = 1);

/**
 * StateDeleted.php
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
 * After state is deleted event
 *
 * @package        FastyBird:CouchDbPlugin!
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
