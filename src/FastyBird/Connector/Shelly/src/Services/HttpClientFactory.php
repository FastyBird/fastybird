<?php declare(strict_types = 1);

/**
 * HttpClientFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Services
 * @since          1.0.0
 *
 * @date           01.07.23
 */

namespace FastyBird\Connector\Shelly\Services;

use GuzzleHttp;
use InvalidArgumentException;
use Nette;
use React\EventLoop;
use React\Http;
use React\Socket;

/**
 * HTTP client factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Services
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class HttpClientFactory
{

	use Nette\SmartObject;

	private const CONNECTION_TIMEOUT = 10;

	private GuzzleHttp\Client|null $client = null;

	private Http\Io\Transaction|null $asyncClient = null;

	public function __construct(
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @return ($async is true ? Http\Io\Transaction : GuzzleHttp\Client)
	 *
	 * @throws InvalidArgumentException
	 */
	public function create(bool $async = true): GuzzleHttp\Client|Http\Io\Transaction
	{
		if ($async) {
			if ($this->asyncClient === null) {
				$connector = new Socket\Connector(
					[
						'timeout' => self::CONNECTION_TIMEOUT,
						'tls' => [
							'verify_peer' => false,
							'verify_peer_name' => false,
							'check_hostname' => false,
							'allow_self_signed' => true,
						],
					],
					$this->eventLoop,
				);

				$this->asyncClient = new Http\Io\Transaction(
					Http\Io\Sender::createFromLoop($this->eventLoop, $connector),
					$this->eventLoop,
				);
			}

			return $this->asyncClient;
		} else {
			if ($this->client === null) {
				$this->client = new GuzzleHttp\Client();
			}

			return $this->client;
		}
	}

}
