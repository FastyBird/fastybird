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

use FastyBird\Connector\Shelly\Entities;
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

	/**
	 * @param Promise\Deferred<Entities\API\Gen2\GetDeviceState|bool>|null $deferred
	 */
	public function __construct(
		#[ObjectMapper\Rules\InstanceOfValue(WsFrame::class)]
		private readonly WsFrame $frame,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\InstanceOfValue(Promise\Deferred::class),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly Promise\Deferred|null $deferred = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\InstanceOfValue(EventLoop\TimerInterface::class),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly EventLoop\TimerInterface|null $timer = null,
	)
	{
	}

	public function getFrame(): WsFrame
	{
		return $this->frame;
	}

	/**
	 * @return Promise\Deferred<Entities\API\Gen2\GetDeviceState|bool>|null
	 */
	public function getDeferred(): Promise\Deferred|null
	{
		return $this->deferred;
	}

	public function getTimer(): EventLoop\TimerInterface|null
	{
		return $this->timer;
	}

}
