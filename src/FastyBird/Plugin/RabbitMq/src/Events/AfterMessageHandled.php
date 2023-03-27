<?php declare(strict_types = 1);

/**
 * AfterMessageHandled.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           27.03.23
 */

namespace FastyBird\Plugin\RabbitMq\Events;

use Bunny;
use Symfony\Contracts\EventDispatcher;

/**
 * After message handled event
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class AfterMessageHandled extends EventDispatcher\Event
{

	public function __construct(private readonly Bunny\Message $message)
	{
	}

	public function getMessage(): Bunny\Message
	{
		return $this->message;
	}

}
