<?php declare(strict_types = 1);

/**
 * StateUpdated.php
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
use Ramsey\Uuid;
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
		private readonly Uuid\UuidInterface $id,
		private readonly States\State $state,
	)
	{
	}

	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	public function getState(): States\State
	{
		return $this->state;
	}

}
