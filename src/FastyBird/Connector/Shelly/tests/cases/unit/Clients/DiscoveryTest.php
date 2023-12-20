<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Tests\Cases\Unit\Clients;

use DateTimeImmutable;
use Error;
use FastyBird\Connector\Shelly\Clients;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Queries;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Services;
use FastyBird\Connector\Shelly\Tests;
use FastyBird\Connector\Shelly\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette\DI;
use Nette\Utils;
use Psr\Http;
use React;
use React\Datagram;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use function is_string;
use function strval;

final class DiscoveryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function setUp(): void
	{
		parent::setUp();

		$dateTimeFactory = $this->createMock(DateTimeFactory\Factory::class);
		$dateTimeFactory
			->method('getNow')
			->willReturn(new DateTimeImmutable('2023-09-11T22:00:00+00:00'));

		$this->mockContainerService(
			DateTimeFactory\Factory::class,
			$dateTimeFactory,
		);
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testDiscoverGen1LocalDevices(): void
	{
		$multicastFactoryService = $this->createMock(Services\MulticastFactory::class);
		$multicastFactoryService
			->method('create')
			->willReturnCallback(
				function (string $address, int $port): Datagram\SocketInterface {
					$datagramClient = $this->createMock(Datagram\Socket::class);
					$datagramClient
						->method('on')
						->willReturnCallback(
							static function (string $eventName, callable $callback) use ($address, $port): void {
								if ($address === '224.0.0.251' && $port === 5_353) {
									$callback(
										Utils\FileSystem::read(
											__DIR__ . '/../../../fixtures/Clients/gen1_shelly_rgbw.mdns',
										),
									);
								}
							},
						);
					$datagramClient
						->method('send')
						->with(
							self::callback(static function ($data): bool {
								self::assertTrue(is_string($data));

								return true;
							}),
							self::callback(static function (string $remoteAddress): bool {
								self::assertSame('224.0.0.251:5353', $remoteAddress);

								return true;
							}),
						);
					$datagramClient
						->method('close');

					return $datagramClient;
				},
			);

		$this->mockContainerService(Services\MulticastFactory::class, $multicastFactoryService);

		$httpAsyncClient = $this->createMock(React\Http\Io\Transaction::class);
		$httpAsyncClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Promise\PromiseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					$responsePromise = $this->createMock(Promise\PromiseInterface::class);
					$responsePromise
						->method('then')
						->with(
							self::callback(static function (callable $callback) use ($response): bool {
								$callback($response);

								return true;
							}),
							self::callback(static fn (): bool => true),
						);

					if (strval($request->getUri()) === 'http://10.10.0.239/shelly') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/gen1_local_discovery_device_info.json',
								),
							);

					} elseif (strval($request->getUri()) === 'http://10.10.0.239/cit/d') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/gen1_local_discovery_device_state.json',
								),
							);

					} else {
						throw new Exceptions\InvalidState(
							'This api call should not occur: ' . strval($request->getUri()),
						);
					}

					$responseBody
						->method('getContents')
						->willReturn(
							'',
						);

					return $responsePromise;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpAsyncClient) {
					if ($async) {
						return $httpAsyncClient;
					}

					throw new Exceptions\InvalidState('Sync clients should not be called when doing devices discovery');
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('shelly-local');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\ShellyConnector::class);
		self::assertInstanceOf(Entities\ShellyConnector::class, $connector);

		$clientFactory = $this->getContainer()->getByType(Clients\DiscoveryFactory::class);

		$client = $clientFactory->create($connector);

		$client->on('finished', static function (array $foundDevices): void {
			self::assertCount(1, $foundDevices);
		});

		$client->discover();

		$eventLoop = $this->getContainer()->getByType(EventLoop\LoopInterface::class);

		$eventLoop->addTimer(35, static function () use ($eventLoop): void {
			$eventLoop->stop();
		});

		$eventLoop->run();

		$queue = $this->getContainer()->getByType(Queue\Queue::class);

		self::assertFalse($queue->isEmpty());

		$consumers = $this->getContainer()->getByType(Queue\Consumers::class);

		$consumers->consume();

		$devicesRepository = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class);

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byIdentifier('c45bbee4c926-shellyrgbw2');

		$device = $devicesRepository->findOneBy($findDeviceQuery, Entities\ShellyDevice::class);

		self::assertInstanceOf(Entities\ShellyDevice::class, $device);
		self::assertSame(Types\DeviceGeneration::GENERATION_1, $device->getGeneration()->getValue());
		self::assertSame('10.10.0.239', $device->getIpAddress());
		self::assertSame('shellyrgbw2-C45BBEE4C926.local', $device->getDomain());
		self::assertFalse($device->hasAuthentication());
		self::assertSame('SHRGBW2', $device->getModel());
		self::assertSame('c4:5b:be:e4:c9:26', $device->getMacAddress());

		$channelsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Channels\ChannelsRepository::class,
		);

		$findChannelsQuery = new DevicesQueries\Entities\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $channelsRepository->findAllBy($findChannelsQuery);

		self::assertCount(5, $channels);
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testDiscoverGen2LocalDevices(): void
	{
		$multicastFactoryService = $this->createMock(Services\MulticastFactory::class);
		$multicastFactoryService
			->method('create')
			->willReturnCallback(
				function (string $address, int $port): Datagram\SocketInterface {
					$datagramClient = $this->createMock(Datagram\Socket::class);
					$datagramClient
						->method('on')
						->willReturnCallback(
							static function (string $eventName, callable $callback) use ($address, $port): void {
								if ($address === '224.0.0.251' && $port === 5_353) {
									$callback(
										Utils\FileSystem::read(
											__DIR__ . '/../../../fixtures/Clients/gen2_shelly_2pm_plus.mdns',
										),
									);
								}
							},
						);
					$datagramClient
						->method('send')
						->with(
							self::callback(static function ($data): bool {
								self::assertTrue(is_string($data));

								return true;
							}),
							self::callback(static function (string $remoteAddress): bool {
								self::assertSame('224.0.0.251:5353', $remoteAddress);

								return true;
							}),
						);
					$datagramClient
						->method('close');

					return $datagramClient;
				},
			);

		$this->mockContainerService(Services\MulticastFactory::class, $multicastFactoryService);

		$httpAsyncClient = $this->createMock(React\Http\Io\Transaction::class);
		$httpAsyncClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Promise\PromiseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					$responsePromise = $this->createMock(Promise\PromiseInterface::class);
					$responsePromise
						->method('then')
						->with(
							self::callback(static function (callable $callback) use ($response): bool {
								$callback($response);

								return true;
							}),
							self::callback(static fn (): bool => true),
						);

					if (strval($request->getUri()) === 'http://10.10.0.37/rpc/Shelly.GetDeviceInfo') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/gen2_local_discovery_device_info.json',
								),
							);

					} elseif (strval($request->getUri()) === 'http://10.10.0.37/rpc/Shelly.GetConfig') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/gen2_local_discovery_device_configuration.json',
								),
							);

					} elseif (strval($request->getUri()) === 'http://10.10.0.37/rpc/Shelly.GetStatus') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/gen2_local_discovery_device_state.json',
								),
							);

					} else {
						throw new Exceptions\InvalidState(
							'This api call should not occur: ' . strval($request->getUri()),
						);
					}

					$responseBody
						->method('getContents')
						->willReturn(
							'',
						);

					return $responsePromise;
				},
			);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpAsyncClient) {
					if ($async) {
						return $httpAsyncClient;
					}

					throw new Exceptions\InvalidState('Sync clients should not be called when doing devices discovery');
				},
			);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('shelly-local');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\ShellyConnector::class);
		self::assertInstanceOf(Entities\ShellyConnector::class, $connector);

		$clientFactory = $this->getContainer()->getByType(Clients\DiscoveryFactory::class);

		$client = $clientFactory->create($connector);

		$client->on('finished', static function (array $foundDevices): void {
			self::assertCount(1, $foundDevices);
		});

		$client->discover();

		$eventLoop = $this->getContainer()->getByType(EventLoop\LoopInterface::class);

		$eventLoop->addTimer(35, static function () use ($eventLoop): void {
			$eventLoop->stop();
		});

		$eventLoop->run();

		$queue = $this->getContainer()->getByType(Queue\Queue::class);

		self::assertFalse($queue->isEmpty());

		$consumers = $this->getContainer()->getByType(Queue\Consumers::class);

		$consumers->consume();

		$devicesRepository = $this->getContainer()->getByType(DevicesModels\Entities\Devices\DevicesRepository::class);

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byIdentifier('441793ad07e8-shellyplus2pm');

		$device = $devicesRepository->findOneBy($findDeviceQuery, Entities\ShellyDevice::class);

		self::assertInstanceOf(Entities\ShellyDevice::class, $device);
		self::assertSame(Types\DeviceGeneration::GENERATION_2, $device->getGeneration()->getValue());
		self::assertSame('10.10.0.37', $device->getIpAddress());
		self::assertSame('ShellyPlus2PM-441793AD07E8.local', $device->getDomain());
		self::assertFalse($device->hasAuthentication());
		self::assertSame('SNSW-102P16EU', $device->getModel());
		self::assertSame('44:17:93:ad:07:e8', $device->getMacAddress());

		$channelsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Channels\ChannelsRepository::class,
		);

		$findChannelsQuery = new DevicesQueries\Entities\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $channelsRepository->findAllBy($findChannelsQuery);

		self::assertCount(4, $channels);
	}

}
