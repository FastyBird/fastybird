<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           01.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Clients\Subscribers;

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
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette\Utils;
use function array_key_exists;
use function array_merge;
use function is_array;

/**
 * Zigbee2MQTT MQTT devices messages subscriber
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Device
{

	public function __construct(
		private readonly MetadataDocuments\DevicesModule\Connector|Entities\Zigbee2MqttConnector $connector,
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly Queue\Queue $queue,
		private readonly Helpers\Entity $entityHelper,
	)
	{
	}

	public function subscribe(API\Client $client): void
	{
		$client->on('message', [$this, 'onMessage']);
	}

	public function unsubscribe(API\Client $client): void
	{
		$client->removeListener('message', [$this, 'onMessage']);
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function onMessage(NetMqtt\Message $message): void
	{
		if (API\MqttValidator::validateTopic($message->getTopic())) {
			// Check if message is sent from broker
			if (!API\MqttValidator::validate($message->getTopic())) {
				return;
			}

			// Skip messages related to bridge
			if (API\MqttValidator::validateBridge($message->getTopic())) {
				return;
			}

			try {
				if (API\MqttValidator::validateDevice($message->getTopic())) {
					$data = API\MqttParser::parse(
						$this->connector->getId(),
						$message->getTopic(),
						$message->getPayload(),
					);

					try {
						$payload = Utils\Json::decode($message->getPayload(), Utils\Json::FORCE_ARRAY);
						$payload = $payload !== null ? (array) $payload : null;
					} catch (Utils\JsonException $ex) {
						throw new Exceptions\ParseMessage(
							'Received device message payload is not valid JSON message',
							$ex->getCode(),
							$ex,
						);
					}

					if (array_key_exists('type', $data)) {
						if (!Types\DeviceMessageType::isValidValue($data['type'])) {
							throw new Exceptions\ParseMessage('Received unsupported device message type');
						}

						$type = Types\DeviceMessageType::get($data['type']);

						if ($type->equalsValue(Types\DeviceMessageType::AVAILABILITY)) {
							if ($payload === null && Types\ConnectionState::isValidValue($message->getPayload())) {
								$this->queue->append(
									$this->entityHelper->create(
										Entities\Messages\StoreDeviceConnectionState::class,
										array_merge($data, ['state' => $message->getPayload()]),
									),
								);

							} elseif ($payload !== null) {
								$this->queue->append(
									$this->entityHelper->create(
										Entities\Messages\StoreDeviceConnectionState::class,
										array_merge($data, $payload),
									),
								);
							}
						} elseif ($type->equalsValue(Types\DeviceMessageType::GET) && $payload !== null) {
							// Handle GET data
							$this->logger->error(
								'No handler for GET message type',
								[
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
									'type' => 'bridge-messages-subscriber',
									'payload' => $message->getPayload(),
								],
							);
						}
					} elseif ($payload !== null) {
						$this->queue->append(
							$this->entityHelper->create(
								Entities\Messages\StoreDeviceState::class,
								array_merge($data, ['states' => $this->convertStatePayload($payload)]),
							),
						);
					}
				}
			} catch (Exceptions\ParseMessage | Exceptions\InvalidArgument $ex) {
				$this->logger->debug(
					'Received message could not be successfully parsed to entity',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
						'type' => 'bridge-messages-subscriber',
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
	 * @return array<mixed>
	 */
	private function convertStatePayload(array $payload): array
	{
		$converted = [];

		foreach ($payload as $key => $value) {
			$converted[] = is_array($value) ? [
				'type' => Types\ExposeDataType::COMPOSITE,
				'identifier' => $key,
				'states' => $this->convertStatePayload($value),
			] : [
				'type' => Types\ExposeDataType::SINGLE,
				'identifier' => $key,
				'value' => $value,
			];
		}

		return $converted;
	}

}
