<?php declare(strict_types = 1);

/**
 * WsMessage.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     ValueObjects
 * @since          1.0.0
 *
 * @date           09.01.23
 */

namespace FastyBird\Connector\Shelly\ValueObjects;

use Orisai\ObjectMapper;
use React\EventLoop;
use React\Promise;

/**
 * Websocket message entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     ValueObjects
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WsMessage implements ObjectMapper\MappedObject
{

	public function __construct(
		private readonly WsFrame $frame,
		private readonly Promise\Deferred|null $deferred = null,
		private readonly EventLoop\TimerInterface|null $timer = null,
	)
	{
	}

	public function getFrame(): WsFrame
	{
		return $this->frame;
	}

	public function getDeferred(): Promise\Deferred|null
	{
		return $this->deferred;
	}

	public function getTimer(): EventLoop\TimerInterface|null
	{
		return $this->timer;
	}

}
