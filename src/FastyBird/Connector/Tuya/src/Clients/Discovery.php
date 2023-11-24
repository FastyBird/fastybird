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

use Evenement;
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Queue;
use FastyBird\Connector\Tuya\Services;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use Nette\Utils;
use React\Datagram;
use React\EventLoop;
use React\Promise;
use SplObjectStorage;
use Throwable;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_values;
use function assert;
use function boolval;
use function count;
use function floatval;
use function in_array;
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
final class Discovery implements Evenement\EventEmitterInterface
{

	use Nette\SmartObject;
	use Evenement\EventEmitterTrait;

	private const UDP_BIND_IP = '0.0.0.0';

	private const UDP_PORT = [
		Types\DeviceProtocolVersion::V31 => 6_666,
		Types\DeviceProtocolVersion::V32_PLUS => 6_667,
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

	/** @var SplObjectStorage<Entities\Clients\DiscoveredLocalDevice, null> */
	private SplObjectStorage $discoveredLocalDevices;

	/** @var array<EventLoop\TimerInterface> */
	private array $handlerTimer = [];

	public function __construct(
		private readonly Entities\TuyaConnector $connector,
		private readonly API\OpenApiFactory $openApiFactory,
		private readonly API\LocalApiFactory $localApiFactory,
		private readonly Services\DatagramFactory $datagramFactory,
		private readonly Helpers\Entity $entityHelper,
		private readonly Queue\Queue $queue,
		private readonly Tuya\Logger $logger,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
		$this->discoveredLocalDevices = new SplObjectStorage();
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
		$this->discoveredLocalDevices = new SplObjectStorage();

		$mode = $this->connector->getClientMode();

		if ($mode->equalsValue(Types\ClientMode::CLOUD)) {
			$this->discoverCloudDevices();

		} elseif ($mode->equalsValue(Types\ClientMode::LOCAL)) {
			$this->discoverLocalDevices();
		}
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'discovery-client',
					'protocol' => $protocolVersion,
				],
			);

			$deferred = new Promise\Deferred();

			$server = $this->datagramFactory->create(self::UDP_BIND_IP, self::UDP_PORT[$protocolVersion]);

