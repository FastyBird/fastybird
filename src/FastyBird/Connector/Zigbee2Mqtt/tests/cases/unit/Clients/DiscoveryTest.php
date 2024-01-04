<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Cases\Unit\Clients;

use BinSoul\Net\Mqtt as NetMqtt;
use Error;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\API;
use FastyBird\Connector\Zigbee2Mqtt\Clients;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Tests;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
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
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Error
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Utils\JsonException
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

		$connectorsConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Connectors\Repository::class,
		);

		$findConnectorQuery = new DevicesQueries\Configuration\FindConnectors();
		$findConnectorQuery->byIdentifier('zigbee2mqtt');

		$connector = $connectorsConfigurationRepository->findOneBy($findConnectorQuery);
		self::assertInstanceOf(MetadataDocuments\DevicesModule\Connector::class, $connector);

		$clientFactory = $this->getContainer()->getByType(Clients\DiscoveryFactory::class);

		$client = $clientFactory->create($connector);

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

		$devicesConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Devices\Repository::class,
		);

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byIdentifier('0xa4c138f06eafa3da');
		$findDeviceQuery->byType(Entities\Devices\SubDevice::TYPE);

		$device = $devicesConfigurationRepository->findOneBy($findDeviceQuery);

		self::assertInstanceOf(MetadataDocuments\DevicesModule\Device::class, $device);
		self::assertSame(Entities\Devices\SubDevice::TYPE, $device->getType());

		$channelsConfigurationRepository = $this->getContainer()->getByType(
			DevicesModels\Configuration\Channels\Repository::class,
		);

		$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $channelsConfigurationRepository->findAllBy($findChannelsQuery);

		self::assertCount(6, $channels);

		$data = [];

		foreach ($channels as $channel) {
			$data[] = [
				'identifier' => $channel->getIdentifier(),
				'name' => $channel->getName(),
			];
		}

		self::assertTrue(file_exists(__DIR__ . '/../../../fixtures/Clients/Documents/channels.json'));

		$expected = Utils\Json::decode(
			Utils\FileSystem::read(__DIR__ . '/../../../fixtures/Clients/Documents/channels.json'),
			Utils\Json::FORCE_ARRAY,
		);

		self::assertTrue(is_array($expected));
		self::assertTrue(array_diff($expected, $data) === []);
	}

}
