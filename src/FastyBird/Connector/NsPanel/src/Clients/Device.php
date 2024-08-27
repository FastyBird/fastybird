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
use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Models;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\Promise;
use Throwable;
use TypeError;
use ValueError;
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
		private readonly Documents\Connectors\Connector $connector,
		private readonly API\LanApiFactory $lanApiFactory,
		private readonly Queue\Queue $queue,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Connectors\Connector $connectorHelper,
		private readonly Helpers\Devices\Gateway $gatewayHelper,
		private readonly Helpers\Devices\ThirdPartyDevice $thirdPartyDeviceHelper,
		private readonly Helpers\Channels\Channel $channelHelper,
		private readonly Helpers\Loader $loader,
		private readonly Models\StateRepository $stateRepository,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
		$this->lanApi = $this->lanApiFactory->create($this->connector->getIdentifier());
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
	 * @throws MetadataExceptions\Mapping
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws ValueError
	 */
	public function connect(): void
	{
		$findDevicesQuery = new Queries\Configuration\FindGatewayDevices();
		$findDevicesQuery->forConnector($this->connector);
		$findDevicesQuery->withoutParents();

		$gateways = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Gateway::class,
		);

		foreach ($gateways as $gateway) {
			$ipAddress = $this->gatewayHelper->getIpAddress($gateway);
			$accessToken = $this->gatewayHelper->getAccessToken($gateway);

			if ($ipAddress === null || $accessToken === null) {
				continue;
			}

			$findDevicesQuery = new Queries\Configuration\FindThirdPartyDevices();
			$findDevicesQuery->forConnector($this->connector);
			$findDevicesQuery->forParent($gateway);

			$devices = $this->devicesConfigurationRepository->findAllBy(
				$findDevicesQuery,
				Documents\Devices\ThirdPartyDevice::class,
			);

			$categoriesMetadata = $this->loader->loadCategories();

			$syncDevices = [];

			foreach ($devices as $device) {
				if (!array_key_exists(
					$this->thirdPartyDeviceHelper->getDisplayCategory($device)->value,
					(array) $categoriesMetadata,
				)) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $gateway->getConnector(),
								'identifier' => $gateway->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::ALERT,
							],
						),
					);

					continue;
				}

				if (
					!$categoriesMetadata[$this->thirdPartyDeviceHelper->getDisplayCategory(
						$device,
					)->value] instanceof Utils\ArrayHash
					|| !$categoriesMetadata[$this->thirdPartyDeviceHelper->getDisplayCategory(
						$device,
					)->value]->offsetExists('capabilities')
					|| !$categoriesMetadata[$this->thirdPartyDeviceHelper->getDisplayCategory(
						$device,
					)->value]->offsetGet(
						'capabilities',
					) instanceof Utils\ArrayHash
				) {
					$this->dispatcher?->dispatch(new DevicesEvents\TerminateConnector(
						MetadataTypes\Sources\Connector::NS_PANEL,
						'Connector configuration is corrupted',
					));

					return;
				}

				$requiredCapabilities = (array) $categoriesMetadata[$this->thirdPartyDeviceHelper->getDisplayCategory(
					$device,
				)->value]->offsetGet('capabilities');
				$deviceCapabilities = [];

				$capabilities = [];
				$tags = [];

				$findChannelsQuery = new Queries\Configuration\FindChannels();
				$findChannelsQuery->forDevice($device);

				$channels = $this->channelsConfigurationRepository->findAllBy(
					$findChannelsQuery,
					Documents\Channels\Channel::class,
				);

				foreach ($channels as $channel) {
					$deviceCapabilities[] = $this->channelHelper->getCapability($channel)->value;

					$capabilityName = null;

					if (
						preg_match(NsPanel\Constants::CHANNEL_IDENTIFIER, $channel->getIdentifier(), $matches) === 1
						&& array_key_exists('key', $matches)
					) {
						$capabilityName = $matches['key'];
					}

					$capabilities[] = [
						'capability' => $this->channelHelper->getCapability($channel)->value,
						'permission' => (
							$this->channelHelper->getCapability($channel)->hasReadWritePermission()
								? Types\Permission::READ_WRITE
								: Types\Permission::READ
						)->value,
						'name' => $capabilityName,
					];

					$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelVariableProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
						$findChannelPropertiesQuery,
						DevicesDocuments\Channels\Properties\Variable::class,
					);

					foreach ($properties as $property) {
						if (
							is_string($property->getValue())
							&& preg_match(
								NsPanel\Constants::PROPERTY_TAG_IDENTIFIER,
								$property->getIdentifier(),
								$matches,
							) === 1
						) {
							$tags[$matches['tag']] = $property->getValue();
						}
					}

					$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
						$findChannelPropertiesQuery,
						DevicesDocuments\Channels\Properties\Dynamic::class,
					);

					foreach ($properties as $property) {
						$state = $this->channelPropertiesStatesManager->read(
							$property,
							MetadataTypes\Sources\Connector::NS_PANEL,
						);

						if ($state instanceof DevicesDocuments\States\Channels\Properties\Property) {
							$this->stateRepository->set($property->getId(), $state->getGet()->getActualValue());
						}
					}

					if (
						$capabilityName !== null
						&& $this->channelHelper->getCapability($channel) === Types\Capability::TOGGLE
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
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $device->getConnector(),
								'identifier' => $device->getIdentifier(),
								'state' => DevicesTypes\ConnectionState::ALERT,
							],
						),
					);

					continue;
				}

				$syncDevices[] = [
					'third_serial_number' => $device->getId()->toString(),
					'name' => $device->getName() ?? $device->getIdentifier(),
					'display_category' => $this->thirdPartyDeviceHelper->getDisplayCategory($device)->value,
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
						->then(function (API\Messages\Response\SyncDevices $response) use ($gateway, $deferred): void {
							$this->logger->debug(
								'NS Panel third-party devices was successfully synchronised',
								[
									'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
									'type' => 'device-client',
									'connector' => [
										'id' => $this->connector->getId()->toString(),
									],
									'gateway' => [
										'id' => $gateway->getId()->toString(),
									],
								],
							);

							foreach ($response->getPayload()->getEndpoints() as $endpoint) {
								$findDeviceQuery = new Queries\Configuration\FindDevices();
								$findDeviceQuery->byId($endpoint->getThirdSerialNumber());
								$findDeviceQuery->forConnector($this->connector);
								$findDeviceQuery->forParent($gateway);

								$device = $this->devicesConfigurationRepository->findOneBy(
									$findDeviceQuery,
									Documents\Devices\Device::class,
								);

								if ($device !== null) {
									$this->queue->append(
										$this->messageBuilder->create(
											Queue\Messages\StoreDeviceConnectionState::class,
											[
												'connector' => $device->getConnector(),
												'identifier' => $device->getIdentifier(),
												'state' => DevicesTypes\ConnectionState::CONNECTED,
											],
										),
									);

									$this->queue->append(
										$this->messageBuilder->create(
											Queue\Messages\StoreThirdPartyDevice::class,
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
											'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
											'type' => 'device-client',
											'connector' => [
												'id' => $this->connector->getId()->toString(),
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
									$this->messageBuilder->create(
										Queue\Messages\StoreDeviceConnectionState::class,
										[
											'connector' => $gateway->getConnector(),
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
											'connector' => $gateway->getConnector(),
											'identifier' => $gateway->getIdentifier(),
											'state' => DevicesTypes\ConnectionState::ALERT,
										],
									),
								);
							}

							$this->logger->error(
								'Could not synchronise third-party devices with NS Panel',
								array_merge(
									[
										'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
										'type' => 'device-client',
										'exception' => ApplicationHelpers\Logger::buildException($ex),
										'connector' => [
											'id' => $this->connector->getId()->toString(),
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
							function (API\Messages\Response\GetSubDevices $response) use ($gateway, $ipAddress, $accessToken): void {
								foreach ($response->getData()->getDevicesList() as $subDevice) {
									if ($subDevice->getThirdSerialNumber() === null) {
										continue;
									}

									$findDevicesQuery = new Queries\Configuration\FindDevices();
									$findDevicesQuery->forParent($gateway);
									$findDevicesQuery->byId($subDevice->getThirdSerialNumber());

									$device = $this->devicesConfigurationRepository->findOneBy(
										$findDevicesQuery,
										Documents\Devices\Device::class,
									);

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
													'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
													'type' => 'device-client',
													'connector' => [
														'id' => $this->connector->getId()->toString(),
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
													$this->messageBuilder->create(
														Queue\Messages\StoreDeviceConnectionState::class,
														[
															'connector' => $gateway->getConnector(),
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
															'connector' => $gateway->getConnector(),
															'identifier' => $gateway->getIdentifier(),
															'state' => DevicesTypes\ConnectionState::ALERT,
														],
													),
												);
											}

											$this->logger->error(
												'Could not remove deleted third-party device from NS Panel',
												array_merge(
													[
														'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
														'type' => 'device-client',
														'exception' => ApplicationHelpers\Logger::buildException($ex),
														'connector' => [
															'id' => $this->connector->getId()->toString(),
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
									$this->messageBuilder->create(
										Queue\Messages\StoreDeviceConnectionState::class,
										[
											'connector' => $gateway->getConnector(),
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
											'connector' => $gateway->getConnector(),
											'identifier' => $gateway->getIdentifier(),
											'state' => DevicesTypes\ConnectionState::ALERT,
										],
									),
								);
							}

							$this->logger->error(
								'Could not fetch NS Panel registered devices',
								array_merge(
									[
										'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
										'type' => 'device-client',
										'exception' => ApplicationHelpers\Logger::buildException($ex),
										'connector' => [
											'id' => $this->connector->getId()->toString(),
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
						'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
						'type' => 'device-client',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
						'gateway' => [
							'id' => $gateway->getId()->toString(),
						],
					],
				);

				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $gateway->getConnector(),
							'identifier' => $gateway->getIdentifier(),
							'state' => DevicesTypes\ConnectionState::ALERT,
						],
					),
				);
			}
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function disconnect(): void
	{
		$findDevicesQuery = new Queries\Configuration\FindGatewayDevices();
		$findDevicesQuery->forConnector($this->connector);
		$findDevicesQuery->withoutParents();

		$gateways = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Gateway::class,
		);

		foreach ($gateways as $gateway) {
			$ipAddress = $this->gatewayHelper->getIpAddress($gateway);
			$accessToken = $this->gatewayHelper->getAccessToken($gateway);

			if ($ipAddress === null || $accessToken === null) {
				continue;
			}

			$findDevicesQuery = new Queries\Configuration\FindThirdPartyDevices();
			$findDevicesQuery->forConnector($this->connector);
			$findDevicesQuery->forParent($gateway);

			$devices = $this->devicesConfigurationRepository->findAllBy(
				$findDevicesQuery,
				Documents\Devices\ThirdPartyDevice::class,
			);

			foreach ($devices as $device) {
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $gateway->getConnector(),
							'identifier' => $device->getIdentifier(),
							'state' => DevicesTypes\ConnectionState::DISCONNECTED,
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
									'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
									'type' => 'device-client',
									'connector' => [
										'id' => $this->connector->getId()->toString(),
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
										'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
										'type' => 'device-client',
										'exception' => ApplicationHelpers\Logger::buildException($ex),
										'connector' => [
											'id' => $this->connector->getId()->toString(),
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
							'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
							'type' => 'device-client',
							'exception' => ApplicationHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getId()->toString(),
							],
							'gateway' => [
								'id' => $gateway->getId()->toString(),
							],
						],
					);
				}
			}

			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $gateway->getConnector(),
						'identifier' => $gateway->getIdentifier(),
						'state' => DevicesTypes\ConnectionState::DISCONNECTED,
					],
				),
			);
		}
	}

}
