<?php declare(strict_types = 1);

/**
 * FbMqttV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           23.02.20
 */

namespace FastyBird\Connector\FbMqtt\Clients;

use BinSoul\Net\Mqtt;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Consumers;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Writers;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use Throwable;
use function explode;
use function sprintf;
use function str_contains;
use function strval;

/**
 * FastyBird MQTT v1 client
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class FbMqttV1 extends Client
{

	public const MQTT_SYSTEM_TOPIC = '$SYS/broker/log/#';

	// MQTT api topics subscribe format
	public const DEVICES_TOPICS = [
		FbMqtt\Constants::MQTT_API_PREFIX . FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX . '/+/+',
		FbMqtt\Constants::MQTT_API_PREFIX . FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX . '/+/+/+',
		FbMqtt\Constants::MQTT_API_PREFIX . FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX . '/+/+/+/+',
		FbMqtt\Constants::MQTT_API_PREFIX . FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX . '/+/+/+/+/+',
		FbMqtt\Constants::MQTT_API_PREFIX . FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX . '/+/+/+/+/+/+',
		FbMqtt\Constants::MQTT_API_PREFIX . FbMqtt\Constants::MQTT_API_V1_VERSION_PREFIX . '/+/+/+/+/+/+/+',
	];

	// When new client is connected, broker send specific payload
	private const NEW_CLIENT_MESSAGE_PAYLOAD = 'New client connected from';

	public function __construct(
		Entities\FbMqttConnector $connector,
		private readonly API\V1Validator $apiValidator,
		private readonly API\V1Parser $apiParser,
		private readonly API\V1Builder $apiBuilder,
		Consumers\Messages $consumer,
		Writers\Writer $writer,
		private readonly DevicesUtilities\DevicePropertiesStates $devicePropertiesStates,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		EventLoop\LoopInterface $loop,
		Mqtt\ClientIdentifierGenerator|null $identifierGenerator = null,
		Mqtt\FlowFactory|null $flowFactory = null,
		Mqtt\StreamParser|null $parser = null,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
		parent::__construct(
			$connector,
			$consumer,
			$writer,
			$loop,
			$identifierGenerator,
			$flowFactory,
			$parser,
			$logger,
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeProperty(
		Entities\FbMqttDevice $device,
		DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Channels\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		if ($property instanceof DevicesEntities\Devices\Properties\Dynamic) {
			return $this->writeDeviceProperty($device, $property);
		}

		return $this->writeChannelProperty($device, $property);
	}

	protected function onConnect(Mqtt\Connection $connection): void
	{
		parent::onConnect($connection);

		$systemTopic = new Mqtt\DefaultSubscription(self::MQTT_SYSTEM_TOPIC);

		// Subscribe to system topic
		$this
			->subscribe($systemTopic)
			->done(
				function (Mqtt\Subscription $subscription): void {
					$this->logger->info(
						sprintf('Subscribed to: %s', $subscription->getFilter()),
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
							'type' => 'fb-mqtt-v1-client',
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
						],
					);
				},
				function (Throwable $ex): void {
					$this->logger->error(
						$ex->getMessage(),
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
							'type' => 'fb-mqtt-v1-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
						],
					);
				},
			);

		// Get all device topics...
		foreach (self::DEVICES_TOPICS as $topic) {
			$topic = new Mqtt\DefaultSubscription($topic);

			// ...& subscribe to them
			$this
				->subscribe($topic)
				->done(
					function (Mqtt\Subscription $subscription): void {
						$this->logger->info(
							sprintf('Subscribed to: %s', $subscription->getFilter()),
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
								'type' => 'fb-mqtt-v1-client',
								'connector' => [
									'id' => $this->connector->getPlainId(),
								],
							],
						);
					},
					function (Throwable $ex): void {
						$this->logger->error(
							$ex->getMessage(),
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
								'type' => 'fb-mqtt-v1-client',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getPlainId(),
								],
							],
						);
					},
				);
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	protected function onMessage(Mqtt\Message $message): void
	{
		parent::onMessage($message);

		// Check for broker system topic
		if (str_contains($message->getTopic(), '$SYS')) {
			[,
				$param1,
				$param2,
				$param3,
			] = explode(FbMqtt\Constants::MQTT_TOPIC_DELIMITER, $message->getTopic()) + [
				null,
				null,
				null,
				null,
			];

			$payload = $message->getPayload();

			// Broker log
			if ($param1 === 'broker' && $param2 === 'log') {
				switch ($param3) {
					// Notice
					case 'N':
						$this->logger->notice(
							$payload,
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
								'type' => 'fb-mqtt-v1-client',
								'connector' => [
									'id' => $this->connector->getPlainId(),
								],
							],
						);

						// Nev device connected message
						if (str_contains($message->getPayload(), self::NEW_CLIENT_MESSAGE_PAYLOAD)) {
							[,,,,,
								$ipAddress,,
								$deviceId,,,
								$username,
							] = explode(' ', $message->getPayload()) + [
								null,
								null,
								null,
								null,
								null,
								null,
								null,
								null,
								null,
								null,
								null,
							];

							// Check for correct data
							if ($username !== null && $deviceId !== null && $ipAddress !== null) {
								$entity = new Entities\Messages\DeviceProperty(
									$this->connector->getId(),
									$deviceId,
									'ip-address',
								);
								$entity->setValue($ipAddress);

								$this->consumer->append($entity);
							}
						}

						break;

					// Error
					case 'E':
						$this->logger->error(
							$payload,
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
								'type' => 'fb-mqtt-v1-client',
								'connector' => [
									'id' => $this->connector->getPlainId(),
								],
							],
						);

						break;

					// Information
					case 'I':
						$this->logger->info(
							$payload,
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
								'type' => 'fb-mqtt-v1-client',
								'connector' => [
									'id' => $this->connector->getPlainId(),
								],
							],
						);

						break;
					default:
						$this->logger->debug(
							$param3 . ': ' . $payload,
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
								'type' => 'fb-mqtt-v1-client',
								'connector' => [
									'id' => $this->connector->getPlainId(),
								],
							],
						);

						break;
				}
			}

			return;
		}

		// Connected device topic
		if (
			$this->apiValidator->validateConvention($message->getTopic())
			&& $this->apiValidator->validateVersion($message->getTopic())
		) {
			// Check if message is sent from broker
			if (!$this->apiValidator->validate($message->getTopic())) {
				return;
			}

			try {
				$entity = $this->apiParser->parse(
					$this->connector->getId(),
					$message->getTopic(),
					$message->getPayload(),
					$message->isRetained(),
				);

			} catch (Exceptions\ParseMessage $ex) {
				$this->logger->debug(
					'Received message could not be successfully parsed to entity',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
						'type' => 'fb-mqtt-v1-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
					],
				);

				return;
			}

			$this->consumer->append($entity);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function writeDeviceProperty(
		Entities\FbMqttDevice $device,
		DevicesEntities\Devices\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		$state = $this->devicePropertiesStates->getValue($property);

		if (
			$state?->getExpectedValue() !== null
			&& $state->isPending() === true
		) {
			return $this->publish(
				$this->apiBuilder->buildDevicePropertyTopic($device, $property),
				strval(DevicesUtilities\ValueHelper::flattenValue($state->getExpectedValue())),
			);
		}

		return Promise\reject(new Exceptions\InvalidArgument('Provided property state is in invalid state'));
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function writeChannelProperty(
		Entities\FbMqttDevice $device,
		DevicesEntities\Channels\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		$state = $this->channelPropertiesStates->getValue($property);

		if (
			$state?->getExpectedValue() !== null
			&& $state->isPending() === true
		) {
			return $this->publish(
				$this->apiBuilder->buildChannelPropertyTopic($device, $property->getChannel(), $property),
				strval(DevicesUtilities\ValueHelper::flattenValue($state->getExpectedValue())),
			);
		}

		return Promise\reject(new Exceptions\InvalidArgument('Provided property state is in invalid state'));
	}

}
