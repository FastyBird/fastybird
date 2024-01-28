<?php declare(strict_types = 1);

/**
 * WriteChannelPropertyState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           18.07.23
 */

namespace FastyBird\Connector\Viera\Queue\Consumers;

use DateTimeInterface;
use FastyBird\Connector\Viera;
use FastyBird\Connector\Viera\API;
use FastyBird\Connector\Viera\Entities;
use FastyBird\Connector\Viera\Exceptions;
use FastyBird\Connector\Viera\Helpers;
use FastyBird\Connector\Viera\Queue;
use FastyBird\Connector\Viera\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use RuntimeException;
use Throwable;
use function boolval;
use function intval;
use function strval;

/**
 * Write state to device message consumer
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WriteChannelPropertyState implements Queue\Consumer
{

	use Nette\SmartObject;

	private const WRITE_PENDING_DELAY = 2_000.0;

	public function __construct(
		private readonly Queue\Queue $queue,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\Entity $entityHelper,
		private readonly Helpers\Device $deviceHelper,
		private readonly Viera\Logger $logger,
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\WriteChannelPropertyState) {
			return false;
		}

		$findConnectorQuery = new DevicesQueries\Configuration\FindConnectors();
		$findConnectorQuery->byId($entity->getConnector());
		$findConnectorQuery->byType(Entities\VieraConnector::TYPE);

		$connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

		if ($connector === null) {
			$this->logger->error(
				'Connector could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::VIERA,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byId($entity->getDevice());
		$findDeviceQuery->byType(Entities\VieraDevice::TYPE);

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::VIERA,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $entity->getDevice()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		if ($this->deviceHelper->getIpAddress($device) === null) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId(),
						'device' => $device->getId(),
						'state' => MetadataTypes\ConnectionState::ALERT,
					],
				),
			);

			$this->logger->error(
				'Device is not configured',
				[
					'source' => MetadataTypes\ConnectorSource::VIERA,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byId($entity->getChannel());
		$findChannelQuery->byType(Entities\VieraChannel::TYPE);

		$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			$this->logger->error(
				'Channel could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::VIERA,
					'type' => 'write-channel-property-state-message-consumer',
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $entity->getChannel()->toString(),
					],
					'property' => [
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byId($entity->getProperty());

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
			$findChannelPropertyQuery,
			MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
		);

		if ($property === null) {
			$this->logger->error(
				'Channel property could not be loaded',
				[
					'source' => MetadataTypes\ConnectorSource::VIERA,
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
						'id' => $entity->getProperty()->toString(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		if (!$property->isSettable()) {
			$this->logger->warning(
				'Channel property is not writable',
				[
					'source' => MetadataTypes\ConnectorSource::VIERA,
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
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$state = $entity->getState();

		if ($state === null) {
			return true;
		}

		$expectedValue = MetadataUtilities\Value::flattenValue(
			$state->getExpectedValue(),
		);

		if ($expectedValue === null) {
			$this->channelPropertiesStatesManager->setPendingState(
				$property,
				false,
				MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::VIERA),
			);

			return true;
		}

		$now = $this->dateTimeFactory->getNow();
		$pending = $state->getPending();

		if (
			$pending === false
			|| (
				$pending instanceof DateTimeInterface
				&& (float) $now->format('Uv') - (float) $pending->format('Uv') <= self::WRITE_PENDING_DELAY
			)
		) {
			return true;
		}

		$this->channelPropertiesStatesManager->setPendingState(
			$property,
			true,
			MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::VIERA),
		);

		try {
			$client = $this->connectionManager->getConnection($device);

			if (!$client->isConnected()) {
				$client->connect();
			}

			switch ($property->getIdentifier()) {
				case Types\ChannelPropertyIdentifier::STATE:
					$result = $expectedValue === true ? $client->turnOn() : $client->turnOff();

					break;
				case Types\ChannelPropertyIdentifier::VOLUME:
					$result = $client->setVolume(intval($expectedValue));

					break;
				case Types\ChannelPropertyIdentifier::MUTE:
					$result = $client->setMute(boolval($expectedValue));

					break;
				case Types\ChannelPropertyIdentifier::INPUT_SOURCE:
					if (intval($expectedValue) < 100) {
						$result = $client->sendKey('NRC_HDMI' . $expectedValue . '-ONOFF');
					} elseif (intval($expectedValue) === 500) {
						$result = $client->sendKey(Types\ActionKey::get(Types\ActionKey::AD_CHANGE));
					} else {
						$result = $client->launchApplication(strval($expectedValue));
					}

					break;
				case Types\ChannelPropertyIdentifier::APPLICATION:
					$result = $client->launchApplication(strval($expectedValue));

					break;
				case Types\ChannelPropertyIdentifier::HDMI:
					$result = $client->sendKey('NRC_HDMI' . $expectedValue . '-ONOFF');

					break;
				default:
					if (
						Types\ChannelPropertyIdentifier::isValidValue($property->getIdentifier())
						&& $property->getDataType()->equalsValue(MetadataTypes\DataType::BUTTON)
					) {
						$result = $client->sendKey(Types\ActionKey::get($expectedValue));
					} else {
						$this->channelPropertiesStatesManager->setPendingState(
							$property,
							false,
							MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::VIERA),
						);

						$this->logger->error(
							'Provided property is not supported for writing',
							[
								'source' => MetadataTypes\ConnectorSource::VIERA,
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
								'data' => $entity->toArray(),
							],
						);

						return true;
					}

					break;
			}
		} catch (Exceptions\InvalidState $ex) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId(),
						'device' => $device->getId(),
						'state' => MetadataTypes\ConnectionState::ALERT,
					],
				),
			);

			$this->channelPropertiesStatesManager->setPendingState(
				$property,
				false,
				MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::VIERA),
			);

			$this->logger->error(
				'Device is not properly configured',
				[
					'source' => MetadataTypes\ConnectorSource::VIERA,
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
					'data' => $entity->toArray(),
				],
			);

			return true;
		} catch (Exceptions\TelevisionApiError $ex) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId(),
						'device' => $device->getId(),
						'state' => MetadataTypes\ConnectionState::ALERT,
					],
				),
			);

			$this->channelPropertiesStatesManager->setPendingState(
				$property,
				false,
				MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::VIERA),
			);

			$this->logger->error(
				'Preparing api request failed',
				[
					'source' => MetadataTypes\ConnectorSource::VIERA,
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
					'data' => $entity->toArray(),
				],
			);

			return true;
		} catch (Exceptions\TelevisionApiCall $ex) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId(),
						'device' => $device->getId(),
						'state' => MetadataTypes\ConnectionState::DISCONNECTED,
					],
				),
			);

			$this->channelPropertiesStatesManager->setPendingState(
				$property,
				false,
				MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::VIERA),
			);

			$this->logger->error(
				'Calling device api failed',
				[
					'source' => MetadataTypes\ConnectorSource::VIERA,
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
					'request' => [
						'method' => $ex->getRequest()?->getMethod(),
						'url' => $ex->getRequest() !== null ? strval($ex->getRequest()->getUri()) : null,
						'body' => $ex->getRequest()?->getBody()->getContents(),
					],
					'response' => [
						'body' => $ex->getResponse()?->getBody()->getContents(),
					],
					'data' => $entity->toArray(),
				],
			);

			return true;
		}

		$result->then(
			function () use ($entity, $connector, $device, $channel, $property, $expectedValue): void {
				$this->logger->debug(
					'Channel state was successfully sent to device',
					[
						'source' => MetadataTypes\ConnectorSource::VIERA,
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
						'data' => $entity->toArray(),
					],
				);

				switch ($property->getIdentifier()) {
					case Types\ChannelPropertyIdentifier::STATE:
					case Types\ChannelPropertyIdentifier::VOLUME:
					case Types\ChannelPropertyIdentifier::MUTE:
					case Types\ChannelPropertyIdentifier::INPUT_SOURCE:
					case Types\ChannelPropertyIdentifier::APPLICATION:
					case Types\ChannelPropertyIdentifier::HDMI:
						$this->queue->append(
							$this->entityHelper->create(
								Entities\Messages\StoreChannelPropertyState::class,
								[
									'connector' => $connector->getId(),
									'device' => $device->getId(),
									'channel' => Types\ChannelType::TELEVISION,
									'property' => $property->getId(),
									'value' => $expectedValue,
								],
							),
						);

						break;
					default:
						if (
							Types\ChannelPropertyIdentifier::isValidValue($property->getIdentifier())
							&& $property->getDataType()->equalsValue(MetadataTypes\DataType::BUTTON)
						) {
							$this->queue->append(
								$this->entityHelper->create(
									Entities\Messages\StoreChannelPropertyState::class,
									[
										'connector' => $connector->getId(),
										'device' => $device->getId(),
										'channel' => Types\ChannelType::TELEVISION,
										'property' => $property->getId(),
										'value' => $expectedValue,
									],
								),
							);
						}

						break;
				}

				if ($property->getIdentifier() === Types\ChannelPropertyIdentifier::INPUT_SOURCE) {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreChannelPropertyState::class,
							[
								'connector' => $connector->getId(),
								'device' => $device->getId(),
								'channel' => Types\ChannelType::TELEVISION,
								'property' => Types\ChannelPropertyIdentifier::HDMI,
								'value' => intval($expectedValue) < 100 ? $expectedValue : null,
							],
						),
					);

					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreChannelPropertyState::class,
							[
								'connector' => $connector->getId(),
								'device' => $device->getId(),
								'channel' => Types\ChannelType::TELEVISION,
								'property' => Types\ChannelPropertyIdentifier::APPLICATION,
								'value' => intval($expectedValue) !== 500 ? $expectedValue : null,
							],
						),
					);
				}

				if (
					$property->getIdentifier() === Types\ChannelPropertyIdentifier::APPLICATION
					|| $property->getIdentifier() === Types\ChannelPropertyIdentifier::HDMI
				) {
					if ($property->getIdentifier() === Types\ChannelPropertyIdentifier::HDMI) {
						$this->queue->append(
							$this->entityHelper->create(
								Entities\Messages\StoreChannelPropertyState::class,
								[
									'connector' => $connector->getId(),
									'device' => $device->getId(),
									'channel' => Types\ChannelType::TELEVISION,
									'property' => Types\ChannelPropertyIdentifier::APPLICATION,
									'value' => null,
								],
							),
						);
					}

					if ($property->getIdentifier() === Types\ChannelPropertyIdentifier::APPLICATION) {
						$this->queue->append(
							$this->entityHelper->create(
								Entities\Messages\StoreChannelPropertyState::class,
								[
									'connector' => $connector->getId(),
									'device' => $device->getId(),
									'channel' => Types\ChannelType::TELEVISION,
									'property' => Types\ChannelPropertyIdentifier::HDMI,
									'value' => null,
								],
							),
						);
					}

					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreChannelPropertyState::class,
							[
								'connector' => $connector->getId(),
								'device' => $device->getId(),
								'channel' => Types\ChannelType::TELEVISION,
								'property' => Types\ChannelPropertyIdentifier::INPUT_SOURCE,
								'value' => $expectedValue,
							],
						),
					);
				}
			},
			function (Throwable $ex) use ($device, $property): void {
				$this->channelPropertiesStatesManager->setPendingState(
					$property,
					false,
					MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::VIERA),
				);

				if ($ex instanceof Exceptions\TelevisionApiError) {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'device' => $device->getId(),
								'state' => MetadataTypes\ConnectionState::ALERT,
							],
						),
					);
				} elseif ($ex->getCode() === 500) {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'device' => $device->getId(),
								'state' => MetadataTypes\ConnectionState::DISCONNECTED,
							],
						),
					);
				}
			},
		);

		$this->logger->debug(
			'Consumed write device state message',
			[
				'source' => MetadataTypes\ConnectorSource::VIERA,
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
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
