<?php declare(strict_types = 1);

/**
 * AfterMessageHandled.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           15.02.24
 */

namespace FastyBird\Plugin\RedisDb\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * After message handled event
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class AfterMessageHandled extends EventDispatcher\Event
{

	public function __construct(private readonly string $message)
	{
	}

	public function getMessage(): string
	{
		return $this->message;
	}

}
