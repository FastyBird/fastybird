<?php declare(strict_types = 1);

/**
 * MulticastFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           30.08.23
 */

namespace FastyBird\Connector\Shelly\Services;

use BadMethodCallException;
use Clue\React\Multicast;
use Nette;
use React\Datagram;
use React\EventLoop;
use RuntimeException;

/**
 * React multicast server factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class MulticastFactory
{

	use Nette\SmartObject;

	public function __construct(
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws BadMethodCallException
	 * @throws RuntimeException
	 */
	public function create(string $address, int $port): Datagram\SocketInterface
	{
		return (new Multicast\Factory($this->eventLoop))->createReceiver($address . ':' . $port);
	}

}
