<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Tests\Cases\Unit\Clients;

use DateTimeImmutable;
use Error;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Clients;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\Connector\Tuya\Tests;
use FastyBird\Connector\Tuya\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
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
use function md5;
use function openssl_encrypt;
use function strval;
use const OPENSSL_RAW_DATA;

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
			->willReturn(new DateTimeImmutable('2023-08-21T22:00:00+00:00'));

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
	public function testDiscoverCloudDevices(): void
	{
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

					if (strval($request->getUri()) === 'https://openapi.tuyaeu.com/v1.0/token?grant_type=1') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/cloud_discovery_connect.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.3/iot-03/devices?source_type=tuyaUser') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/cloud_discovery_get_devices.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.0/iot-03/devices/factory-infos?device_ids=402675772462ab280dae') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/cloud_discovery_get_devices_factory_infos.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.2/iot-03/devices/402675772462ab280dae/specification') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/cloud_discovery_get_device_specification.json',
								),
							);

					} else {
						throw new Exceptions\InvalidState(
							'This api call should not occur: ' . strval($request->getUri()),
						);
					}

					return $responsePromise;
				},
			);

		$httpClientFactory = $this->createMock(API\HttpClientFactory::class);
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
			API\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsRepository = $this->getContainer()->getByType(DevicesModels\Connectors\ConnectorsRepository::class);

		$findConnectorQuery = new Queries\FindConnectors();
		$findConnectorQuery->byIdentifier('tuya-cloud');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\TuyaConnector::class);
		self::assertInstanceOf(Entities\TuyaConnector::class, $connector);

		$clientFactory = $this->getContainer()->getByType(Clients\DiscoveryFactory::class);

		$client = $clientFactory->create($connector);

		$client->on('finished', static function (array $foundDevices): void {
			self::assertCount(1, $foundDevices);
		});

		$client->discover();

		$eventLoop = $this->getContainer()->getByType(EventLoop\LoopInterface::class);

		$eventLoop->addTimer(6, static function () use ($eventLoop): void {
			$eventLoop->stop();
		});

		$eventLoop->run();

		$queue = $this->getContainer()->getByType(Queue\Queue::class);

		self::assertFalse($queue->isEmpty());

		$consumers = $this->getContainer()->getByType(Queue\Consumers::class);

		$consumers->consume();

		$devicesRepository = $this->getContainer()->getByType(DevicesModels\Devices\DevicesRepository::class);

		$findDeviceQuery = new Queries\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byIdentifier('402675772462ab280dae');

		$device = $devicesRepository->findOneBy($findDeviceQuery, Entities\TuyaDevice::class);

		self::assertInstanceOf(Entities\TuyaDevice::class, $device);
		self::assertSame('WiFi Smart Timer', $device->getName());
		self::assertSame('ATMS1601', $device->getModel());
		self::assertSame('80.78.136.56', $device->getIpAddress());
		self::assertSame('24:62:ab:28:0d:ae', $device->getMacAddress());
		self::assertSame('YyGzzRui2Xej4D04', $device->getLocalKey());

		$channelsRepository = $this->getContainer()->getByType(DevicesModels\Channels\ChannelsRepository::class);

		$findChannelQuery = new DevicesQueries\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(Types\DataPoint::CLOUD);

		$channel = $channelsRepository->findOneBy($findChannelQuery);

		self::assertInstanceOf(DevicesEntities\Channels\Channel::class, $channel);
		self::assertCount(2, $channel->getProperties());
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
	public function testDiscoverLocalDevices(): void
	{
		$localApiApi = $this->createMock(API\LocalApi::class);
		$localApiApi
			->method('connect')
			->willReturnCallback(
				function (): Promise\ExtendedPromiseInterface {
					$promise = $this->createMock(Promise\ExtendedPromiseInterface::class);
					$promise
						->method('then')
						->willReturnCallback(
							static function (callable $callback) use ($promise): Promise\ExtendedPromiseInterface {
								$callback(true);

								return $promise;
							},
						);
					$promise
						->method('otherwise')
						->with(self::callback(static fn (): bool => true));

					return $promise;
				},
			);
		$localApiApi
			->method('isConnected')
			->willReturn(true);
		$localApiApi
			->method('readStates')
			->willReturnCallback(
				function (): Promise\ExtendedPromiseInterface {
					$promise = $this->createMock(Promise\ExtendedPromiseInterface::class);
					$promise
						->method('then')
						->willReturnCallback(
							static function (callable $callback) use ($promise): Promise\ExtendedPromiseInterface {
								$entities = [
									new Entities\API\DeviceDataPointState(
										'1',
										false,
									),
									new Entities\API\DeviceDataPointState(
										'2',
										1_000,
									),
								];

								$callback($entities);

								return $promise;
							},
						);
					$promise
						->method('otherwise')
						->with(self::callback(static fn (): bool => true));

					return $promise;
				},
			);
		$localApiApi
			->method('disconnect');

		$localApiFactory = $this->createMock(API\LocalApiFactory::class);
		$localApiFactory
			->method('create')
			->willReturn($localApiApi);

		$this->mockContainerService(
			API\LocalApiFactory::class,
			$localApiFactory,
		);

		$datagramFactory = $this->createMock(Datagram\Factory::class);
		$datagramFactory
			->method('createServer')
			->willReturnCallback(
				function (string $address): Promise\ExtendedPromiseInterface {
					$datagramClient = $this->createMock(Datagram\Socket::class);
					$datagramClient
						->method('on')
						->willReturnCallback(
							static function (string $eventName, callable $callback) use ($address): void {
								if ($address === '0.0.0.0:6667') {
									// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
									$body = '{"ip":"10.10.0.10","gwId":"402675772462ab280dae","active":2,"ablilty":0,"encrypt":true,"productKey":"keyytm9ujajn77ce","version":"3.3","lan_cap":5000,"lan_seq":56,"token":true}';

									$encryptedPacket = 'bbbbbbbbbbbbbbbbbbbb' . openssl_encrypt(
										$body,
										'AES-128-ECB',
										md5('yGAdlopoPVldABfn', true),
										OPENSSL_RAW_DATA,
									) . 'bbbbbbbb';

									$callback($encryptedPacket);
								}
							},
						);

					$datagramFactoryPromise = $this->createMock(Promise\ExtendedPromiseInterface::class);
					$datagramFactoryPromise
						->method('then')
						->willReturnCallback(
							static function (callable $callback) use ($datagramFactoryPromise, $datagramClient): Promise\ExtendedPromiseInterface {
								$callback($datagramClient);

								return $datagramFactoryPromise;
							},
						);
					$datagramFactoryPromise
						->method('otherwise')
						->with(
							self::callback(static fn (): bool => true),
						);

					return $datagramFactoryPromise;
				},
			);

		$datagramFactoryService = $this->createMock(Clients\DatagramFactory::class);
		$datagramFactoryService
			->method('create')
			->willReturn($datagramFactory);

		$this->mockContainerService(Clients\DatagramFactory::class, $datagramFactoryService);

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

					if (strval($request->getUri()) === 'https://openapi.tuyaeu.com/v1.0/token?grant_type=1') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/local_discovery_connect.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.0/iot-03/devices/factory-infos?device_ids=402675772462ab280dae') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/local_discovery_get_devices_factory_infos.json',
								),
							);

					} elseif (strval(
						$request->getUri(),
					) === 'https://openapi.tuyaeu.com/v1.1/iot-03/devices/402675772462ab280dae') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/Clients/local_discovery_get_device_detail.json',
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

		$httpClientFactory = $this->createMock(API\HttpClientFactory::class);
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
			API\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsRepository = $this->getContainer()->getByType(DevicesModels\Connectors\ConnectorsRepository::class);

		$findConnectorQuery = new Queries\FindConnectors();
		$findConnectorQuery->byIdentifier('tuya-local');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\TuyaConnector::class);
		self::assertInstanceOf(Entities\TuyaConnector::class, $connector);

		$clientFactory = $this->getContainer()->getByType(Clients\DiscoveryFactory::class);

		$client = $clientFactory->create($connector);

		$client->on('finished', static function (array $foundDevices): void {
			self::assertCount(1, $foundDevices);
		});

		$client->discover();

		$eventLoop = $this->getContainer()->getByType(EventLoop\LoopInterface::class);

		$eventLoop->addTimer(10, static function () use ($eventLoop): void {
			$eventLoop->stop();
		});

		$eventLoop->run();

		$queue = $this->getContainer()->getByType(Queue\Queue::class);

		self::assertFalse($queue->isEmpty());

		$consumers = $this->getContainer()->getByType(Queue\Consumers::class);

		$consumers->consume();

		$devicesRepository = $this->getContainer()->getByType(DevicesModels\Devices\DevicesRepository::class);

		$findDeviceQuery = new Queries\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byIdentifier('402675772462ab280dae');

		$device = $devicesRepository->findOneBy($findDeviceQuery, Entities\TuyaDevice::class);

		self::assertInstanceOf(Entities\TuyaDevice::class, $device);
		self::assertSame('WiFi Smart Timer', $device->getName());
		self::assertSame('ATMS1601', $device->getModel());
		self::assertSame('10.10.0.10', $device->getIpAddress());
		self::assertSame('24:62:ab:28:0d:ae', $device->getMacAddress());
		self::assertSame('YyGzzRui2Xej4D04', $device->getLocalKey());

		$channelsRepository = $this->getContainer()->getByType(DevicesModels\Channels\ChannelsRepository::class);

		$findChannelQuery = new DevicesQueries\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(Types\DataPoint::LOCAL);

		$channel = $channelsRepository->findOneBy($findChannelQuery);

		self::assertInstanceOf(DevicesEntities\Channels\Channel::class, $channel);
		self::assertCount(2, $channel->getProperties());
	}

}
