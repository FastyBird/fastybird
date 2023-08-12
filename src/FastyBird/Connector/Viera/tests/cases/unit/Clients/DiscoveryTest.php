<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\Tests\Cases\Unit\Clients;

use Clue\React\Multicast;
use Error;
use FastyBird\Connector\Viera\API;
use FastyBird\Connector\Viera\Clients;
use FastyBird\Connector\Viera\Entities;
use FastyBird\Connector\Viera\Exceptions;
use FastyBird\Connector\Viera\Queries;
use FastyBird\Connector\Viera\Queue;
use FastyBird\Connector\Viera\Tests\Cases\Unit\DbTestCase;
use FastyBird\Connector\Viera\Types;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette\DI;
use Nette\Utils;
use Psr\Http;
use React;
use React\Datagram;
use React\EventLoop;
use RuntimeException;

final class DiscoveryTest extends DbTestCase
{

	public function testDiscover(): void
	{
		$sender = $this->createMock(Datagram\SocketInterface::class);
		$sender
			->expects(self::once())
			->method('on')
			->with(
				self::callback(static function (string $event): bool {
					self::assertSame('message', $event);

					return true;
				}),
				self::callback(static function (callable $callback): bool {
					self::assertIsCallable($callback);

					return true;
				}),
			);

		$sender
			->expects(self::once())
			->method('close');

		$sender
			->expects(self::once())
			->method('send')
			->with(
				self::callback(static function (string $data): bool {
					$expected = "M-SEARCH * HTTP/1.1\r\n";
					$expected .= "HOST: 239.255.255.250:1900\r\n";
					$expected .= "MAN: \"ssdp:discover\"\r\n";
					$expected .= "ST: urn:panasonic-com:service:p00NetworkControl:1\r\n";
					$expected .= "MX: 1\r\n";
					$expected .= "\r\n";

					self::assertSame($expected, $data);

					return true;
				}),
				self::callback(static function (string $destination): bool {
					self::assertSame('239.255.255.250:1900', $destination);

					return true;
				}),
			);

		$multicastFactory = $this->createMock(Multicast\Factory::class);
		$multicastFactory
			->method('createSender')
			->willReturn($sender);

		$this->mockContainerService(
			Multicast\Factory::class,
			$multicastFactory,
		);

		$connectorsRepository = $this->getContainer()->getByType(DevicesModels\Connectors\ConnectorsRepository::class);

		$findConnectorQuery = new Queries\FindConnectors();
		$findConnectorQuery->byIdentifier('viera');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\VieraConnector::class);
		self::assertInstanceOf(Entities\VieraConnector::class, $connector);

		$clientFactory = $this->getContainer()->getByType(Clients\DiscoveryFactory::class);

		$client = $clientFactory->create($connector);

		$client->on('finished', static function (array $foundDevices): void {
			self::assertCount(0, $foundDevices);
		});

		$client->discover();

		$eventLoop = $this->getContainer()->getByType(EventLoop\LoopInterface::class);

		$eventLoop->addTimer(10, static function () use ($eventLoop): void {
			$eventLoop->stop();
		});

		$eventLoop->run();

		$queue = $this->getContainer()->getByType(Queue\Queue::class);

		self::assertTrue($queue->isEmpty());

		$consumers = $this->getContainer()->getByType(Queue\Consumers::class);

		$consumers->consume();
	}
}
