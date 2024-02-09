<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\Clients;

use BinSoul\Net\Mqtt as NetMqtt;
use Error;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\API;
use FastyBird\Connector\Zigbee2Mqtt\Clients;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Tests;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use InvalidArgumentException;
use Nette\DI;
use Nette\Utils;
use React;
use React\EventLoop;
use RuntimeException;
use function array_diff;
use function in_array;
use function sprintf;

final class DiscoveryTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function testDiscover(): void
	{
		$subscribePromise = $this->createMock(React\Promise\PromiseInterface::class);
		$subscribePromise
			->method('then')
			->with(
				self::callback(static function (callable $callback): bool {
					$topic = sprintf(Zigbee2Mqtt\Constants::BRIDGE_TOPIC, Entities\Devices\Bridge::BASE_TOPIC);
					$subscription = new NetMqtt\DefaultSubscription($topic);

					$callback($subscription);

					return true;
				}),
				self::callback(static fn (): bool => true),
			);

		$publishPromise = $this->createMock(React\Promise\PromiseInterface::class);
		$publishPromise
			->method('then')
			->with(
				self::callback(static function (callable $callback): bool {
					$callback();

					return true;
				}),
				self::callback(static fn (): bool => true),
			);

		$apiClient = $this->createMock(API\Client::class);
		$apiClient
			->expects(self::exactly(2))
			->method('on')
			->with(
				self::callback(static function (string $event): bool {
					self::assertTrue(in_array($event, ['connect', 'message'], true));

					return true;
				}),
				self::callback(static function ($callback): bool {
					if ($callback[1] === 'onConnect') {
						$callback();
					} elseif ($callback[1] === 'onMessage') {
						$message = new NetMqtt\DefaultMessage(
							'zigbee2mqtt/bridge/devices',
							Utils\FileSystem::read(__DIR__ . '/../../../fixtures/Clients/Messages/bridge_devices.json'),
						);

						$callback($message);
					}

					return true;
				}),
			);
		$apiClient
			->expects(self::exactly(2))
			->method('removeListener')
			->with(
				self::callback(static function (string $event): bool {
					self::assertTrue(in_array($event, ['connect', 'message'], true));

					return true;
				}),
				self::callback(static fn (): bool => true),
			);
		$apiClient
			->method('subscribe')
			->willReturn($subscribePromise);
		$apiClient
			->method('publish')
			->willReturn($publishPromise);

		$connectionManager = $this->createMock(API\ConnectionManager::class);
		$connectionManager
			->method('getClient')
			->willReturn($apiClient);

		$this->mockContainerService(
			API\ConnectionManager::class,
			$connectionManager,
		);

		$connectorsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Connectors\ConnectorsRepository::class,
		);

		$findConnectorQuery = new Queries\Entities\FindConnectors();
		$findConnectorQuery->byIdentifier('zigbee2mqtt');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\Connectors\Connector::class);
		self::assertInstanceOf(Entities\Connectors\Connector::class, $connector);

		$connectorsConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Connectors\Repository::class,
		);

		$findConnectorQuery = new DevicesQueries\Configuration\FindConnectors();
		$findConnectorQuery->byIdentifier('zigbee2mqtt');
		$findConnectorQuery->byType(Entities\Connectors\Connector::TYPE);

		$connectorDocument = $connectorsConfigurationRepository->findOneBy($findConnectorQuery);
		self::assertInstanceOf(MetadataDocuments\DevicesModule\Connector::class, $connectorDocument);

		self::assertEquals($connector->getId(), $connectorDocument->getId());

		$clientFactory = $this->getContainer()->getByType(Clients\DiscoveryFactory::class);

		$client = $clientFactory->create($connectorDocument);

		$client->discover();

		$eventLoop = $this->getContainer()->getByType(EventLoop\LoopInterface::class);

		$eventLoop->addTimer(1, static function () use ($eventLoop, $client): void {
			$client->disconnect();

			$eventLoop->stop();
		});

		$eventLoop->run();

		$queue = $this->getContainer()->getByType(Queue\Queue::class);

		self::assertFalse($queue->isEmpty());

		$consumers = $this->getContainer()->getByType(Queue\Consumers::class);

		$consumers->consume();

		$devicesRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Devices\DevicesRepository::class,
		);

		$findDeviceQuery = new Queries\Entities\FindSubDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byIdentifier('0xa4c138f06eafa3da');

		$device = $devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\SubDevice::class);

		self::assertInstanceOf(Entities\Devices\SubDevice::class, $device);

		$channelsRepository = $this->getContainer()->getByType(
			DevicesModels\Entities\Channels\ChannelsRepository::class,
		);

		$findChannelsQuery = new Queries\Entities\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $channelsRepository->findAllBy($findChannelsQuery, Entities\Channels\Channel::class);

		self::assertCount(6, $channels);

		$data = [];

		foreach ($channels as $channel) {
			$data[$channel->getIdentifier()] = $channel->getName();
		}

		$expected = [
			'numeric_voltage' => 'Voltage',
			'binary_occupancy' => 'Occupancy',
			'numeric_linkquality' => 'Linkquality',
			'numeric_battery' => 'Battery',
			'binary_tamper' => 'Tamper',
			'binary_battery_low' => 'Battery low',
		];

		self::assertEmpty(array_diff($expected, $data));
	}

}
