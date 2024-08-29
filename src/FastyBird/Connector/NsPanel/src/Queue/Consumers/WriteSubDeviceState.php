<?php declare(strict_types = 1);

/**
 * WriteSubDeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           18.07.23
 */

namespace FastyBird\Connector\NsPanel\Queue\Consumers;

use DateTimeInterface;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Models;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette;
use Throwable;
use TypeError;
use ValueError;
use function array_merge;
use function React\Async\async;
use function React\Async\await;
use function strval;

/**
 * Write state to sub-device message consumer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WriteSubDeviceState implements Queue\Consumer
{

	use StateWriter;
	use Nette\SmartObject;

	private const WRITE_PENDING_DELAY = 2_000.0;

	private API\LanApi|null $lanApiApi = null;

	public function __construct(
		protected readonly Helpers\Channels\Channel $channelHelper,
		protected readonly Models\StateRepository $stateRepository,
		protected readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly Queue\Queue $queue,
		private readonly API\LanApiFactory $lanApiApiFactory,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Devices\Gateway $gatewayHelper,
		private readonly Helpers\Devices\SubDevice $subDeviceHelper,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly DateTimeFactory\Clock $clock,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\WriteSubDeviceState) {
			return false;
		}

		$findConnectorQuery = new Queries\Configuration\FindConnectors();
		$findConnectorQuery->byId($message->getConnector());

		$connector = $this->connectorsConfigurationRepository->findOneBy(
			$findConnectorQuery,
			Documents\Connectors\Connector::class,
		);

		if ($connector === null) {
			$this->logger->error(
				'Connector could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
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

		$findDeviceQuery = new Queries\Configuration\FindSubDevices();
		$findDeviceQuery->forConnector($connector);
		$findDeviceQuery->byId($message->getDevice());

		$device = $this->devicesConfigurationRepository->findOneBy(
			$findDeviceQuery,
			Documents\Devices\SubDevice::class,
		);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
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

		$gateway = $this->subDeviceHelper->getGateway($device);

		$ipAddress = $this->gatewayHelper->getIpAddress($gateway);
		$accessToken = $this->gatewayHelper->getAccessToken($gateway);

		if ($ipAddress === null || $accessToken === null) {
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $connector->getId(),
						'identifier' => $gateway->getIdentifier(),
						'state' => DevicesTypes\ConnectionState::ALERT,
					],
				),
			);

			$this->logger->error(
				'Device owning NS Panel is not configured',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
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

		$findChannelQuery = new Queries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byId($message->getChannel());

		$channel = $this->channelsConfigurationRepository->findOneBy(
			$findChannelQuery,
			Documents\Channels\Channel::class,
		);

		if ($channel === null) {
			$this->logger->error(
				'Channel could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
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

		if (!$this->channelHelper->getCapability($channel)->hasReadWritePermission()) {
			$this->logger->error(
				'Device state is not writable',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
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

		$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byId($message->getProperty());

		$propertyToUpdate = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertyQuery);

		if ($propertyToUpdate === null) {
			$this->logger->error(
				'Channel property could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
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

		if (
			$propertyToUpdate instanceof DevicesDocuments\Channels\Properties\Dynamic
			&& !$propertyToUpdate->isSettable()
		) {
			$this->logger->warning(
				'Channel property is not writable',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
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
						'id' => $propertyToUpdate->getId()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		$state = null;

		if ($propertyToUpdate instanceof DevicesDocuments\Channels\Properties\Dynamic) {
			$state = $message->getState();

			if ($state === null) {
				return true;
			}

			if ($state->getExpectedValue() === null) {
				await($this->channelPropertiesStatesManager->setPendingState(
					$propertyToUpdate,
					false,
					MetadataTypes\Sources\Connector::NS_PANEL,
				));

				return true;
			}

			$now = $this->clock->getNow();
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

			await($this->channelPropertiesStatesManager->setPendingState(
				$propertyToUpdate,
				true,
				MetadataTypes\Sources\Connector::NS_PANEL,
			));
		}

		$mapped = $this->mapChannelToState($channel, $propertyToUpdate, $state);

		if ($mapped === null) {
			$this->logger->error(
				'Device state could not be created',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
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
						'id' => $propertyToUpdate->getId()->toString(),
					],
					'data' => $message->toArray(),
				],
			);

			if ($propertyToUpdate instanceof DevicesDocuments\Channels\Properties\Dynamic) {
				await($this->channelPropertiesStatesManager->setPendingState(
					$propertyToUpdate,
					false,
					MetadataTypes\Sources\Connector::NS_PANEL,
				));
			}

			return true;
		}

		try {
			$this->getApiClient($connector)->setSubDeviceState(
				$device->getIdentifier(),
				$mapped,
				$ipAddress,
				$accessToken,
			)
				->then(function () use ($connector, $device, $channel, $propertyToUpdate, $message): void {
					$this->logger->debug(
						'Channel state was successfully sent to device',
						[
							'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
							'type' => 'write-sub-device-state-message-consumer',
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
								'id' => $propertyToUpdate->getId()->toString(),
							],
							'data' => $message->toArray(),
						],
					);
				})
				->catch(
					async(
						function (Throwable $ex) use ($message, $connector, $gateway, $device, $channel, $propertyToUpdate): void {
							if ($propertyToUpdate instanceof DevicesDocuments\Channels\Properties\Dynamic) {
								await($this->channelPropertiesStatesManager->setPendingState(
									$propertyToUpdate,
									false,
									MetadataTypes\Sources\Connector::NS_PANEL,
								));
							}

							$extra = [];

							if ($ex instanceof Exceptions\LanApiCall) {
								$extra = [
									'request' => [
										'method' => $ex->getRequest()?->getMethod(),
										'url' => $ex->getRequest() !== null ? strval(
											$ex->getRequest()->getUri(),
										) : null,
										'body' => $ex->getRequest()?->getBody()->getContents(),
									],
									'response' => [
										'body' => $ex->getResponse()?->getBody()->getContents(),
									],
								];

								$this->queue->append(
									$this->messageBuilder->create(
										Queue\Messages\StoreDeviceConnectionState::class,
										[
											'connector' => $connector->getId(),
											'identifier' => $gateway->getIdentifier(),
											'state' => DevicesTypes\ConnectionState::DISCONNECTED,
										],
									),
								);

							} else {
								$this->queue->append(
									$this->messageBuilder->create(
										Queue\Messages\StoreDeviceConnectionState::class,
										[
											'connector' => $connector->getId(),
											'identifier' => $gateway->getIdentifier(),
											'state' => DevicesTypes\ConnectionState::LOST,
										],
									),
								);
							}

							$this->logger->error(
								'Could write state to sub-device',
								array_merge(
									[
										'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
										'type' => 'write-sub-device-state-message-consumer',
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
											'id' => $propertyToUpdate->getId()->toString(),
										],
										'data' => $message->toArray(),
									],
									$extra,
								),
							);
						},
					),
				);
		} catch (Throwable $ex) {
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'write-sub-device-state-message-consumer',
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
						'id' => $propertyToUpdate->getId()->toString(),
					],
					'data' => $message->toArray(),
				],
			);
		}

		$this->logger->debug(
			'Consumed write sub-device state message',
			[
				'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
				'type' => 'write-sub-device-state-message-consumer',
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
					'id' => $propertyToUpdate->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

	private function getApiClient(Documents\Connectors\Connector $connector): API\LanApi
	{
		if ($this->lanApiApi === null) {
			$this->lanApiApi = $this->lanApiApiFactory->create($connector->getIdentifier());
		}

		return $this->lanApiApi;
	}

}
