<?php declare(strict_types = 1);

/**
 * Bridge.php
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

/**
 * Zigbee2MQTT MQTT bridge messages subscriber
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Bridge
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

			try {
				if (API\MqttValidator::validateBridge($message->getTopic())) {
					$data = API\MqttParser::parse(
						$this->connector->getId(),
						$message->getTopic(),
						$message->getPayload(),
					);

					if (array_key_exists('type', $data)) {
						if (!Types\BridgeMessageType::isValidValue($data['type'])) {
							throw new Exceptions\ParseMessage('Received unsupported bridge message type');
						}

						$type = Types\BridgeMessageType::get($data['type']);

						if ($type->equalsValue(Types\BridgeMessageType::INFO)) {
							$payload = $this->parsePayload($message);

							if ($payload === null) {
								$this->logger->warning(
									'Received message payload is not valid for bridge info message',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
										'type' => 'bridge-messages-subscriber',
										'connector' => [
											'id' => $this->connector->getId()->toString(),
										],
									],
								);

								return;
							}

							$this->queue->append(
								$this->entityHelper->create(
									Entities\Messages\StoreBridgeInfo::class,
									array_merge($data, $payload),
								),
							);

						} elseif ($type->equalsValue(Types\BridgeMessageType::STATE)) {
							if (
								$message->getPayload() !== ''
								&& Types\ConnectionState::isValidValue($message->getPayload())
							) {
								$this->queue->append(
									$this->entityHelper->create(
										Entities\Messages\StoreBridgeConnectionState::class,
										array_merge($data, ['state' => $message->getPayload()]),
									),
								);

							} else {
								$payload = $this->parsePayload($message);

								if ($payload === null) {
									$this->logger->warning(
										'Received message payload is not valid for bridge state message',
										[
											'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
											'type' => 'bridge-messages-subscriber',
											'connector' => [
												'id' => $this->connector->getId()->toString(),
											],
										],
									);

									return;
								}

								$this->queue->append(
									$this->entityHelper->create(
										Entities\Messages\StoreBridgeConnectionState::class,
										array_merge($data, $payload),
									),
								);
							}
						} elseif ($type->equalsValue(Types\BridgeMessageType::LOGGING)) {
							$payload = $this->parsePayload($message);

							if ($payload === null) {
								$this->logger->warning(
									'Received message payload is not valid for bridge log',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
										'type' => 'bridge-messages-subscriber',
										'connector' => [
											'id' => $this->connector->getId()->toString(),
										],
									],
								);

								return;
							}

							$this->queue->append(
								$this->entityHelper->create(
									Entities\Messages\StoreBridgeLog::class,
									array_merge($data, $payload),
								),
							);

						} elseif ($type->equalsValue(Types\BridgeMessageType::DEVICES)) {
							$payload = $this->parsePayload($message);

							if ($payload === null) {
								$this->logger->warning(
									'Received message payload is not valid for bridge devices message',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
										'type' => 'bridge-messages-subscriber',
										'connector' => [
											'id' => $this->connector->getId()->toString(),
										],
									],
								);

								return;
							}

							$this->queue->append(
								$this->entityHelper->create(
									Entities\Messages\StoreBridgeDevices::class,
									array_merge($data, ['devices' => $payload]),
								),
							);

						} elseif ($type->equalsValue(Types\BridgeMessageType::GROUPS)) {
							$payload = $this->parsePayload($message);

							if ($payload === null) {
								$this->logger->warning(
									'Received message payload is not valid for bridge groups message',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
										'type' => 'bridge-messages-subscriber',
										'connector' => [
											'id' => $this->connector->getId()->toString(),
										],
									],
								);

								return;
							}

							$this->queue->append(
								$this->entityHelper->create(
									Entities\Messages\StoreBridgeGroups::class,
									array_merge($data, ['groups' => $payload]),
								),
							);

						} elseif ($type->equalsValue(Types\BridgeMessageType::EVENT)) {
							$payload = $this->parsePayload($message);

							if ($payload === null) {
								$this->logger->warning(
									'Received message payload is not valid for bridge event message',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
										'type' => 'bridge-messages-subscriber',
										'connector' => [
											'id' => $this->connector->getId()->toString(),
										],
									],
								);

								return;
							}

							$this->queue->append(
								$this->entityHelper->create(
									Entities\Messages\StoreBridgeEvent::class,
									array_merge($data, $payload),
								),
							);

						} elseif ($type->equalsValue(Types\BridgeMessageType::EXTENSIONS)) {
							$payload = $this->parsePayload($message);

							if ($payload === null) {
								$this->logger->warning(
									'Received message payload is not valid for bridge extensions message',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
										'type' => 'bridge-messages-subscriber',
										'connector' => [
											'id' => $this->connector->getId()->toString(),
										],
									],
								);

								return;
							}

							// This message could be ignored
						}
					} elseif (array_key_exists('request', $data)) {
						// TODO: Handle request messages
					} elseif (array_key_exists('response', $data)) {
						// TODO: Handle response messages
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
	 * @return array<mixed>|null
	 */
	private function parsePayload(NetMqtt\Message $message): array|null
	{
		try {
			$payload = Utils\Json::decode($message->getPayload(), Utils\Json::FORCE_ARRAY);

			return $payload !== null ? (array) $payload : null;
		} catch (Utils\JsonException $ex) {
			$this->logger->error(
				'Received bridge message payload is not valid JSON message',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
					'type' => 'bridge-messages-subscriber',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			return null;
		}
	}

}
