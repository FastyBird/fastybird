<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\Clients;

use Error;
use FastyBird\Connector\NsPanel\Clients;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Services;
use FastyBird\Connector\NsPanel\Tests;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette\DI;
use Nette\Utils;
use Psr\Http;
use React;
use React\EventLoop;
use RuntimeException;

final class DiscoveryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testDiscover(): void
	{
		$responseBody = $this->createMock(Http\Message\StreamInterface::class);
		$responseBody
			->method('getContents')
			->willReturn(Utils\FileSystem::read(__DIR__ . '/../../../fixtures/Clients/responses/get_sub_devices.json'));

		$response = $this->createMock(Http\Message\ResponseInterface::class);
		$response
			->method('getBody')
			->willReturn($responseBody);

		$responsePromise = $this->createMock(React\Promise\PromiseInterface::class);
		$responsePromise
			->method('then')
			->with(
				self::callback(static function (callable $callback) use ($response): bool {
					$callback($response);

					return true;
				}),
				self::callback(static fn (): bool => true),
			);

		$httpClient = $this->createMock(React\Http\Io\Transaction::class);
		$httpClient
			->method('send')
			->willReturn($responsePromise);

		$httpClientFactory = $this->createMock(Services\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturn($httpClient);

		$this->mockContainerService(
			Services\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Connectors\Repository::class,
		);

		$findConnectorQuery = new DevicesQueries\Configuration\FindConnectors();
		$findConnectorQuery->byIdentifier('ns-panel');
		$findConnectorQuery->byType(Entities\NsPanelConnector::TYPE);

		$connector = $connectorsConfigurationRepository->findOneBy($findConnectorQuery);
		self::assertInstanceOf(MetadataDocuments\DevicesModule\Connector::class, $connector);

		$clientFactory = $this->getContainer()->getByType(Clients\DiscoveryFactory::class);

		$client = $clientFactory->create($connector);

		$client->on('finished', static function (array $foundSubDevices): void {
			self::assertCount(1, $foundSubDevices);

			$data = [];

			foreach ($foundSubDevices as $gatewaySubDevices) {
				self::assertCount(1, $gatewaySubDevices);

				foreach ($gatewaySubDevices as $subDevice) {
					self::assertInstanceOf(Entities\Clients\DiscoveredSubDevice::class, $subDevice);

					$data[] = $subDevice->toArray();
				}
			}

			self::assertSame(
				[
					[
						'serial_number' => 'a480062416',
						'third_serial_number' => null,
						'service_address' => null,
						'name' => 'Temperature/Humidity Sensor',
						'manufacturer' => 'eWeLink',
						'model' => 'TH01',
						'firmware_version' => '0.5',
						'hostname' => null,
						'mac_address' => '00124b002a5d75b1',
						'app_name' => null,
						'display_category' => 'temperatureAndHumiditySensor',
						'capabilities' => [
							[
								'capability' => 'temperature',
								'permission' => 'read',
								'name' => null,
							],
							[
								'capability' => 'humidity',
								'permission' => 'read',
								'name' => null,
							],
							[
								'capability' => 'battery',
								'permission' => 'read',
								'name' => null,
							],
							[
								'capability' => 'rssi',
								'permission' => 'read',
								'name' => null,
							],
						],
						'protocol' => 'zigbee',
						'tags' => [
							'temperature_unit' => 'c',
						],
						'online' => true,
						'subnet' => true,
					],
				],
				$data,
			);
		});

		$client->discover();

		$eventLoop = $this->getContainer()->getByType(EventLoop\LoopInterface::class);

		$eventLoop->addTimer(1, static function () use ($eventLoop): void {
			$eventLoop->stop();
		});

		$eventLoop->run();

		$queue = $this->getContainer()->getByType(Queue\Queue::class);

		self::assertFalse($queue->isEmpty());

		$consumers = $this->getContainer()->getByType(Queue\Consumers::class);

		$consumers->consume();

		$devicesConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Devices\Repository::class,
		);

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byIdentifier('a480062416');
		$findDeviceQuery->byType(Entities\Devices\SubDevice::TYPE);

		$device = $devicesConfigurationRepository->findOneBy($findDeviceQuery);

		$deviceHelper = $this->getContainer()->getByType(Helpers\Devices\SubDevice::class);

		self::assertInstanceOf(MetadataDocuments\DevicesModule\Device::class, $device);
		self::assertSame(
			Types\Category::TEMPERATURE_HUMIDITY_SENSOR,
			$deviceHelper->getDisplayCategory($device)->getValue(),
		);
		self::assertSame('eWeLink', $deviceHelper->getManufacturer($device));
		self::assertSame('TH01', $deviceHelper->getModel($device));

		$channelsConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Channels\Repository::class,
		);

		$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelsQuery->forDevice($device);
		$findChannelsQuery->byType(Entities\NsPanelChannel::TYPE);

		$channels = $channelsConfigurationRepository->findAllBy($findChannelsQuery);

		$channelHelper = $this->getContainer()->getByType(Helpers\Channel::class);

		self::assertCount(4, $channels);

		foreach ($channels as $channel) {
			self::assertContains(
				$channelHelper->getCapability($channel)->getValue(),
				[
					Types\Capability::TEMPERATURE,
					Types\Capability::HUMIDITY,
					Types\Capability::BATTERY,
					Types\Capability::RSSI,
				],
			);

			self::assertCount(1, $channel->getProperties());
		}
	}

}
