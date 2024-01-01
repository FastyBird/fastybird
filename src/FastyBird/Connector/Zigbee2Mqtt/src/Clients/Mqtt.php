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
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use InvalidArgumentException;
use Nette;
use Throwable;
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

	// Zigbee2MQTT topics subscribe format
	public const CLIENT_TOPICS = [
		'%s/#',
	];

	private Clients\Subscribers\Bridge $bridgeSubscriber;

	private Clients\Subscribers\Device $deviceSubscriber;

	public function __construct(
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
		private readonly Clients\Subscribers\BridgeFactory $bridgeSubscriberFactory,
		private readonly Clients\Subscribers\DeviceFactory $deviceSubscriberFactory,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly Helpers\Connector $connectorHelper,
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
	 */
	public function connect(): void
	{
		$client = $this->connectionManager->getClient(
			$this->connector->getId()->toString(),
			$this->connectorHelper->getServerAddress($this->connector),
			$this->connectorHelper->getServerPort($this->connector),
			$this->connectorHelper->getUsername($this->connector),
			$this->connectorHelper->getPassword($this->connector),
		);

		$client->on('connect', [$this, 'onConnect']);
		$this->bridgeSubscriber->subscribe($client);
		$this->deviceSubscriber->subscribe($client);

		$client->connect();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function disconnect(): void
	{
		$client = $this->connectionManager->getClient(
			$this->connector->getId()->toString(),
			$this->connectorHelper->getServerAddress($this->connector),
			$this->connectorHelper->getServerPort($this->connector),
			$this->connectorHelper->getUsername($this->connector),
			$this->connectorHelper->getPassword($this->connector),
		);

		$client->disconnect();

		$client->removeListener('connect', [$this, 'onConnect']);
		$this->bridgeSubscriber->unsubscribe($client);
		$this->deviceSubscriber->unsubscribe($client);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function onConnect(): void
	{
		$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($this->connector);
		$findDevicesQuery->byType(Entities\Devices\Bridge::TYPE);

		$bridges = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);

		foreach ($bridges as $bridge) {
			// Get all topics...
			foreach (self::CLIENT_TOPICS as $topic) {
				$topic = sprintf($topic, $this->bridgeHelper->getBaseTopic($bridge));
				$topic = new NetMqtt\DefaultSubscription($topic);

				// ...& subscribe to them
				$this->connectionManager->getClient(
					$this->connector->getId()->toString(),
					$this->connectorHelper->getServerAddress($this->connector),
					$this->connectorHelper->getServerPort($this->connector),
					$this->connectorHelper->getUsername($this->connector),
					$this->connectorHelper->getPassword($this->connector),
				)
					->subscribe($topic)
					->then(
						function (mixed $subscription): void {
							assert($subscription instanceof NetMqtt\Subscription);
							$this->logger->info(
								sprintf('Subscribed to: %s', $subscription->getFilter()),
								[
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
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
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
									'type' => 'mqtt-client',
									'exception' => BootstrapHelpers\Logger::buildException($ex),
									'connector' => [
										'id' => $this->connector->getId()->toString(),
									],
								],
							);
						},
					);
			}
		}
	}

}
