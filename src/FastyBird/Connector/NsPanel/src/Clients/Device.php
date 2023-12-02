<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           16.07.23
 */

namespace FastyBird\Connector\NsPanel\Clients;

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Nette\Utils;
use React\Promise;
use Throwable;
use function array_diff;
use function array_key_exists;
use function array_merge;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function strval;

/**
 * Connector third-party device client
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device implements Client
{

	use Nette\SmartObject;

	private API\LanApi $lanApi;

	public function __construct(
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
		API\LanApiFactory $lanApiFactory,
		private readonly Queue\Queue $queue,
		private readonly Helpers\Loader $loader,
		private readonly Helpers\Entity $entityHelper,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Helpers\Devices\Gateway $gatewayHelper,
		private readonly Helpers\Devices\ThirdPartyDevice $thirdPartyDeviceHelper,
		private readonly Helpers\Channel $channelHelper,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
	)
	{
		$this->lanApi = $lanApiFactory->create($this->connector->getIdentifier());
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Terminate
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	public function connect(): void
	{
		$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($this->connector);
		$findDevicesQuery->withoutParents();
		$findDevicesQuery->byType(Entities\Devices\Gateway::TYPE);

		foreach ($this->devicesConfigurationRepository->findAllBy($findDevicesQuery) as $gateway) {
			$ipAddress = $this->gatewayHelper->getIpAddress($gateway);
			$accessToken = $this->gatewayHelper->getAccessToken($gateway);

			if ($ipAddress === null || $accessToken === null) {
				continue;
			}

			$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
			$findDevicesQuery->forConnector($this->connector);
			$findDevicesQuery->forParent($gateway);
			$findDevicesQuery->byType(Entities\Devices\ThirdPartyDevice::TYPE);

			$devices = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);

			$categoriesMetadata = $this->loader->loadCategories();

			$syncDevices = [];

			foreach ($devices as $device) {
				if (!array_key_exists(
					$this->thirdPartyDeviceHelper->getDisplayCategory($device)->getValue(),
					(array) $categoriesMetadata,
				)) {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $gateway->getConnector(),
								'identifier' => $gateway->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_ALERT,
							],
						),
					);

					continue;
				}

				if (
					!$categoriesMetadata[$this->thirdPartyDeviceHelper->getDisplayCategory(
						$device,
					)->getValue()] instanceof Utils\ArrayHash
					|| !$categoriesMetadata[$this->thirdPartyDeviceHelper->getDisplayCategory(
						$device,
					)->getValue()]->offsetExists('capabilities')
					|| !$categoriesMetadata[$this->thirdPartyDeviceHelper->getDisplayCategory(
						$device,
					)->getValue()]->offsetGet(
						'capabilities',
					) instanceof Utils\ArrayHash
				) {
					throw new DevicesExceptions\Terminate('Connector configuration is corrupted');
				}

				$requiredCapabilities = (array) $categoriesMetadata[$this->thirdPartyDeviceHelper->getDisplayCategory(
					$device,
				)->getValue()]->offsetGet('capabilities');
				$deviceCapabilities = [];

				$capabilities = [];
				$tags = [];

				$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
				$findChannelsQuery->forDevice($device);

				foreach ($this->channelsConfigurationRepository->findAllBy($findChannelsQuery) as $channel) {
					$deviceCapabilities[] = $this->channelHelper->getCapability($channel)->getValue();

					$capabilityName = null;

					if (
						preg_match(NsPanel\Constants::CHANNEL_IDENTIFIER, $channel->getIdentifier(), $matches) === 1
						&& array_key_exists('identifier', $matches)
					) {
						$capabilityName = $matches['key'];
					}

					$capabilities[] = [
						'capability' => $this->channelHelper->getCapability($channel)->getValue(),
						'permission' => Types\Permission::get(
							$this->channelHelper->getCapability($channel)->hasReadWritePermission()
								? Types\Permission::READ_WRITE
								: Types\Permission::READ,
						)->getValue(),
						'name' => $capabilityName,
					];

					$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelVariableProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
						$findChannelPropertiesQuery,
						MetadataDocuments\DevicesModule\ChannelVariableProperty::class,
					);

					foreach ($properties as $property) {
						if (
							is_string($property->getValue())
							&& preg_match(
								NsPanel\Constants::PROPERTY_TAG_IDENTIFIER,
								$property->getIdentifier(),
								$matches,
							) === 1
							&& array_key_exists('tag', $matches)
						) {
							$tags[$matches['tag']] = $property->getValue();
						}
					}

					if (
						$capabilityName !== null
						&& $this->channelHelper->getCapability($channel)->equalsValue(Types\Capability::TOGGLE)
					) {
						if (!array_key_exists('toggle', $tags)) {
							$tags['toggle'] = [];
						}

						if (is_array($tags['toggle'])) {
							$tags['toggle'][$capabilityName] = $channel->getName() ?? $channel->getIdentifier();
						}
					}
				}

				// Device have to have configured all required capabilities
				if (array_diff($requiredCapabilities, $deviceCapabilities) !== []) {
					$this->queue->append(
						$this->entityHelper->create(
							Entities\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => MetadataTypes\ConnectionState::STATE_ALERT,
							],
						),
					);

					continue;
				}

				$syncDevices[] = [
					'third_serial_number' => $device->getId()->toString(),
					'name' => $device->getName() ?? $device->getIdentifier(),
					'display_category' => $this->thirdPartyDeviceHelper->getDisplayCategory($device)->getValue(),
					'capabilities' => $capabilities,
					'state' => [],
					'tags' => $tags,
					'manufacturer' => $this->thirdPartyDeviceHelper->getManufacturer($device),
					'model' => $this->thirdPartyDeviceHelper->getModel($device),
					'firmware_version' => $this->thirdPartyDeviceHelper->getFirmwareVersion($device),
					'service_address' => sprintf(
						'http://%s:%d/do-directive/%s/%s',
						Helpers\Network::getLocalAddress(),
						$this->connectorHelper->getPort($this->connector),
						$gateway->getId()->toString(),
						$device->getId()->toString(),
					),
					'online' => true, // Virtual device is always online
				];
			}

			$deferred = new Promise\Deferred();

			$promise = $deferred->promise();

			try {
				if ($syncDevices !== []) {
					$this->lanApi->synchroniseDevices(
						$syncDevices,
						$ipAddress,
						$accessToken,
					)
						->then(function (Entities\API\Response\SyncDevices $response) use ($gateway, $deferred): void {
							$this->logger->debug(
								'NS Panel third-party devices was successfully synchronised',
								[
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
									'type' => 'device-client',
									'connector' => [
										'id' => $gateway->getConnector()->toString(),
									],
									'gateway' => [
										'id' => $gateway->getId()->toString(),
									],
								],
							);

							foreach ($response->getPayload()->getEndpoints() as $endpoint) {
								$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
								$findDeviceQuery->byId($endpoint->getThirdSerialNumber());
								$findDeviceQuery->forConnector($this->connector);
								$findDeviceQuery->forParent($gateway);

								$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

								if ($device !== null) {
									$this->queue->append(
										$this->entityHelper->create(
											Entities\Messages\StoreDeviceConnectionState::class,
											[
												'connector' => $device->getConnector(),
												'identifier' => $device->getIdentifier(),
												'state' => MetadataTypes\ConnectionState::STATE_CONNECTED,
											],
										),
									);

									$this->queue->append(
										$this->entityHelper->create(
											Entities\Messages\StoreThirdPartyDevice::class,
											[
												'connector' => $device->getConnector(),
												'gateway' => $gateway->getId(),
												'identifier' => $device->getIdentifier(),
												'gateway_identifier' => $endpoint->getSerialNumber(),
											],
										),
									);
								} else {
									$this->logger->error(
										'Could not finish third-party device synchronisation',
										[
											'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
											'type' => 'device-client',
											'connector' => [
												'id' => $gateway->getConnector()->toString(),
											],
											'gateway' => [
												'id' => $gateway->getId()->toString(),
											],
											'device' => [
												'id' => $endpoint->getThirdSerialNumber()->toString(),
											],
										],
									);
								}
							}

							$deferred->resolve(true);
						})
						->catch(function (Throwable $ex) use ($gateway, $deferred): void {
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
									$this->entityHelper->create(
										Entities\Messages\StoreDeviceConnectionState::class,
										[
											'connector' => $gateway->getConnector(),
											'identifier' => $gateway->getIdentifier(),
											'state' => MetadataTypes\ConnectionState::STATE_DISCONNECTED,
										],
									),
								);

							} else {
								$this->queue->append(
									$this->entityHelper->create(
										Entities\Messages\StoreDeviceConnectionState::class,
										[
											'connector' => $gateway->getConnector(),
											'identifier' => $gateway->getIdentifier(),
											'state' => MetadataTypes\ConnectionState::STATE_ALERT,
										],
									),
								);
							}

							$this->logger->error(
								'Could not synchronise third-party devices with NS Panel',
								array_merge(
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
										'type' => 'device-client',
										'exception' => BootstrapHelpers\Logger::buildException($ex),
										'connector' => [
											'id' => $gateway->getConnector()->toString(),
										],
										'gateway' => [
											'id' => $gateway->getId()->toString(),
										],
									],
									$extra,
								),
							);

							$deferred->reject($ex);
						});
				} else {
					$deferred->resolve(true);
				}

				$promise->then(function () use ($gateway, $ipAddress, $accessToken): void {
					$this->lanApi->getSubDevices($ipAddress, $accessToken)
						->then(
							function (Entities\API\Response\GetSubDevices $response) use ($gateway, $ipAddress, $accessToken): void {
								foreach ($response->getData()->getDevicesList() as $subDevice) {
									if ($subDevice->getThirdSerialNumber() === null) {
										continue;
									}

									$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
									$findDevicesQuery->forParent($gateway);
									$findDevicesQuery->byId($subDevice->getThirdSerialNumber());

									$device = $this->devicesConfigurationRepository->findOneBy($findDevicesQuery);

									if ($device !== null) {
										continue;
									}

									$this->lanApi->removeDevice(
										$subDevice->getSerialNumber(),
										$ipAddress,
										$accessToken,
									)
										->then(function () use ($gateway, $subDevice): void {
											$this->logger->debug(
												'Removed third-party from NS Panel',
												[
													'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
													'type' => 'device-client',
													'connector' => [
														'id' => $gateway->getConnector()->toString(),
													],
													'gateway' => [
														'id' => $gateway->getId()->toString(),
													],
													'device' => [
														'id' => $subDevice->getThirdSerialNumber()->toString(),
													],
												],
											);
										})
										->catch(function (Throwable $ex) use ($gateway, $subDevice): void {
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
													$this->entityHelper->create(
														Entities\Messages\StoreDeviceConnectionState::class,
														[
															'connector' => $gateway->getConnector(),
															'identifier' => $gateway->getIdentifier(),
															'state' => MetadataTypes\ConnectionState::STATE_DISCONNECTED,
														],
													),
												);

											} else {
												$this->queue->append(
													$this->entityHelper->create(
														Entities\Messages\StoreDeviceConnectionState::class,
														[
															'connector' => $gateway->getConnector(),
															'identifier' => $gateway->getIdentifier(),
															'state' => MetadataTypes\ConnectionState::STATE_ALERT,
														],
													),
												);
											}

											$this->logger->error(
												'Could not remove deleted third-party device from NS Panel',
												array_merge(
													[
														'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
														'type' => 'device-client',
														'exception' => BootstrapHelpers\Logger::buildException($ex),
														'connector' => [
															'id' => $gateway->getConnector()->toString(),
														],
														'gateway' => [
															'id' => $gateway->getId()->toString(),
														],
														'device' => [
															'id' => $subDevice->getThirdSerialNumber()->toString(),
														],
													],
													$extra,
												),
											);
										});
								}
							},
						)
						->catch(function (Throwable $ex) use ($gateway): void {
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
									$this->entityHelper->create(
										Entities\Messages\StoreDeviceConnectionState::class,
										[
											'connector' => $gateway->getConnector(),
											'identifier' => $gateway->getIdentifier(),
											'state' => MetadataTypes\ConnectionState::STATE_DISCONNECTED,
										],
									),
								);

							} else {
								$this->queue->append(
									$this->entityHelper->create(
										Entities\Messages\StoreDeviceConnectionState::class,
										[
											'connector' => $gateway->getConnector(),
											'identifier' => $gateway->getIdentifier(),
											'state' => MetadataTypes\ConnectionState::STATE_ALERT,
										],
									),
								);
							}

							$this->logger->error(
								'Could not fetch NS Panel registered devices',
								array_merge(
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
										'type' => 'device-client',
										'exception' => BootstrapHelpers\Logger::buildException($ex),
										'connector' => [
											'id' => $gateway->getConnector()->toString(),
										],
										'gateway' => [
											'id' => $gateway->getId()->toString(),
										],
									],
									$extra,
								),
							);
						});
				});

			} catch (Throwable $ex) {
				$this->logger->error(
					'An unhandled error occurred',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
						'type' => 'device-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $gateway->getConnector()->toString(),
						],
						'gateway' => [
							'id' => $gateway->getId()->toString(),
						],
					],
				);

				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $gateway->getConnector(),
							'identifier' => $gateway->getIdentifier(),
							'state' => MetadataTypes\ConnectionState::STATE_ALERT,
						],
					),
				);
			}
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function disconnect(): void
	{
		$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($this->connector);
		$findDevicesQuery->withoutParents();
		$findDevicesQuery->byType(Entities\Devices\Gateway::TYPE);

		foreach ($this->devicesConfigurationRepository->findAllBy($findDevicesQuery) as $gateway) {
			$ipAddress = $this->gatewayHelper->getIpAddress($gateway);
			$accessToken = $this->gatewayHelper->getAccessToken($gateway);

			if ($ipAddress === null || $accessToken === null) {
				continue;
			}

			$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
			$findDevicesQuery->forConnector($this->connector);
			$findDevicesQuery->forParent($gateway);
			$findDevicesQuery->byType(Entities\Devices\ThirdPartyDevice::TYPE);

			foreach ($this->devicesConfigurationRepository->findAllBy($findDevicesQuery) as $device) {
				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $gateway->getConnector(),
							'identifier' => $device->getIdentifier(),
							'state' => MetadataTypes\ConnectionState::STATE_DISCONNECTED,
						],
					),
				);

				try {
					$serialNumber = $this->thirdPartyDeviceHelper->getGatewayIdentifier($device);

					if ($serialNumber === null) {
						continue;
					}
				} catch (Throwable) {
					continue;
				}

				try {
					$this->lanApi->reportDeviceOnline(
						$serialNumber,
						false,
						$ipAddress,
						$accessToken,
					)
						->then(function () use ($gateway, $device): void {
							$this->logger->debug(
								'State for NS Panel third-party device was successfully updated',
								[
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
									'type' => 'device-client',
									'connector' => [
										'id' => $gateway->getConnector()->toString(),
									],
									'gateway' => [
										'id' => $gateway->getId()->toString(),
									],
									'device' => [
										'id' => $device->getId()->toString(),
									],
								],
							);
						})
						->catch(function (Throwable $ex) use ($gateway): void {
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
							}

							$this->logger->error(
								'State for NS Panel third-party device could not be updated',
								array_merge(
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
										'type' => 'device-client',
										'exception' => BootstrapHelpers\Logger::buildException($ex),
										'connector' => [
											'id' => $gateway->getConnector()->toString(),
										],
										'gateway' => [
											'id' => $gateway->getId()->toString(),
										],
									],
									$extra,
								),
							);
						});
				} catch (Throwable $ex) {
					$this->logger->error(
						'An unhandled error occurred',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
							'type' => 'device-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $gateway->getConnector()->toString(),
							],
							'gateway' => [
								'id' => $gateway->getId()->toString(),
							],
						],
					);
				}
			}

			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $gateway->getConnector(),
						'identifier' => $gateway->getIdentifier(),
						'state' => MetadataTypes\ConnectionState::STATE_DISCONNECTED,
					],
				),
			);
		}
	}

}