			$server
				->then(function (Datagram\Socket $client) use ($deferred, $protocolVersion): void {
					$client->on('message', async(function (string $message): void {
						$this->handleDiscoveredLocalDevice($message);
					}));

					// Searching timeout
					$this->handlerTimer[$protocolVersion] = $this->eventLoop->addTimer(
						self::UDP_TIMEOUT,
						function () use ($deferred, $protocolVersion): void {
							if (array_key_exists($protocolVersion, $this->handlerTimer)) {
								$this->eventLoop->cancelTimer($this->handlerTimer[$protocolVersion]);

								unset($this->handlerTimer[$protocolVersion]);
							}

							$deferred->resolve(true);
						},
					);
				})
				->catch(function (Throwable $ex) use ($deferred, $protocolVersion): void {
					$this->logger->error(
						'Could not create local discovery server',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'discovery-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'protocol' => $protocolVersion,
						],
					);

					$deferred->reject($ex);
				});

			$promises[] = $deferred->promise();
		}

		Promise\all($promises)
			->then(async(function (): void {
				$this->discoveredLocalDevices->rewind();

				$devices = [];

				foreach ($this->discoveredLocalDevices as $device) {
					$devices[] = $device;
				}

				$this->discoveredLocalDevices = new SplObjectStorage();

				if ($devices !== []) {
					$this->handleFoundLocalDevices($devices);
				}

				$this->emit('finished', [$devices]);
			}))
			->catch(function (): void {
				$this->emit('finished', [[]]);
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
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'discovery-client',
			],
		);

		try {
			await($this->getCloudApiConnection()->connect());
		} catch (Exceptions\OpenApiCall | Exceptions\OpenApiError $ex) {
			$this->logger->error(
				'Could not connect to cloud api',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'discovery-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			return;
		}

		$this->getCloudApiConnection()
			->getDevices(
				[
					'source_id' => $this->connector->getUid(),
					'source_type' => 'tuyaUser',
				],
			)
			->then(function (Entities\API\GetDevices $response): void {
				$devices = $response->getResult()->getList();

				$this->getCloudApiConnection()
					->getDevicesFactoryInfos(
						array_map(
							static fn (Entities\API\Device $userDevice): string => $userDevice->getId(),
							$devices,
						),
					)
					->then(async(function (Entities\API\GetDevicesFactoryInfos $response) use ($devices): void {
						$devices = $this->handleFoundCloudDevices($devices, $response->getResult());

						$this->emit('finished', [$devices]);
					}))
					->catch(function (Throwable $ex): void {
						if ($ex instanceof Exceptions\OpenApiError) {
							$this->logger->warning(
								'Loading devices factory infos from cloud failed',
								[
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
									'type' => 'discovery-client',
									'error' => $ex->getMessage(),
								],
							);
						} elseif ($ex instanceof Exceptions\OpenApiCall) {
							$this->logger->error(
								'Loading devices factory infos from cloud failed',
								[
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
									'type' => 'discovery-client',
									'exception' => BootstrapHelpers\Logger::buildException($ex),
								],
							);
						} else {
							$this->logger->error(
								'Could not load device factory infos from Tuya cloud',
								[
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
									'type' => 'discovery-client',
									'exception' => BootstrapHelpers\Logger::buildException($ex),
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
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'discovery-client',
							'error' => $ex->getMessage(),
						],
					);
				} elseif ($ex instanceof Exceptions\OpenApiCall) {
					$this->logger->error(
						'Loading devices from cloud failed',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'discovery-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
						],
					);
				} else {
					$this->logger->error(
						'Could not load devices from Tuya cloud',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'discovery-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
						],
					);
				}
			});
	}

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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'discovery-client',
				],
			);

			return;
		}

		try {
			$decoded = Utils\Json::decode($decryptedPacket, Utils\Json::FORCE_ARRAY);

			if (!is_array($decoded)) {
				$this->logger->error(
					'Decoded discovered local message has invalid format',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
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
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'discovery-client',
					],
				);

				return;
			}

			$this->discoveredLocalDevices->attach(
				$this->entityHelper->create(
					Entities\Clients\DiscoveredLocalDevice::class,
					[
						'id' => strval($deviceInfo->offsetGet('gwId')),
						'ip_address' => strval($deviceInfo->offsetGet('ip')),
						'product_key' => strval($deviceInfo->offsetGet('productKey')),
						'encrypted' => boolval($deviceInfo->offsetGet('encrypt')),
						'version' => strval($deviceInfo->offsetGet('version')),
					],
				),
			);

		} catch (Utils\JsonException $ex) {
			$this->logger->error(
				'Received invalid packet. Received data are not valid JSON string',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'discovery-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			return;
		} catch (Exceptions\Runtime $ex) {
			$this->logger->error(
				'Received data could not be transformed to entity',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'discovery-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			return;
		}
	}

	/**
	 * @param array<Entities\Clients\DiscoveredLocalDevice> $devices
	 *
	 * @return array<Entities\Clients\DiscoveredLocalDevice>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Throwable
	 */
	private function handleFoundLocalDevices(array $devices): array
	{
		$processedDevices = [];

		try {
			await($this->getCloudApiConnection()->connect());
		} catch (Exceptions\OpenApiCall | Exceptions\OpenApiError $ex) {
			$this->logger->error(
				'Could not connect to cloud api',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'discovery-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			return [];
		}

		$devicesFactoryInfos = [];

		try {
			$response = await($this->getCloudApiConnection()
				->getDevicesFactoryInfos(
					array_map(
						static fn (Entities\Clients\DiscoveredLocalDevice $userDevice): string => $userDevice->getId(),
						$devices,
					),
				));

			$devicesFactoryInfos = $response->getResult();

		} catch (Throwable $ex) {
			$this->logger->error(
				'Loading device factory infos from cloud failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'discovery-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);
		}

		foreach ($devices as $device) {
			try {
				$response = await($this->getCloudApiConnection()->getDeviceDetail($device->getId()));

				$deviceInformation = $response->getResult();
			} catch (Throwable $ex) {
				$this->logger->error(
					'Could not load device basic information from Tuya cloud',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'discovery-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'device' => [
							'identifier' => $device->getId(),
							'ip_address' => $device->getIpAddress(),
						],
					],
				);

				continue;
			}

			$dataPoints = $this->loadLocalDeviceDataPoints(
				$deviceInformation->getId(),
				$deviceInformation->getLocalKey(),
				$device->getIpAddress(),
				Types\DeviceProtocolVersion::get($device->getVersion()),
			);

			try {
				/** @var array<Entities\API\DeviceFactoryInfos> $deviceFactoryInfosFiltered */
				$deviceFactoryInfosFiltered = array_values(array_filter(
					$devicesFactoryInfos,
					static fn (Entities\API\DeviceFactoryInfos $item): bool => $device->getId() === $item->getId(),
				));

				$deviceFactoryInfos = count($deviceFactoryInfosFiltered) > 0 ? $deviceFactoryInfosFiltered[0] : null;

				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\StoreLocalDevice::class,
						[
							'connector' => $this->connector->getId()->toString(),
							'id' => $device->getId(),
							'ip_address' => $device->getIpAddress(),
							'local_key' => $deviceInformation->getLocalKey(),
							'encrypted' => $device->isEncrypted(),
							'version' => $device->getVersion(),
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

				$processedDevices[] = $device;
			} catch (Throwable $ex) {
				$this->logger->error(
					'Could not create device description message',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'discovery-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
					],
				);

				continue;
			}

			if (in_array($deviceInformation->getCategory(), self::GATEWAY_CATEGORIES, true)) {
				try {
					$response = await($this->getCloudApiConnection()->getUserDeviceChildren($device->getId()));

					$children = $response->getResult();
				} catch (Throwable $ex) {
					$this->logger->error(
						'Could not load device children from Tuya cloud',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'discovery-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'device' => [
								'identifier' => $device->getId(),
								'ip_address' => $device->getIpAddress(),
							],
						],
					);

					continue;
				}

				foreach ($children as $child) {
					try {
						$response = await($this->getCloudApiConnection()->getDeviceDetail($child->getId()));

						$childDeviceInformation = $response->getResult();
					} catch (Throwable $ex) {
						$this->logger->error(
							'Could not load child device basic information from Tuya cloud',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'discovery-client',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'device' => [
									'identifier' => $child->getId(),
									'ip_address' => $device->getIpAddress(),
								],
							],
						);

						continue;
					}

					$dataPoints = $this->loadLocalDeviceDataPoints(
						$child->getId(),
						$deviceInformation->getLocalKey(),
						$device->getIpAddress(),
						Types\DeviceProtocolVersion::get($device->getVersion()),
						$device->getId(),
						$child->getNodeId(),
					);

					try {
						/** @var array<Entities\API\DeviceFactoryInfos> $childDeviceFactoryInfosFiltered */
						$childDeviceFactoryInfosFiltered = array_values(array_filter(
							$devicesFactoryInfos,
							static fn (Entities\API\DeviceFactoryInfos $item): bool => $device->getId() === $item->getId(),
						));

						$childDeviceFactoryInfos = count(
							$childDeviceFactoryInfosFiltered,
						) > 0
							? $childDeviceFactoryInfosFiltered[0]
							: null;

						$this->queue->append(
							$this->entityHelper->create(
								Entities\Messages\StoreLocalDevice::class,
								[
									'connector' => $this->connector->getId()->toString(),
									'id' => $child->getId(),
									'ip_address' => null,
									'local_key' => $childDeviceInformation->getLocalKey(),
									'encrypted' => $device->isEncrypted(),
									'version' => $device->getVersion(),
									'gateway' => $device->getId(),
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
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'discovery-client',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
							],
						);

						continue;
					}
				}
			}
		}

		$this->getCloudApiConnection()->disconnect();

		return $processedDevices;
	}

	/**
	 * @param array<Entities\API\Device> $devices
	 * @param array<Entities\API\DeviceFactoryInfos> $devicesFactoryInfos
	 *
	 * @return array<Entities\Clients\DiscoveredCloudDevice>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Throwable
	 */
	private function handleFoundCloudDevices(
		array $devices,
		array $devicesFactoryInfos,
	): array
	{
		$processedDevices = [];

		try {
			await($this->getCloudApiConnection()->connect());
		} catch (Exceptions\OpenApiCall | Exceptions\OpenApiError $ex) {
			$this->logger->error(
				'Could not connect to cloud api',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'discovery-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			return [];
		}

		foreach ($devices as $device) {
			/** @var array<Entities\API\DeviceFactoryInfos> $deviceFactoryInfosFiltered */
			$deviceFactoryInfosFiltered = array_values(array_filter(
				$devicesFactoryInfos,
				static fn (Entities\API\DeviceFactoryInfos $item): bool => $device->getId() === $item->getId(),
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
					$dataPointDataType = Metadata\Types\DataType::get(Metadata\Types\DataType::DATA_TYPE_UNKNOWN);

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
						$dataPointDataType = Metadata\Types\DataType::get(Metadata\Types\DataType::DATA_TYPE_BOOLEAN);
					} elseif ($dataPointType === 'integer') {
						$dataPointDataType = Metadata\Types\DataType::get(Metadata\Types\DataType::DATA_TYPE_INT);
					} elseif ($dataPointType === 'enum') {
						$dataPointDataType = Metadata\Types\DataType::get(Metadata\Types\DataType::DATA_TYPE_ENUM);
					}

					try {
						$dataPointSpecification = Utils\Json::decode($dataPointSpecification, Utils\Json::FORCE_ARRAY);
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
						'data_type' => $dataPointDataType->getValue(),
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
							? floatval($dataPointSpecification->offsetGet('scale'))
							: null,
						'queryable' => $dataPointStatus !== null,
						'settable' => $dataPointFunction !== null,
					];
				}
			} catch (Exceptions\OpenApiError $ex) {
				$this->logger->warning(
					'Device specification could not be loaded',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
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
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'discovery-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'device' => [
							'id' => $device->getId(),
						],
					],
				);
			}

			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreCloudDevice::class,
					[
						'connector' => $this->connector->getId()->toString(),
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

			$processedDevices[] = $this->entityHelper->create(
				Entities\Clients\DiscoveredCloudDevice::class,
				[
					'id' => $device->getId(),
					'ip_address' => $device->getIp(),
				],
			);
		}

		$this->getCloudApiConnection()->disconnect();

		return $processedDevices;
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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'discovery-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
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
				/** @var array<Entities\API\DeviceDataPointState>|Types\LocalDeviceError $deviceStatuses */
				$deviceStatuses = await($localApi->readStates());

				$localApi->disconnect();

				if (is_array($deviceStatuses)) {
					foreach ($deviceStatuses as $status) {
						$dataType = Metadata\Types\DataType::get(Metadata\Types\DataType::DATA_TYPE_UNKNOWN);

						if (is_bool($status->getValue())) {
							$dataType = Metadata\Types\DataType::get(Metadata\Types\DataType::DATA_TYPE_BOOLEAN);
						} elseif (is_float($status->getValue())) {
							$dataType = Metadata\Types\DataType::get(Metadata\Types\DataType::DATA_TYPE_FLOAT);
						} elseif (is_numeric($status->getValue())) {
							$dataType = Metadata\Types\DataType::get(Metadata\Types\DataType::DATA_TYPE_INT);
						} elseif (is_string($status->getValue())) {
							$dataType = Metadata\Types\DataType::get(Metadata\Types\DataType::DATA_TYPE_STRING);
						}

						$dataPoints[] = [
							'device' => $id,

							'code' => $status->getCode(),
							'name' => $status->getCode(),
							'data_type' => $dataType->getValue(),
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
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'discovery-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function getCloudApiConnection(): API\OpenApi
	{
		if ($this->cloudApiConnection === null) {
			assert(is_string($this->connector->getAccessId()));
			assert(is_string($this->connector->getAccessSecret()));

			$this->cloudApiConnection = $this->openApiFactory->create(
				$this->connector->getIdentifier(),
				$this->connector->getAccessId(),
				$this->connector->getAccessSecret(),
				$this->connector->getOpenApiEndpoint(),
			);
		}

		return $this->cloudApiConnection;
	}

}
