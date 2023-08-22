<?php declare(strict_types = 1);

/**
 * WebSocketClientFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           13.08.23
 */

namespace FastyBird\Connector\Tuya\API;

use InvalidArgumentException;
use Ratchet\Client;
use React\EventLoop;
use React\Promise;
use React\Socket;

/**
 * OpenPulsar websockets client factory
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WebSocketClientFactory
{

	public function __construct(
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	public function create(
		string $topicUrl,
		string $accessId,
		string $password,
	): Promise\PromiseInterface|Promise\ExtendedPromiseInterface
	{
		try {
			$reactConnector = new Socket\Connector([
				'dns' => '8.8.8.8',
				'timeout' => 10,
				'tls' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
					'check_hostname' => false,
				],
			]);

			$connector = new Client\Connector($this->eventLoop, $reactConnector);

			return $connector(
				$topicUrl,
				[],
				[
					'Connection' => 'Upgrade',
					'username' => $accessId,
					'password' => $password,
				],
			);
		} catch (InvalidArgumentException $ex) {
			return Promise\reject($ex);
		}
	}

}
