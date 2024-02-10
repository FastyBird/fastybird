<?php declare(strict_types = 1);

/**
 * WriteChannelPropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           31.08.23
 */

namespace FastyBird\Connector\Shelly\Queue\Consumers;

use DateTimeInterface;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use RuntimeException;
use Throwable;
use function array_merge;
use function strval;

/**
 * Write state to device message consumer
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WriteChannelPropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Queue\Queue $queue,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Helpers\Device $deviceHelper,
		private readonly Shelly\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\WriteChannelPropertyState) {
			return false;
		}

		$findConnectorQuery = new DevicesQueries\Configuration\FindConnectors();
		$findConnectorQuery->byId($message->getConnector());
		$findConnectorQuery->byType(Entities\Connectors\Connector::TYPE);

		$connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

		if ($connector === null) {
			$this->logger->error(
				'Connector could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::SHELLY,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byId($message->getDevice());
		$findDeviceQuery->byType(Entities\Devices\Device::TYPE);

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::SHELLY,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $message->getDevice()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byId($message->getChannel());
		$findChannelQuery->byType(Entities\Channels\Channel::TYPE);

		$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			$this->logger->error(
				'Channel could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::SHELLY,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $message->getChannel()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byId($message->getProperty());

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findChannelPropertyQuery,
			MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
		);

		if ($property === null) {
			$this->logger->error(
				'Channel property could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::SHELLY,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $channel->getId()->toString(),
					],
					'property' => [
						'id' => $message->getProperty()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		if (!$property->isSettable()) {
			$this->logger->warning(
				'Channel property is not writable',
				[
					'source' => MetadataTypes\Sources\Connector::SHELLY,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $channel->getId()->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$state = $message->getState();

		if ($state === null) {
			return true;
		}

		$expectedValue = MetadataUtilities\Value::flattenValue($state->getExpectedValue());

		if ($expectedValue === null) {
			$this->channelPropertiesStatesManager->setPendingState(
				$property,
				false,
				MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::SHELLY),
			);

			return true;
		}

		$now = $this->dateTimeFactory->getNow();
		$pending = $state->getPending();

		if (
			$pending === false
			|| (
				$pending instanceof DateTimeInterface
				&& (float) $now->format('Uv') - (float) $pending->format('Uv') <= Shelly\Constants::WRITE_DEBOUNCE_DELAY
			)
		) {
			return true;
		}

		$this->channelPropertiesStatesManager->setPendingState(
			$property,
			true,
			MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::SHELLY),
		);

		try {
			if ($this->connectorHelper->getClientMode($connector)->equalsValue(Types\ClientMode::LOCAL)) {
				$address = $this->deviceHelper->getLocalAddress($device);

				if ($address === null) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::ALERT,
							],
						),
					);

					$this->channelPropertiesStatesManager->setPendingState(
						$property,
						false,
						MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::SHELLY),
					);

					$this->logger->error(
						'Device is not properly configured. Address is missing',
						[
							'source' => MetadataTypes\Sources\Connector::SHELLY,
							'type' => 'write-channel-property-state-message-consumer',
							'connector' => [
								'id' => $connector->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'channel' => [
								'id' => $channel->getId()->toString(),
							],
							'property' => [
								'id' => $property->getId()->toString(),
							],
							'data' => $message->toArray(),
						],
					);

					return true;
				}

				if (
					$this->deviceHelper->getGeneration($device)->equalsValue(Types\DeviceGeneration::GENERATION_2)
				) {
					$result = $this->connectionManager->getGen2HttpApiConnection()->setDeviceState(
						$address,
						$this->deviceHelper->getUsername($device),
						$this->deviceHelper->getPassword($device),
						$property->getIdentifier(),
						$expectedValue,
					);
				} elseif (
					$this->deviceHelper->getGeneration($device)->equalsValue(Types\DeviceGeneration::GENERATION_1)
				) {
					$result = $this->connectionManager->getGen1HttpApiConnection()->setDeviceState(
						$address,
						$this->deviceHelper->getUsername($device),
						$this->deviceHelper->getPassword($device),
						$channel->getIdentifier(),
						$property->getIdentifier(),
						$expectedValue,
					);
				} else {
					$this->channelPropertiesStatesManager->setPendingState(
						$property,
						false,
						MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::SHELLY),
					);

					return true;
				}
			} else {
				$this->channelPropertiesStatesManager->setPendingState(
					$property,
					false,
					MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::SHELLY),
				);

				return true;
			}
		} catch (Throwable $ex) {
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $device->getConnector(),
						'identifier' => $device->getIdentifier(),
						'state' => MetadataTypes\ConnectionState::ALERT,
					],
				),
			);

			$this->channelPropertiesStatesManager->setPendingState(
				$property,
				false,
				MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::SHELLY),
			);

			$this->logger->error(
				'Channel state could not be written into device',
				[
					'source' => MetadataTypes\Sources\Connector::SHELLY,
					'type' => 'write-channel-property-state-message-consumer',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $channel->getId()->toString(),
					],
					'property' => [
						'id' => $property->getId()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$result->then(
			function () use ($message, $connector, $device, $channel, $property): void {
				$this->logger->debug(
					'Channel state was successfully sent to device',
					[
						'source' => MetadataTypes\Sources\Connector::SHELLY,
						'type' => 'write-channel-property-state-message-consumer',
						'connector' => [
							'id' => $connector->getId()->toString(),
						],
						'device' => [
							'id' => $device->getId()->toString(),
						],
						'channel' => [
							'id' => $channel->getId()->toString(),
						],
						'property' => [
							'id' => $property->getId()->toString(),
						],
						'data' => $message->toArray(),
					],
				);
			},
			function (Throwable $ex) use ($connector, $device, $channel, $property, $message): void {
				$this->channelPropertiesStatesManager->setPendingState(
					$property,
					false,
					MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::SHELLY),
				);

				$extra = [];

				if ($ex instanceof Exceptions\HttpApiCall) {
					$extra = [
						'request' => [
							'method' => $ex->getRequest()?->getMethod(),
							'url' => $ex->getRequest() !== null ? strval($ex->getRequest()->getUri()) : null,
							'body' => $ex->getRequest()?->getBody()->getContents(),
						],
						'response' => [
							'body' => $ex->getResponse()?->getBody()->getContents(),
						],
					];
				}

				if ($ex instanceof Exceptions\HttpApiCall) {
					if (
						$ex->getResponse() !== null
						&& $ex->getResponse()->getStatusCode() >= StatusCodeInterface::STATUS_BAD_REQUEST
						&& $ex->getResponse()->getStatusCode() < StatusCodeInterface::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS
					) {
						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $device->getConnector(),
									'identifier' => $device->getIdentifier(),
									'state' => MetadataTypes\ConnectionState::ALERT,
								],
							),
						);

					} elseif (
						$ex->getResponse() !== null
						&& $ex->getResponse()->getStatusCode() >= StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
						&& $ex->getResponse()->getStatusCode() < StatusCodeInterface::STATUS_NETWORK_AUTHENTICATION_REQUIRED
					) {
						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $device->getConnector(),
									'identifier' => $device->getIdentifier(),
									'state' => MetadataTypes\ConnectionState::LOST,
								],
							),
						);

					} else {
						$this->queue->append(
							$this->messageBuilder->create(
								Queue\Messages\StoreDeviceConnectionState::class,
								[
									'connector' => $device->getConnector(),
									'identifier' => $device->getIdentifier(),
									'state' => MetadataTypes\ConnectionState::UNKNOWN,
								],
							),
						);
					}
				} elseif ($ex instanceof Exceptions\HttpApiError) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::ALERT,
							],
						),
					);

				} else {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::LOST,
							],
						),
					);
				}

				$this->logger->error(
					'Channel state could not be written into device',
					array_merge(
						[
							'source' => MetadataTypes\Sources\Connector::SHELLY,
							'type' => 'write-channel-property-state-message-consumer',
							'exception' => ApplicationHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $connector->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'channel' => [
								'id' => $channel->getId()->toString(),
							],
							'property' => [
								'id' => $property->getId()->toString(),
							],
							'data' => $message->toArray(),
						],
						$extra,
					),
				);
			},
		);

		$this->logger->debug(
			'Consumed write device state message',
			[
				'source' => MetadataTypes\Sources\Connector::SHELLY,
				'type' => 'write-channel-property-state-message-consumer',
				'connector' => [
					'id' => $connector->getId()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'channel' => [
					'id' => $channel->getId()->toString(),
				],
				'property' => [
					'id' => $property->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

}
