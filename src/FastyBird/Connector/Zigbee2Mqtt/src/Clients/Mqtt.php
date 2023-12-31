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
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Throwable;
use function array_key_exists;
use function array_map;
use function array_merge;
use function assert;
use function is_array;
use function is_scalar;
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

	public function __construct(
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly Queue\Queue $queue,
		private readonly Helpers\Entity $entityHelper,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Helpers\Devices\Bridge $bridgeHelper,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
	)
	{
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
		$client->on('message', [$this, 'onMessage']);

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
		$client->removeListener('message', [$this, 'onMessage']);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
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
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $this->connector->getId(),
						'identifier' => $bridge->getIdentifier(),
						'state' => MetadataTypes\ConnectionState::STATE_CONNECTED,
					],
				),
			);

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

	/**
	 * @throws Exceptions\Runtime
	 */
	private function onMessage(NetMqtt\Message $message): void
	{
		if (API\MqttValidator::validateTopic($message->getTopic())) {
			// Check if message is sent from broker
			if (!API\MqttValidator::validate($message->getTopic())) {
				return;
			}

			try {
				if (API\MqttValidator::validateBridge($message->getTopic())) {
					try {
						$data = array_merge(
							API\MqttParser::parse(
								$this->connector->getId(),
								$message->getTopic(),
								$message->getPayload(),
								$message->isRetained(),
							),
							(array) Utils\Json::decode($message->getPayload(), Utils\Json::FORCE_ARRAY),
						);
					} catch (Utils\JsonException $ex) {
						throw new Exceptions\ParseMessage(
							'Bridge message payload could not be parsed',
							$ex->getCode(),
							$ex,
						);
					}

					if (array_key_exists('type', $data)) {
						if (!Types\BridgeMessageType::isValidValue($data['type'])) {
							throw new Exceptions\ParseMessage('Received unsupported bridge message type');
						}

						$type = Types\BridgeMessageType::get($data['type']);

						if ($type->equalsValue(Types\BridgeMessageType::INFO)) {
							$this->queue->append(
								$this->entityHelper->create(Entities\Messages\StoreBridgeInfo::class, $data),
							);

						} elseif ($type->equalsValue(Types\BridgeMessageType::STATE)) {
							$this->queue->append(
								$this->entityHelper->create(Entities\Messages\StoreBridgeConnectionState::class, $data),
							);

						} elseif ($type->equalsValue(Types\BridgeMessageType::LOGGING)) {
							$this->queue->append(
								$this->entityHelper->create(Entities\Messages\StoreBridgeLog::class, $data),
							);

						} elseif ($type->equalsValue(Types\BridgeMessageType::DEVICES)) {
							$this->queue->append(
								$this->entityHelper->create(Entities\Messages\StoreBridgeDevices::class, $data),
							);

						} elseif ($type->equalsValue(Types\BridgeMessageType::GROUPS)) {
							$this->queue->append(
								$this->entityHelper->create(Entities\Messages\StoreBridgeGroups::class, $data),
							);

						} elseif ($type->equalsValue(Types\BridgeMessageType::EVENT)) {
							$this->queue->append(
								$this->entityHelper->create(Entities\Messages\StoreBridgeEvent::class, $data),
							);
						}
					} elseif (array_key_exists('request', $data)) {
						// TODO: Handle request messages
					}
				} elseif (API\MqttValidator::validateDevice($message->getTopic())) {
					try {
						$data = array_merge(
							API\MqttParser::parse(
								$this->connector->getId(),
								$message->getTopic(),
								$message->getPayload(),
								$message->isRetained(),
							),
							(array) Utils\Json::decode($message->getPayload(), Utils\Json::FORCE_ARRAY),
						);
					} catch (Utils\JsonException $ex) {
						throw new Exceptions\ParseMessage(
							'Bridge message payload could not be parsed',
							$ex->getCode(),
							$ex,
						);
					}

					if (array_key_exists('type', $data)) {
						if (!Types\DeviceMessageType::isValidValue($data['type'])) {
							throw new Exceptions\ParseMessage('Received unsupported bridge message type');
						}

						$type = Types\DeviceMessageType::get($data['type']);

						if ($type->equalsValue(Types\DeviceMessageType::AVAILABILITY)) {
							$this->queue->append(
								$this->entityHelper->create(Entities\Messages\StoreDeviceConnectionState::class, $data),
							);
						} elseif ($type->equalsValue(Types\DeviceMessageType::GET)) {
							// Handle GET data
							$this->logger->error(
								'No handler for GET message type',
								[
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
									'type' => 'mqtt-client',
									'payload' => $message->getPayload(),
								],
							);
						}
					} else {
						try {
							$payload = (array) Utils\Json::decode($message->getPayload(), Utils\Json::FORCE_ARRAY);

						} catch (Utils\JsonException $ex) {
							throw new Exceptions\ParseMessage(
								'Bridge message payload could not be parsed',
								$ex->getCode(),
								$ex,
							);
						}

						$data['states'] = $this->convertStatePayload($payload);

						$this->queue->append(
							$this->entityHelper->create(Entities\Messages\StoreDeviceState::class, $data),
						);
					}
				}
			} catch (Exceptions\ParseMessage $ex) {
				$this->logger->debug(
					'Received message could not be successfully parsed to entity',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
						'type' => 'mqtt-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);
			}
		}
	}

	/**
	 * @param array<mixed> $payload
	 *
	 * @return array<int, array<string, string|int|float|bool|null>>
	 */
	private function convertStatePayload(array $payload): array
	{
		$converted = [];

		foreach ($payload as $key => $value) {
			if (is_scalar($value)) {
				$converted[] = [
					'identifier' => $key,
					'value' => $value,
				];
			} elseif (is_array($value)) {
				$converted = array_merge(
					$converted,
					array_map(static function (array $item) use ($key): array {
						$item['parent'] = $key;

						return $item;
					}, $this->convertStatePayload($value)),
				);
			}
		}

		return $converted;
	}

}
