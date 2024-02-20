<?php declare(strict_types = 1);

/**
 * Mqtt.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           24.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Clients;

use BinSoul\Net\Mqtt as NetMqtt;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\API;
use FastyBird\Connector\Zigbee2Mqtt\Clients;
use FastyBird\Connector\Zigbee2Mqtt\Documents;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use InvalidArgumentException;
use Nette;
use Throwable;
use TypeError;
use ValueError;
use function assert;
use function sprintf;

/**
 * Zigbee2MQTT MQTT client
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Mqtt implements Client
{

	use Nette\SmartObject;

	private Clients\Subscribers\Bridge $bridgeSubscriber;

	private Clients\Subscribers\Device $deviceSubscriber;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly Clients\Subscribers\BridgeFactory $bridgeSubscriberFactory,
		private readonly Clients\Subscribers\DeviceFactory $deviceSubscriberFactory,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly Helpers\Connectors\Connector $connectorHelper,
		private readonly Helpers\Devices\Bridge $bridgeHelper,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
	)
	{
		$this->bridgeSubscriber = $this->bridgeSubscriberFactory->create($this->connector);
		$this->deviceSubscriber = $this->deviceSubscriberFactory->create($this->connector);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function connect(): void
	{
		$client = $this->getClient();
		$client->onConnect[] = function (): void {
			$this->onConnect();
		};

		$this->bridgeSubscriber->subscribe($client);
		$this->deviceSubscriber->subscribe($client);

		$client->connect();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function disconnect(): void
	{
		$client = $this->getClient();

		$client->disconnect();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function onConnect(): void
	{
		$findDevicesQuery = new Queries\Configuration\FindBridgeDevices();
		$findDevicesQuery->forConnector($this->connector);

		$bridges = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Bridge::class,
		);

		foreach ($bridges as $bridge) {
			$topic = sprintf(Zigbee2Mqtt\Constants::BRIDGE_TOPIC, $this->bridgeHelper->getBaseTopic($bridge));
			$topic = new NetMqtt\DefaultSubscription($topic);

			$this->getClient()
				->subscribe($topic)
				->then(
					function (mixed $subscription): void {
						assert($subscription instanceof NetMqtt\Subscription);

						$this->logger->info(
							sprintf('Subscribed to: %s', $subscription->getFilter()),
							[
								'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
								'type' => 'mqtt-client',
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
							],
						);
					},
					function (Throwable $ex): void {
						$this->logger->error(
							$ex->getMessage(),
							[
								'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
								'type' => 'mqtt-client',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
							],
						);
					},
				);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function getClient(): API\Client
	{
		return $this->connectionManager->getClient(
			$this->connector->getId()->toString(),
			$this->connectorHelper->getServerAddress($this->connector),
			$this->connectorHelper->getServerPort($this->connector),
			$this->connectorHelper->getUsername($this->connector),
			$this->connectorHelper->getPassword($this->connector),
		);
	}

}
