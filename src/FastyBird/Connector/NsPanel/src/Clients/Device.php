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
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Types as DevicesTypes;
use Nette;
use React\Promise;
use Throwable;
use TypeError;
use ValueError;
use function array_merge;
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
		private readonly Helpers\Devices\Gateway $gatewayHelper,
		private readonly Protocol\Driver $devicesDriver,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
	)
	{
		$this->lanApi = $this->lanApiFactory->create($this->connector->getId());
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
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
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $gateway->getConnector(),
							'device' => $gateway->getId(),
							'state' => DevicesTypes\ConnectionState::ALERT,
						],
					),
				);

				continue;
			}

			$protocolDevices = $this->devicesDriver->findDevices($gateway->getId());

			$devicesToSync = [];

			foreach ($protocolDevices as $protocolDevice) {
				if (!$protocolDevice instanceof Protocol\Devices\ThirdPartyDevice) {
					continue;
				}

				if (
					$protocolDevice->getGatewayIdentifier() === null
					|| $protocolDevice->isCorrupted()
				) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $protocolDevice->getConnector(),
								'device' => $protocolDevice->getId(),
								'state' => DevicesTypes\ConnectionState::ALERT,
							],
						),
					);

					if ($protocolDevice->isCorrupted()) {
						$this->logger->warning(
							'NS Panel third-party device is not correctly configured.',
							[
								'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
								'type' => 'device-client',
								'connector' => [
									'id' => $gateway->getConnector()->toString(),
								],
								'gateway' => [
									'id' => $gateway->getId()->toString(),
								],
								'device' => [
									'id' => $protocolDevice->getId()->toString(),
								],
							],
						);
					}

					continue;
				}

				$devicesToSync[] = $protocolDevice->toRepresentation();
			}

			$deferred = new Promise\Deferred();

			$promise = $deferred->promise();

			try {
				if ($devicesToSync !== []) {
					$this->lanApi->synchroniseDevices(
						$devicesToSync,
						$ipAddress,
						$accessToken,
					)
						->then(function (API\Messages\Response\SyncDevices $response) use ($gateway, $deferred): void {
							$this->logger->debug(
								'NS Panel third-party devices were successfully synchronised',
								[
									'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
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
								$protocolDevice = $this->devicesDriver->findDevice($endpoint->getThirdSerialNumber());

								if ($protocolDevice instanceof Protocol\Devices\ThirdPartyDevice) {
									$this->queue->append(
										$this->messageBuilder->create(
											Queue\Messages\StoreDeviceConnectionState::class,
											[
												'connector' => $protocolDevice->getConnector(),
												'device' => $protocolDevice->getId(),
												'state' => DevicesTypes\ConnectionState::CONNECTED,
											],
										),
									);

									$this->queue->append(
										$this->messageBuilder->create(
											Queue\Messages\StoreThirdPartyDevice::class,
											[
												'connector' => $protocolDevice->getConnector(),
												'gateway' => $gateway->getId(),
												'device' => $protocolDevice->getId(),
												'gateway_identifier' => $endpoint->getSerialNumber(),
											],
										),
									);

									$protocolDevice->setProvisioned(true);
								} else {
									$this->logger->error(
										'Could not finish third-party devices synchronisation',
										[
											'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
											'type' => 'device-client',
											'connector' => [
												'id' => $gateway->getConnector()->toString(),
											],
											'gateway' => [
												'id' => $gateway->getId()->toString(),
											],
											'device' => [
												'id' => $endpoint->getThirdSerialNumber(),
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
											'device' => $gateway->getId(),
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
											'device' => $gateway->getId(),
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
							function (API\Messages\Response\GetSubDevices $response) use ($gateway, $ipAddress, $accessToken): void {
								foreach ($response->getData()->getDevicesList() as $subDevice) {
									if ($subDevice->getThirdSerialNumber() === null) {
										continue;
									}

									$protocolDevice = $this->devicesDriver->findDevice(
										$subDevice->getThirdSerialNumber(),
									);

									if ($protocolDevice instanceof Protocol\Devices\ThirdPartyDevice) {
										continue;
									}

									$this->lanApi->removeDevice(
										$subDevice->getSerialNumber(),
										$ipAddress,
										$accessToken,
									)
										->then(function () use ($gateway, $subDevice): void {
											$this->logger->debug(
												'Removed unrecognized third-party device from NS Panel',
												[
													'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
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
													$this->messageBuilder->create(
														Queue\Messages\StoreDeviceConnectionState::class,
														[
															'connector' => $gateway->getConnector(),
															'device' => $gateway->getId(),
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
															'device' => $gateway->getId(),
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
									$this->messageBuilder->create(
										Queue\Messages\StoreDeviceConnectionState::class,
										[
											'connector' => $gateway->getConnector(),
											'device' => $gateway->getId(),
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
											'device' => $gateway->getId(),
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
						'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
						'type' => 'device-client',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $gateway->getConnector()->toString(),
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
							'device' => $gateway->getId(),
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

			$protocolDevices = $this->devicesDriver->findDevices($gateway->getId());

			foreach ($protocolDevices as $protocolDevice) {
				if (!$protocolDevice instanceof Protocol\Devices\ThirdPartyDevice) {
					continue;
				}

				$protocolDevice->setProvisioned(false);

				if ($protocolDevice->getGatewayIdentifier() === null) {
					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreDeviceConnectionState::class,
							[
								'connector' => $protocolDevice->getConnector(),
								'device' => $protocolDevice->getId(),
								'state' => DevicesTypes\ConnectionState::ALERT,
							],
						),
					);

					continue;
				}

				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\StoreDeviceConnectionState::class,
						[
							'connector' => $protocolDevice->getConnector(),
							'device' => $protocolDevice->getId(),
							'state' => DevicesTypes\ConnectionState::DISCONNECTED,
						],
					),
				);

				try {
					$this->lanApi->reportDeviceOnline(
						$protocolDevice->getGatewayIdentifier(),
						false,
						$ipAddress,
						$accessToken,
					)
						->then(function () use ($gateway, $protocolDevice): void {
							$this->logger->debug(
								'State for NS Panel third-party device was successfully published',
								[
									'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
									'type' => 'device-client',
									'connector' => [
										'id' => $gateway->getConnector()->toString(),
									],
									'gateway' => [
										'id' => $gateway->getId()->toString(),
									],
									'device' => [
										'id' => $protocolDevice->getId()->toString(),
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
							'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
							'type' => 'device-client',
							'exception' => ApplicationHelpers\Logger::buildException($ex),
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
				$this->messageBuilder->create(
					Queue\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $gateway->getConnector(),
						'device' => $gateway->getId(),
						'state' => DevicesTypes\ConnectionState::DISCONNECTED,
					],
				),
			);
		}
	}

}
