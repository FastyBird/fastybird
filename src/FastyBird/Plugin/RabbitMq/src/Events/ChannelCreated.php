<?php declare(strict_types = 1);

/**
 * ClientCreated.php
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
 * Client channel created event
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelCreated extends EventDispatcher\Event
{

	public function __construct(private readonly Bunny\Channel $channel)
	{
	}

	public function getChannel(): Bunny\Channel
	{
		return $this->channel;
	}

}
