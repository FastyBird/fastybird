<?php declare(strict_types = 1);

/**
 * WsMessage.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 * @since          0.13.0
 *
 * @date           09.01.23
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities\Clients\Entity;
use Nette;
use React\EventLoop;
use React\Promise;

/**
 * Websocket message entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WsMessage implements Entity
{

	use Nette\SmartObject;

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

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'frame' => $this->getFrame()->toArray(),
		];
	}

}
