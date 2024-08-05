<?php declare(strict_types = 1);

/**
 * Discovery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           27.08.22
 */

namespace FastyBird\Connector\Tuya\Clients;

use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Documents;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\Connector\Tuya\Services;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use Nette\Utils;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\Datagram;
use React\EventLoop;
use React\Promise;
use Throwable;
use TypeError;
use ValueError;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_values;
use function assert;
use function boolval;
use function count;
use function floatval;
use function in_array;
use function intval;
use function is_array;
use function is_bool;
use function is_float;
use function is_numeric;
use function is_string;
use function md5;
use function openssl_decrypt;
use function React\Async\async;
use function React\Async\await;
use function strval;
use function substr;
use const OPENSSL_RAW_DATA;

/**
 * Devices discovery client
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Discovery
{

	use Nette\SmartObject;

	private const UDP_BIND_IP = '0.0.0.0';

	private const UDP_PORT = [
		Types\DeviceProtocolVersion::V31->value => 6_666,
		Types\DeviceProtocolVersion::V32_PLUS->value => 6_667,
	];

	private const UDP_TIMEOUT = 5;

	private const GATEWAY_CATEGORIES = [
		'bywg',
		'zigbee',
		'wg2',
		'xnwg',
		'alexa_yywg',
		'gywg',
	];

	private API\OpenApi|null $cloudApiConnection = null;

	/** @var array<EventLoop\TimerInterface> */
	private array $handlerTimer = [];

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly API\OpenApiFactory $openApiFactory,
		private readonly API\LocalApiFactory $localApiFactory,
		private readonly Services\DatagramFactory $datagramFactory,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Queue\Queue $queue,
		private readonly Tuya\Logger $logger,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Throwable
	 */
	public function discover(): void
	{
		$mode = $this->connectorHelper->getClientMode($this->connector);

		if ($mode === Types\ClientMode::CLOUD) {
			$this->discoverCloudDevices();

		} elseif ($mode === Types\ClientMode::LOCAL) {
			$this->discoverLocalDevices();
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function disconnect(): void
	{
		foreach ($this->handlerTimer as $index => $timer) {
			$this->eventLoop->cancelTimer($timer);

			unset($this->handlerTimer[$index]);
		}

		$this->getCloudApiConnection()->disconnect();
	}

	private function discoverLocalDevices(): void
	{
		$promises = [];

		$knownProtocolsVersions = [
			Types\DeviceProtocolVersion::V31,
			Types\DeviceProtocolVersion::V32_PLUS,
		];

		// Process all known protocols
		foreach ($knownProtocolsVersions as $protocolVersion) {
			$this->logger->debug(
				'Starting local devices discovery',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'discovery-client',
					'protocol' => $protocolVersion->value,
				],
			);

			$deferred = new Promise\Deferred();

			$server = $this->datagramFactory->create(self::UDP_BIND_IP, self::UDP_PORT[$protocolVersion->value]);

			$server
				->then(function (Datagram\Socket $client) use ($deferred, $protocolVersion): void {
					$client->on('message', async(function (string $message): void {
						$this->handleDiscoveredLocalDevice($message);
					}));

					// Searching timeout
					$this->handlerTimer[$protocolVersion->value] = $this->eventLoop->addTimer(
						self::UDP_TIMEOUT,
						async(function () use ($deferred, $protocolVersion): void {
							if (array_key_exists($protocolVersion->value, $this->handlerTimer)) {
								$this->eventLoop->cancelTimer($this->handlerTimer[$protocolVersion->value]);

								unset($this->handlerTimer[$protocolVersion->value]);
							}

							$deferred->resolve(true);
						}),
					);
				})
				->catch(function (Throwable $ex) use ($deferred, $protocolVersion): void {
					$this->logger->error(
						'Could not create local discovery server',
						[
							'source' => MetadataTypes\Sources\Connector::TUYA->value,
							'type' => 'discovery-client',
							'exception' => ApplicationHelpers\Logger::buildException($ex),
							'protocol' => $protocolVersion->value,
						],
					);

					$deferred->reject($ex);
				});

			$promises[] = $deferred->promise();
		}

		Promise\all($promises)
			->then(async(function (): void {
				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\Sources\Connector::TUYA,
						'Devices discovery finished',
					),
				);
			}))
			->catch(function (): void {
				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\Sources\Connector::TUYA,
						'Devices discovery failed',
					),
				);
			})
			->finally(function (): void {
				foreach ($this->handlerTimer as $index => $timer) {
					$this->eventLoop->cancelTimer($timer);

					unset($this->handlerTimer[$index]);
				}
			});
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Throwable
	 */
	private function discoverCloudDevices(): void
	{
		$this->logger->debug(
			'Starting cloud devices discovery',
			[
				'source' => MetadataTypes\Sources\Connector::TUYA->value,
				'type' => 'discovery-client',
			],
		);

		try {
			await($this->getCloudApiConnection()->connect());
		} catch (Exceptions\OpenApiCall | Exceptions\OpenApiError $ex) {
			$this->logger->error(
				'Could not connect to cloud api',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'discovery-client',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			return;
		}

		$this->getCloudApiConnection()
			->getDevices(
				[
					'source_id' => $this->connectorHelper->getUid($this->connector),
					'source_type' => 'tuyaUser',
				],
			)
			->then(function (API\Messages\Response\GetDevices $response): void {
				$devices = $response->getResult()->getList();

				$this->getCloudApiConnection()
					->getDevicesFactoryInfos(
						array_map(
							static fn (API\Messages\Response\Device $userDevice): string => $userDevice->getId(),
							$devices,
						),
					)
					->then(
						async(function (API\Messages\Response\GetDevicesFactoryInfos $response) use ($devices): void {
							$this->handleFoundCloudDevices($devices, $response->getResult());

							$this->dispatcher?->dispatch(
								new DevicesEvents\TerminateConnector(
									MetadataTypes\Sources\Connector::TUYA,
									'Devices discovery failed',
								),
							);
						}),
					)
					->catch(function (Throwable $ex): void {
						if ($ex instanceof Exceptions\OpenApiError) {
							$this->logger->warning(
								'Loading devices factory infos from cloud failed',
								[
									'source' => MetadataTypes\Sources\Connector::TUYA->value,
									'type' => 'discovery-client',
									'error' => $ex->getMessage(),
								],
							);
						} elseif ($ex instanceof Exceptions\OpenApiCall) {
							$this->logger->error(
								'Loading devices factory infos from cloud failed',
								[
									'source' => MetadataTypes\Sources\Connector::TUYA->value,
									'type' => 'discovery-client',
									'exception' => ApplicationHelpers\Logger::buildException($ex),
								],
							);
						} else {
							$this->logger->error(
								'Could not load device factory infos from Tuya cloud',
								[
									'source' => MetadataTypes\Sources\Connector::TUYA->value,
									'type' => 'discovery-client',
									'exception' => ApplicationHelpers\Logger::buildException($ex),
								],
							);
						}
					});
			})
			->catch(function (Throwable $ex): void {
				if ($ex instanceof Exceptions\OpenApiError) {
					$this->logger->warning(
						'Loading devices from cloud failed',
						[
							'source' => MetadataTypes\Sources\Connector::TUYA->value,
							'type' => 'discovery-client',
							'error' => $ex->getMessage(),
						],
					);
				} elseif ($ex instanceof Exceptions\OpenApiCall) {
					$this->logger->error(
						'Loading devices from cloud failed',
						[
							'source' => MetadataTypes\Sources\Connector::TUYA->value,
							'type' => 'discovery-client',
							'exception' => ApplicationHelpers\Logger::buildException($ex),
						],
					);
				} else {
					$this->logger->error(
						'Could not load devices from Tuya cloud',
						[
							'source' => MetadataTypes\Sources\Connector::TUYA->value,
							'type' => 'discovery-client',
							'exception' => ApplicationHelpers\Logger::buildException($ex),
						],
					);
				}
			});
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleDiscoveredLocalDevice(string $packet): void
	{
		$decryptedPacket = openssl_decrypt(
			substr($packet, 20, -8),
			'AES-128-ECB',
			md5('yGAdlopoPVldABfn', true),
			OPENSSL_RAW_DATA,
		);

		if ($decryptedPacket === false) {
			$this->logger->error(
				'Received invalid packet. Received data could not be decrypted',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'discovery-client',
				],
			);

			return;
		}

		try {
			$decoded = Utils\Json::decode($decryptedPacket, forceArrays: true);

			if (!is_array($decoded)) {
				$this->logger->error(
					'Decoded discovered local message has invalid format',
					[
						'source' => MetadataTypes\Sources\Connector::TUYA->value,
						'type' => 'discovery-client',
					],
				);

				return;
			}

			$deviceInfo = Utils\ArrayHash::from($decoded);

			if (
				!$deviceInfo->offsetExists('gwId')
				|| !$deviceInfo->offsetExists('ip')
				|| !$deviceInfo->offsetExists('productKey')
				|| !$deviceInfo->offsetExists('encrypt')
				|| !$deviceInfo->offsetExists('version')
			) {
				$this->logger->error(
					'Decoded discovered local message has invalid format',
					[
						'source' => MetadataTypes\Sources\Connector::TUYA->value,
						'type' => 'discovery-client',
					],
				);

				return;
			}

			$this->handleFoundLocalDevice(
				strval($deviceInfo->offsetGet('gwId')),
				strval($deviceInfo->offsetGet('ip')),
				boolval($deviceInfo->offsetGet('encrypt')),
				strval($deviceInfo->offsetGet('version')),
			);

		} catch (Utils\JsonException $ex) {
			$this->logger->error(
				'Received invalid packet. Received data are not valid JSON string',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'discovery-client',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			return;
		} catch (Exceptions\Runtime $ex) {
			$this->logger->error(
				'Received data could not be transformed to message',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'discovery-client',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			return;
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function handleFoundLocalDevice(
		string $id,
		string $ipAddress,
		bool $encrypted,
		string $version,
	): void
	{
		try {
			await($this->getCloudApiConnection()->connect());
		} catch (Throwable $ex) {
			$this->logger->error(
				'Could not connect to cloud api',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'discovery-client',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			return;
		}

		$devicesFactoryInfos = [];

		try {
			$response = await($this->getCloudApiConnection()->getDevicesFactoryInfos([$id]));

			$devicesFactoryInfos = $response->getResult();

		} catch (Throwable $ex) {
			$this->logger->error(
				'Loading device factory infos from cloud failed',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'discovery-client',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);
		}

		try {
			$response = await($this->getCloudApiConnection()->getDeviceDetail($id));

			$deviceInformation = $response->getResult();
		} catch (Throwable $ex) {
			$this->logger->error(
				'Could not load device basic information from Tuya cloud',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'discovery-client',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
					'device' => [
						'identifier' => $id,
						'ip_address' => $ipAddress,
					],
				],
			);

			return;
		}

		$dataPoints = $this->loadLocalDeviceDataPoints(
			$deviceInformation->getId(),
			$deviceInformation->getLocalKey(),
			$ipAddress,
			Types\DeviceProtocolVersion::from($version),
		);

		try {
			/** @var array<API\Messages\Response\DeviceFactoryInfos> $deviceFactoryInfosFiltered */
			$deviceFactoryInfosFiltered = array_values(array_filter(
				$devicesFactoryInfos,
				static fn (API\Messages\Response\DeviceFactoryInfos $item): bool => $id === $item->getId(),
			));

			$deviceFactoryInfos = count($deviceFactoryInfosFiltered) > 0 ? $deviceFactoryInfosFiltered[0] : null;

			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreLocalDevice::class,
					[
						'connector' => $this->connector->getId(),
						'id' => $id,
						'ip_address' => $ipAddress,
						'local_key' => $deviceInformation->getLocalKey(),
						'encrypted' => $encrypted,
						'version' => $version,
						'gateway' => $deviceInformation->getGatewayId(),
						'node_id' => $deviceInformation->getNodeId(),
						'name' => $deviceInformation->getName(),
						'model' => $deviceInformation->getModel(),
						'icon' => $deviceInformation->getIcon(),
						'category' => $deviceInformation->getCategory(),
						'latitude' => $deviceInformation->getLat(),
						'longitude' => $deviceInformation->getLon(),
						'product_id' => $deviceInformation->getProductId(),
						'product_name' => $deviceInformation->getProductName(),
						'sn' => $deviceFactoryInfos?->getSn(),
						'mac' => $deviceFactoryInfos?->getMac(),
						'data_points' => $dataPoints,
					],
				),
			);

		} catch (Throwable $ex) {
			$this->logger->error(
				'Could not create device description message',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'discovery-client',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			return;
		}

		if (in_array($deviceInformation->getCategory(), self::GATEWAY_CATEGORIES, true)) {
			try {
				$response = await($this->getCloudApiConnection()->getUserDeviceChildren($id));

				$children = $response->getResult();
			} catch (Throwable $ex) {
				$this->logger->error(
					'Could not load device children from Tuya cloud',
					[
						'source' => MetadataTypes\Sources\Connector::TUYA->value,
						'type' => 'discovery-client',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
						'device' => [
							'identifier' => $id,
							'ip_address' => $ipAddress,
						],
					],
				);

				return;
			}

			foreach ($children as $child) {
				try {
					$response = await($this->getCloudApiConnection()->getDeviceDetail($child->getId()));

					$childDeviceInformation = $response->getResult();
				} catch (Throwable $ex) {
					$this->logger->error(
						'Could not load child device basic information from Tuya cloud',
						[
							'source' => MetadataTypes\Sources\Connector::TUYA->value,
							'type' => 'discovery-client',
							'exception' => ApplicationHelpers\Logger::buildException($ex),
							'device' => [
								'identifier' => $child->getId(),
								'ip_address' => $ipAddress,
							],
						],
					);

					return;
				}

				$dataPoints = $this->loadLocalDeviceDataPoints(
					$child->getId(),
					$deviceInformation->getLocalKey(),
					$ipAddress,
					Types\DeviceProtocolVersion::from($version),
					$id,
					$child->getNodeId(),
				);

				try {
					/** @var array<API\Messages\Response\DeviceFactoryInfos> $childDeviceFactoryInfosFiltered */
					$childDeviceFactoryInfosFiltered = array_values(array_filter(
						$devicesFactoryInfos,
						static fn (API\Messages\Response\DeviceFactoryInfos $item): bool => $id === $item->getId(),
					));

					$childDeviceFactoryInfos = count(
						$childDeviceFactoryInfosFiltered,
					) > 0
						? $childDeviceFactoryInfosFiltered[0]
						: null;

					$this->queue->append(
						$this->messageBuilder->create(
							Queue\Messages\StoreLocalDevice::class,
							[
								'connector' => $this->connector->getId(),
								'id' => $child->getId(),
								'ip_address' => null,
								'local_key' => $childDeviceInformation->getLocalKey(),
								'encrypted' => $encrypted,
								'version' => $version,
								'gateway' => $id,
								'node_id' => $child->getNodeId(),
								'name' => $childDeviceInformation->getName(),
								'model' => $childDeviceInformation->getModel(),
								'icon' => $childDeviceInformation->getIcon(),
								'category' => $childDeviceInformation->getCategory(),
								'latitude' => $childDeviceInformation->getLat(),
								'longitude' => $childDeviceInformation->getLon(),
								'product_id' => $childDeviceInformation->getProductId(),
								'product_name' => $childDeviceInformation->getProductName(),
								'sn' => $childDeviceFactoryInfos?->getSn(),
								'mac' => $childDeviceFactoryInfos?->getMac(),
								'data_points' => $dataPoints,
							],
						),
					);
				} catch (Throwable $ex) {
					$this->logger->error(
						'Could not create child device description message',
						[
							'source' => MetadataTypes\Sources\Connector::TUYA->value,
							'type' => 'discovery-client',
							'exception' => ApplicationHelpers\Logger::buildException($ex),
						],
					);

					return;
				}
			}
		}

		$this->getCloudApiConnection()->disconnect();
	}

	/**
	 * @param array<API\Messages\Response\Device> $devices
	 * @param array<API\Messages\Response\DeviceFactoryInfos> $devicesFactoryInfos
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Throwable
	 */
	private function handleFoundCloudDevices(
		array $devices,
		array $devicesFactoryInfos,
	): void
	{
		try {
			await($this->getCloudApiConnection()->connect());
		} catch (Exceptions\OpenApiCall | Exceptions\OpenApiError $ex) {
			$this->logger->error(
				'Could not connect to cloud api',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'discovery-client',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			return;
		}

		foreach ($devices as $device) {
			/** @var array<API\Messages\Response\DeviceFactoryInfos> $deviceFactoryInfosFiltered */
			$deviceFactoryInfosFiltered = array_values(array_filter(
				$devicesFactoryInfos,
				static fn (API\Messages\Response\DeviceFactoryInfos $item): bool => $device->getId() === $item->getId(),
			));

			$deviceFactoryInfos = count($deviceFactoryInfosFiltered) > 0 ? $deviceFactoryInfosFiltered[0] : null;

			$dataPoints = [];

			try {
				$response = await($this->getCloudApiConnection()->getDeviceSpecification($device->getId()));

				$deviceSpecifications = $response->getResult();

				$dataPointsInfos = [];

				foreach ($deviceSpecifications->getFunctions() as $function) {
					if (!array_key_exists($function->getCode(), $dataPointsInfos)) {
						$dataPointsInfos[$function->getCode()] = [
							'function' => null,
							'status' => null,
						];
					}

					$dataPointsInfos[$function->getCode()]['function'] = $function;
				}

				foreach ($deviceSpecifications->getStatus() as $status) {
					if (!array_key_exists($status->getCode(), $dataPointsInfos)) {
						$dataPointsInfos[$status->getCode()] = [
							'function' => null,
							'status' => null,
						];
					}

					$dataPointsInfos[$status->getCode()]['status'] = $status;
				}

				foreach ($dataPointsInfos as $dataPointInfos) {
					$dataPointFunction = $dataPointInfos['function'];

					$dataPointStatus = $dataPointInfos['status'];

					if ($dataPointFunction === null && $dataPointStatus === null) {
						continue;
					}

					$dataPointCode = null;
					$dataPointDataType = MetadataTypes\DataType::UNKNOWN;

					if ($dataPointFunction !== null) {
						$dataPointCode = $dataPointFunction->getCode();
						$dataPointType = Utils\Strings::lower($dataPointFunction->getType());
						$dataPointSpecification = $dataPointFunction->getValues();

					} else {
						$dataPointCode = $dataPointStatus->getCode();
						$dataPointType = Utils\Strings::lower($dataPointStatus->getType());
						$dataPointSpecification = $dataPointStatus->getValues();
					}

					if ($dataPointType === 'boolean') {
						$dataPointDataType = MetadataTypes\DataType::BOOLEAN;
					} elseif ($dataPointType === 'integer') {
						$dataPointDataType = MetadataTypes\DataType::INT;
					} elseif ($dataPointType === 'enum') {
						$dataPointDataType = MetadataTypes\DataType::ENUM;
					}

					try {
						$dataPointSpecification = Utils\Json::decode($dataPointSpecification, forceArrays: true);
						$dataPointSpecification = Utils\ArrayHash::from(
							is_array($dataPointSpecification) ? $dataPointSpecification : [],
						);

					} catch (Utils\JsonException) {
						$dataPointSpecification = Utils\ArrayHash::from([]);
					}

					$dataPoints[] = [
						'device' => $device->getId(),

						'code' => $dataPointCode,
						'name' => $dataPointCode,
						'data_type' => $dataPointDataType->value,
						'unit' => $dataPointSpecification->offsetExists('unit')
							? strval($dataPointSpecification->offsetGet('unit'))
							: null,
						'range' => $dataPointSpecification->offsetExists('range')
								&& (
									is_array($dataPointSpecification->offsetGet('range'))
									|| $dataPointSpecification->offsetGet('range') instanceof Utils\ArrayHash
								)
							? array_map(
								static fn ($item): string => strval($item),
								(array) $dataPointSpecification->offsetGet('range'),
							)
							: [],
						'min' => $dataPointSpecification->offsetExists('min')
							? floatval($dataPointSpecification->offsetGet('min'))
							: null,
						'max' => $dataPointSpecification->offsetExists('max')
							? floatval($dataPointSpecification->offsetGet('max'))
							: null,
						'step' => $dataPointSpecification->offsetExists('step')
							? floatval($dataPointSpecification->offsetGet('step'))
							: null,
						'scale' => $dataPointSpecification->offsetExists('scale')
							? intval($dataPointSpecification->offsetGet('scale'))
							: null,
						'queryable' => $dataPointStatus !== null,
						'settable' => $dataPointFunction !== null,
					];
				}
			} catch (Exceptions\OpenApiError $ex) {
				$this->logger->warning(
					'Device specification could not be loaded',
					[
						'source' => MetadataTypes\Sources\Connector::TUYA->value,
						'type' => 'discovery-client',
						'error' => $ex->getMessage(),
						'device' => [
							'id' => $device->getId(),
						],
					],
				);
			} catch (Exceptions\OpenApiCall $ex) {
				$this->logger->error(
					'Device specification could not be loaded',
					[
						'source' => MetadataTypes\Sources\Connector::TUYA->value,
						'type' => 'discovery-client',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
						'device' => [
							'id' => $device->getId(),
						],
					],
				);
			}

			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\StoreCloudDevice::class,
					[
						'connector' => $this->connector->getId(),
						'id' => $device->getId(),
						'local_key' => $device->getLocalKey(),
						'ip_address' => $device->getIp(),
						'name' => $device->getName(),
						'model' => $device->getModel(),
						'icon' => $device->getIcon(),
						'category' => $device->getCategory(),
						'latitude' => $device->getLat(),
						'longitude' => $device->getLon(),
						'product_id' => $device->getProductId(),
						'product_name' => $device->getProductName(),
						'sn' => $deviceFactoryInfos?->getSn(),
						'mac' => $deviceFactoryInfos?->getMac(),
						'data_points' => $dataPoints,
					],
				),
			);
		}

		$this->getCloudApiConnection()->disconnect();
	}

	/**
	 * @return array<array<string, mixed>>
	 */
	private function loadLocalDeviceDataPoints(
		string $id,
		string $localKey,
		string $ipAddress,
		Types\DeviceProtocolVersion $version,
		string|null $gatewayId = null,
		string|null $nodeId = null,
	): array
	{
		$dataPoints = [];

		$localApi = $this->localApiFactory->create(
			$id,
			$gatewayId,
			$nodeId,
			$localKey,
			$ipAddress,
			$version,
		);

		try {
			await($localApi->connect());
		} catch (Throwable $ex) {
			$this->logger->error(
				'Could not establish local connection with device',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'discovery-client',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
					'device' => [
						'identifier' => $id,
						'ip_address' => $ipAddress,
						'gateway_id' => $gatewayId,
						'node_id' => $nodeId,
					],
				],
			);

			return [];
		}

		try {
			if ($localApi->isConnected()) {
				/** @var array<API\Messages\Response\DeviceDataPointState>|Types\LocalDeviceError $deviceStatuses */
				$deviceStatuses = await($localApi->readStates());

				$localApi->disconnect();

				if (is_array($deviceStatuses)) {
					foreach ($deviceStatuses as $status) {
						$dataType = MetadataTypes\DataType::UNKNOWN;

						if (is_bool($status->getValue())) {
							$dataType = MetadataTypes\DataType::BOOLEAN;
						} elseif (is_float($status->getValue())) {
							$dataType = MetadataTypes\DataType::FLOAT;
						} elseif (is_numeric($status->getValue())) {
							$dataType = MetadataTypes\DataType::INT;
						} elseif (is_string($status->getValue())) {
							$dataType = MetadataTypes\DataType::STRING;
						}

						$dataPoints[] = [
							'device' => $id,

							'code' => $status->getCode(),
							'name' => $status->getCode(),
							'data_type' => $dataType->value,
							'unit' => null,
							'format' => null,
							'min' => null,
							'max' => null,
							'step' => null,
							'queryable' => true,
							'settable' => true,
						];
					}
				}
			} else {
				$this->logger->error(
					'Local connection with device failed',
					[
						'source' => MetadataTypes\Sources\Connector::TUYA->value,
						'type' => 'discovery-client',
						'device' => [
							'identifier' => $id,
							'ip_address' => $ipAddress,
							'gateway_id' => $gatewayId,
							'node_id' => $nodeId,
						],
					],
				);
			}
		} catch (Throwable $ex) {
			$this->logger->error(
				'Could not read device data points states',
				[
					'source' => MetadataTypes\Sources\Connector::TUYA->value,
					'type' => 'discovery-client',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
					'device' => [
						'identifier' => $id,
						'ip_address' => $ipAddress,
						'gateway_id' => $gatewayId,
						'node_id' => $nodeId,
					],
				],
			);
		}

		return $dataPoints;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function getCloudApiConnection(): API\OpenApi
	{
		if ($this->cloudApiConnection === null) {
			assert(is_string($this->connectorHelper->getAccessId($this->connector)));
			assert(is_string($this->connectorHelper->getAccessSecret($this->connector)));

			$this->cloudApiConnection = $this->openApiFactory->create(
				$this->connector->getIdentifier(),
				$this->connectorHelper->getAccessId($this->connector),
				$this->connectorHelper->getAccessSecret($this->connector),
				$this->connectorHelper->getOpenApiEndpoint($this->connector),
			);
		}

		return $this->cloudApiConnection;
	}

}
