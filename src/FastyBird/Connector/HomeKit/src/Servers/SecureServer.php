<?php declare(strict_types = 1);

/**
 * SecureServer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Servers
 * @since          1.0.0
 *
 * @date           26.09.22
 */

namespace FastyBird\Connector\HomeKit\Servers;

use Closure;
use Evenement;
use FastyBird\Connector\HomeKit\Documents;
use Nette;
use Nette\Utils;
use React\Socket;
use SplObjectStorage;
use Throwable;
use function str_replace;

/**
 * HTTP secured server wrapper
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Servers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SecureServer implements Socket\ServerInterface
{

	use Evenement\EventEmitterTrait;
	use Nette\SmartObject;

	/** @var array<Closure(Socket\ConnectionInterface $connection): void> */
	public array $onConnection = [];

	/** @var array<Closure(): void> */
	public array $onClose = [];

	/** @var array<Closure(string $data): void> */
	public array $onData = [];

	/** @var array<Closure(Throwable $error): void> */
	public array $onError = [];

	/** @var SplObjectStorage<SecureConnection, null> */
	private SplObjectStorage $activeConnections;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly Socket\ServerInterface $server,
		private readonly SecureConnectionFactory $secureConnectionFactory,
		private string|null $sharedKey = null,
	)
	{
		$this->activeConnections = new SplObjectStorage();

		$this->server->on('connection', function (Socket\ConnectionInterface $connection): void {
			$securedConnection = $this->secureConnectionFactory->create(
				$this->connector,
				$this->sharedKey,
				$connection,
			);

			Utils\Arrays::invoke($this->onConnection, $securedConnection);

			$this->emit('connection', [$securedConnection]);

			$this->activeConnections->attach($securedConnection);

			$securedConnection->onClose[] = function () use ($securedConnection): void {
				$this->activeConnections->detach($securedConnection);

				Utils\Arrays::invoke($this->onClose);

				$this->emit('close');
			};

			$securedConnection->onData[] = function (string $data): void {
				Utils\Arrays::invoke($this->onData, $data);

				$this->emit('data', [$data]);
			};
		});

		$this->server->on('error', function (Throwable $error): void {
			Utils\Arrays::invoke($this->onError, $error);

			$this->emit('error', [$error]);
		});
	}

	public function setSharedKey(string|null $sharedKey): void
	{
		$this->sharedKey = $sharedKey;

		$this->activeConnections->rewind();

		foreach ($this->activeConnections as $connection) {
			$connection->setSharedKey($sharedKey);
		}
	}

	public function getAddress(): string|null
	{
		$address = $this->server->getAddress();

		if ($address === null) {
			return null;
		}

		return str_replace('tcp://', 'tls://', $address);
	}

	public function pause(): void
	{
		$this->server->pause();
	}

	public function resume(): void
	{
		$this->server->resume();
	}

	public function close(): void
	{
		$this->server->close();
	}

}
