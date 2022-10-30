<?php declare(strict_types = 1);

/**
 * FbMqttV1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 * @since          0.25.0
 *
 * @date           23.02.20
 */

namespace FastyBird\Connector\FbMqtt\Clients;

use BinSoul\Net\Mqtt;
use DateTimeInterface;
use Exception;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Consumers;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette\Utils;
use Psr\Log;
use React\EventLoop;
use Throwable;
use function array_key_exists;
use function assert;
use function explode;
use function in_array;
use function is_string;
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

	/** @var Array<string> */
	private array $processedDevices = [];

	/** @var Array<string, DateTimeInterface> */
	private array $processedProperties = [];

	public function __construct(
		Entities\FbMqttConnector $connector,
		private readonly API\V1Validator $apiValidator,
		private readonly API\V1Parser $apiParser,
		private readonly API\V1Builder $apiBuilder,
		Helpers\Connector $connectorHelper,
		private readonly Helpers\Property $propertyStateHelper,
		Consumers\Messages $consumer,
		private readonly DevicesModels\States\DevicePropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModels\States\ChannelPropertiesRepository $channelPropertiesRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		EventLoop\LoopInterface $loop,
		Mqtt\ClientIdentifierGenerator|null $identifierGenerator = null,
		Mqtt\FlowFactory|null $flowFactory = null,
		Mqtt\StreamParser|null $parser = null,
		Log\LoggerInterface|null $logger = null,
	)
	{
		parent::__construct(
			$connector,
			$connectorHelper,
			$consumer,
			$loop,
			$identifierGenerator,
			$flowFactory,
			$parser,
			$logger,
		);
	}

	public function getVersion(): Types\ProtocolVersion
	{
		return Types\ProtocolVersion::get(Types\ProtocolVersion::VERSION_1);
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Exception
	 */
	protected function handleCommunication(): void
	{
		foreach ($this->processedProperties as $index => $processedProperty) {
			if ((float) $this->dateTimeFactory->getNow()->format('Uv') - (float) $processedProperty->format(
				'Uv',
			) >= 500) {
				unset($this->processedProperties[$index]);
			}
		}

		foreach ($this->connector->getDevices() as $device) {
			assert($device instanceof Entities\FbMqttDevice);

			if (
				!in_array($device->getPlainId(), $this->processedDevices, true)
				&& $this->deviceConnectionManager->getState($device)
					->equalsValue(MetadataTypes\ConnectionState::STATE_READY)
			) {
				$this->processedDevices[] = $device->getPlainId();

				if ($this->processDevice($device)) {
					$this->registerLoopHandler();

					return;
				}
			}
		}

		$this->processedDevices = [];

		$this->registerLoopHandler();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function onClose(Mqtt\Connection $connection): void
	{
		parent::onClose($connection);

		foreach ($this->connector->getDevices() as $device) {
			assert($device instanceof Entities\FbMqttDevice);

			if ($this->deviceConnectionManager->getState($device)
				->equalsValue(MetadataTypes\ConnectionState::STATE_READY)) {
				$this->deviceConnectionManager->setState(
					$device,
					MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_DISCONNECTED),
				);
			}
		}
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 */
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
							'exception' => [
								'message' => $ex->getMessage(),
								'code' => $ex->getCode(),
							],
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
								'exception' => [
									'message' => $ex->getMessage(),
									'code' => $ex->getCode(),
								],
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
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
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
	 * @throws DevicesExceptions\Terminate
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Exception
	 */
	private function processDevice(Entities\FbMqttDevice $device): bool
	{
		if ($this->writeDeviceProperty($device)) {
			return true;
		}

		return $this->writeChannelsProperty($device);
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Exception
	 */
	private function writeDeviceProperty(Entities\FbMqttDevice $device): bool
	{
		$now = $this->dateTimeFactory->getNow();

		foreach ($device->getProperties() as $property) {
			if (!$property instanceof DevicesEntities\Devices\Properties\Dynamic) {
				continue;
			}

			$state = $this->devicePropertiesRepository->findOneById($property->getId());

			if ($state === null) {
				continue;
			}

			if (
				$property->isSettable()
				&& $state->getExpectedValue() !== null
				&& $state->isPending() === true
			) {
				$pending = is_string($state->getPending())
					? Utils\DateTime::createFromFormat(
						DateTimeInterface::ATOM,
						$state->getPending(),
					)
					: true;
				$debounce = array_key_exists($property->getId()
					->toString(), $this->processedProperties) ? $this->processedProperties[$property->getId()
						->toString()] : false;

				if (
					$debounce !== false
					&& (float) $now->format('Uv') - (float) $debounce->format('Uv') < 500
				) {
					continue;
				}

				unset($this->processedProperties[$property->getPlainId()]);

				if (
					$pending === true
					|| (
						$pending !== false
						&& (float) $now->format('Uv') - (float) $pending->format('Uv') > 2_000
					)
				) {
					$this->processedProperties[$property->getPlainId()] = $now;

					$this->publish(
						$this->apiBuilder->buildDevicePropertyTopic($device, $property),
						strval($state->getExpectedValue()),
					)->then(function () use ($property, $now): void {
						$this->propertyStateHelper->setValue($property, Utils\ArrayHash::from([
							'pending' => $now->format(DateTimeInterface::ATOM),
						]));
					})->otherwise(function () use ($property): void {
						unset($this->processedProperties[$property->getPlainId()]);
					});

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Exception
	 */
	private function writeChannelsProperty(Entities\FbMqttDevice $device): bool
	{
		$now = $this->dateTimeFactory->getNow();

		foreach ($device->getChannels() as $channel) {
			foreach ($channel->getProperties() as $property) {
				if (!$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
					continue;
				}

				$state = $this->channelPropertiesRepository->findOneById($property->getId());

				if ($state === null) {
					continue;
				}

				if (
					$property->isSettable()
					&& $state->getExpectedValue() !== null
					&& $state->isPending() === true
				) {
					$pending = is_string($state->getPending())
						? Utils\DateTime::createFromFormat(
							DateTimeInterface::ATOM,
							$state->getPending(),
						)
						: true;
					$debounce = array_key_exists($property->getId()
						->toString(), $this->processedProperties) ? $this->processedProperties[$property->getId()
							->toString()] : false;

					if (
						$debounce !== false
						&& (float) $now->format('Uv') - (float) $debounce->format('Uv') < 500
					) {
						continue;
					}

					unset($this->processedProperties[$property->getPlainId()]);

					if (
						$pending === true
						|| (
							$pending !== false
							&& (float) $now->format('Uv') - (float) $pending->format('Uv') > 2_000
						)
					) {
						$this->processedProperties[$property->getPlainId()] = $now;

						$this->publish(
							$this->apiBuilder->buildChannelPropertyTopic($device, $channel, $property),
							strval($state->getExpectedValue()),
						)->then(function () use ($property, $now): void {
							$this->propertyStateHelper->setValue($property, Utils\ArrayHash::from([
								'pending' => $now->format(DateTimeInterface::ATOM),
							]));
						})->otherwise(function () use ($property): void {
							unset($this->processedProperties[$property->getPlainId()]);
						});

						return true;
					}
				}
			}
		}

		return false;
	}

}
