<?php declare(strict_types = 1);

/**
 * Discovery.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 * @since          0.13.0
 *
 * @date           27.08.22
 */

namespace FastyBird\Connector\Tuya\Clients;

use Evenement;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Consumers;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Metadata;
use FastyBird\Metadata\Entities as MetadataEntities;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\Datagram;
use React\EventLoop;
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
		Types\DeviceProtocolVersion::VERSION_V31 => 6_666,
		Types\DeviceProtocolVersion::VERSION_V33 => 6_667,
	];

	private const UDP_TIMEOUT = 5;

	/** @var Array<string> */
	private array $processedProtocols = [];

	/** @var SplObjectStorage<Entities\API\DiscoveredLocalDevice, null> */
	private SplObjectStorage $discoveredLocalDevices;

	private EventLoop\TimerInterface|null $handlerTimer = null;

	private Datagram\SocketInterface|null $connection = null;

	private API\OpenApi|null $openApiApi = null;

	private Datagram\Factory $serverFactory;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly MetadataEntities\DevicesModule\Connector $connector,
		private readonly API\OpenApiFactory $openApiApiFactory,
		private readonly API\LocalApiFactory $localApiFactory,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Consumers\Messages $consumer,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();

		$this->serverFactory = new Datagram\Factory($this->eventLoop);
	}

	/**
	 * @throws Throwable
	 */
	public function discover(): void
	{
		$this->discoveredLocalDevices = new SplObjectStorage();

		$mode = $this->connectorHelper->getConfiguration(
			$this->connector->getId(),
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE),
		);

		if ($mode === Types\ClientMode::MODE_CLOUD) {
			$this->discoverCloudDevices();

		} elseif ($mode === Types\ClientMode::MODE_LOCAL) {
			$this->discoverLocalDevices();
		}
	}

	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);
		}
	}

	/**
	 * @throws Throwable
	 */
	private function discoverLocalDevices(): void
	{
		$knownProtocolsVersions = [
			Types\DeviceProtocolVersion::VERSION_V31,
			Types\DeviceProtocolVersion::VERSION_V33,
		];

		// Process all known protocols
		foreach ($knownProtocolsVersions as $protocolVersion) {
			if (in_array($protocolVersion, $this->processedProtocols, true)) {
				continue;
			}

			$this->logger->debug(
				'Starting local devices discovery',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'discovery-client',
					'protocol' => $protocolVersion,
				],
			);

			try {
				$server = await($this->serverFactory->createServer(
					self::UDP_BIND_IP . ':' . self::UDP_PORT[$protocolVersion],
				));
				assert($server instanceof Datagram\Socket);

				$server->on('message', function (string $message, string $remote): void {
					$this->handleDiscoveredLocalDevice($message);
				});

				$this->connection = $server;
			} catch (Throwable $ex) {
				$this->logger->error(
					'Could not create local discovery server',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'discovery-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'protocol' => $protocolVersion,
					],
				);
			}

			// Searching timeout
			$this->eventLoop->addTimer(
				self::UDP_TIMEOUT,
				async(function (): void {
					$this->connection?->close();
					$this->connection = null;

					$this->discoverLocalDevices();
				}),
			);

			$this->processedProtocols[] = $protocolVersion;

			return;
		}

		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);
		}

		$this->discoveredLocalDevices->rewind();

		$devices = [];

		foreach ($this->discoveredLocalDevices as $device) {
			$devices[] = $device;
		}

		$this->discoveredLocalDevices = new SplObjectStorage();

		$devices = $this->handleFoundLocalDevices($devices);

		$this->emit('finished', [$devices]);
	}

	/**
	 * @throws Throwable
	 */
	private function discoverCloudDevices(): void
	{
		$this->openApiApi = $this->openApiApiFactory->create($this->connector);

		$this->logger->debug(
			'Starting cloud devices discovery',
			[
				'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
				'type' => 'discovery-client',
			],
		);

		try {
			/** @var Array<Entities\API\DeviceInformation> $devices */
			$devices = await($this->openApiApi->getDevices([
				'source_id' => $this->connectorHelper->getConfiguration(
					$this->connector->getId(),
					Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_UID),
				),
				'source_type' => 'tuyaUser',
			]));

		} catch (Exceptions\OpenApiCall $ex) {
			$this->logger->error(
				'Loading devices from cloud failed',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'discovery-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
				],
			);

			return;
		}

		try {
			/** @var Array<Entities\API\DeviceFactoryInfos> $devicesFactoryInfos */
			$devicesFactoryInfos = await($this->openApiApi->getDevicesFactoryInfos(
				array_map(
					static fn (Entities\API\DeviceInformation $userDevice): string => $userDevice->getId(),
					$devices,
				),
			));

		} catch (Exceptions\OpenApiCall $ex) {
			$this->logger->error(
				'Loading devices factory infos from cloud failed',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'discovery-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
				],
			);

			return;
		}

		$devices = $this->handleFoundCloudDevices($devices, $devicesFactoryInfos);

		$this->emit('finished', [$devices]);
	}

	private function handleDiscoveredLocalDevice(string $packet): void
	{
		$encryptedPacket = openssl_decrypt(
			substr($packet, 20, -8),
			'AES-128-ECB',
			md5('yGAdlopoPVldABfn', true),
			OPENSSL_RAW_DATA,
		);

		if ($encryptedPacket === false) {
			$this->logger->error(
				'Received invalid packet. Received data could not be decrypted',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'discovery-client',
				],
			);

			return;
		}

		try {
			$decoded = Utils\Json::decode($encryptedPacket, Utils\Json::FORCE_ARRAY);

			if (!is_array($decoded)) {
				$this->logger->error(
					'Decoded discovered local message has invalid format',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
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
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'discovery-client',
					],
				);

				return;
			}

			$this->discoveredLocalDevices->attach(new Entities\API\DiscoveredLocalDevice(
				strval($deviceInfo->offsetGet('gwId')),
				strval($deviceInfo->offsetGet('ip')),
				strval($deviceInfo->offsetGet('productKey')),
				boolval($deviceInfo->offsetGet('encrypt')),
				strval($deviceInfo->offsetGet('version')),
			));

		} catch (Utils\JsonException $ex) {
			$this->logger->error(
				'Received invalid packet. Received data are not valid JSON string',
				[
					'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
					'type' => 'discovery-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
				],
			);

			return;
		}
	}

	/**
	 * @param Array<Entities\API\DiscoveredLocalDevice> $devices
	 *
	 * @return Array<Entities\Messages\DiscoveredLocalDevice>
	 *
	 * @throws Throwable
	 */
	private function handleFoundLocalDevices(array $devices): array
	{
		$processedDevices = [];

		$this->openApiApi = $this->openApiApiFactory->create($this->connector);

		$this->openApiApi->connect();

		foreach ($devices as $device) {
			$dataPoints = [];

			try {
				$deviceInformation = await($this->openApiApi->getDeviceInformation($device->getId()));
				assert($deviceInformation instanceof Entities\API\DeviceInformation);
			} catch (Throwable $ex) {
				$this->logger->error(
					'Could not load device basic information from Tuya cloud',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'discovery-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
					],
				);

				continue;
			}

			$localApi = $this->localApiFactory->create(
				$device->getId(),
				$deviceInformation->getGatewayId(),
				$deviceInformation->getLocalKey(),
				$device->getIpAddress(),
				Types\DeviceProtocolVersion::get($device->getVersion()),
			);

			try {
				await($localApi->connect());
			} catch (Throwable $ex) {
				$this->logger->error(
					'Could not create connection with device',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'discovery-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
					],
				);

				continue;
			}

			try {
				if ($localApi->isConnected()) {
					/** @var Array<Entities\API\DeviceDataPointStatus> $deviceStatuses */
					$deviceStatuses = await($localApi->readStates());

					$localApi->disconnect();

					foreach ($deviceStatuses as $status) {
						$dataType = Metadata\Types\DataType::get(Metadata\Types\DataType::DATA_TYPE_UNKNOWN);

						if (is_bool($status->getValue())) {
							$dataType = Metadata\Types\DataType::get(Metadata\Types\DataType::DATA_TYPE_BOOLEAN);
						} elseif (is_numeric($status->getValue())) {
							$dataType = Metadata\Types\DataType::get(Metadata\Types\DataType::DATA_TYPE_INT);
						} elseif (is_string($status->getValue())) {
							$dataType = Metadata\Types\DataType::get(Metadata\Types\DataType::DATA_TYPE_STRING);
						}

						$dataPoints[] = new Entities\Messages\DiscoveredLocalDataPoint(
							$device->getId(),
							$status->getCode(),
							$status->getCode(),
							$dataType,
							null,
							null,
							null,
							null,
							null,
							true,
							true,
							Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_DISCOVERY),
						);
					}
				} else {
					$this->logger->error(
						'Could not connect to device',
						[
							'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
							'type' => 'discovery-client',
						],
					);
				}

				$message = new Entities\Messages\DiscoveredLocalDevice(
					$this->connector->getId(),
					$device->getId(),
					$device->getIpAddress(),
					$deviceInformation->getLocalKey(),
					$device->isEncrypted(),
					$device->getVersion(),
					$dataPoints,
					Types\MessageSource::get(Types\MessageSource::SOURCE_LOCAL_DISCOVERY),
				);

				$processedDevices[] = $message;

				$this->consumer->append($message);
			} catch (Throwable $ex) {
				$this->logger->error(
					'Could not read device data points states',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'discovery-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
					],
				);

				continue;
			}
		}

		return $processedDevices;
	}

	/**
	 * @param Array<Entities\API\DeviceInformation> $devices
	 * @param Array<Entities\API\DeviceFactoryInfos> $devicesFactoryInfos
	 *
	 * @return Array<Entities\Messages\DiscoveredCloudDevice>
	 *
	 * @throws Throwable
	 */
	private function handleFoundCloudDevices(
		array $devices,
		array $devicesFactoryInfos,
	): array
	{
		if ($this->openApiApi === null) {
			return [];
		}

		$processedDevices = [];

		foreach ($devices as $device) {
			/** @var Array<Entities\API\DeviceFactoryInfos> $deviceFactoryInfosFiltered */
			$deviceFactoryInfosFiltered = array_values(array_filter(
				$devicesFactoryInfos,
				static fn (Entities\API\DeviceFactoryInfos $item): bool => $device->getId() === $item->getId(),
			));

			$deviceFactoryInfos = count($deviceFactoryInfosFiltered) > 0 ? $deviceFactoryInfosFiltered[0] : null;

			$dataPoints = [];

			try {
				$deviceSpecifications = await($this->openApiApi->getDeviceSpecification($device->getId()));
				assert($deviceSpecifications instanceof Entities\API\DeviceSpecification);

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

					$dataPoints[] = new Entities\Messages\DiscoveredCloudDataPoint(
						$device->getId(),
						$dataPointCode,
						$dataPointCode,
						$dataPointDataType,
						$dataPointSpecification->offsetExists('unit') ? strval(
							$dataPointSpecification->offsetGet('unit'),
						) : null,
						$dataPointSpecification->offsetExists('range') && is_array(
							$dataPointSpecification->offsetGet('range'),
						) ? array_map(
							static fn ($item): string => strval($item),
							$dataPointSpecification->offsetGet('range'),
						) : [],
						$dataPointSpecification->offsetExists('min') ? floatval(
							$dataPointSpecification->offsetGet('min'),
						) : null,
						$dataPointSpecification->offsetExists('max') ? floatval(
							$dataPointSpecification->offsetGet('max'),
						) : null,
						$dataPointSpecification->offsetExists('step') ? floatval(
							$dataPointSpecification->offsetGet('step'),
						) : null,
						$dataPointSpecification->offsetExists('scale') ? floatval(
							$dataPointSpecification->offsetGet('scale'),
						) : null,
						$dataPointStatus !== null,
						$dataPointFunction !== null,
						Types\MessageSource::get(Types\MessageSource::SOURCE_CLOUD_DISCOVERY),
					);
				}
			} catch (Exceptions\OpenApiCall $ex) {
				$this->logger->error(
					'Device specification could not be loaded',
					[
						'source' => Metadata\Constants::CONNECTOR_TUYA_SOURCE,
						'type' => 'discovery-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'device' => [
							'id' => $device->getId(),
						],
					],
				);
			}

			$message = new Entities\Messages\DiscoveredCloudDevice(
				$this->connector->getId(),
				$device->getId(),
				$device->getLocalKey(),
				$device->getIp(),
				$device->getName(),
				$device->getModel(),
				$deviceFactoryInfos?->getSn(),
				$deviceFactoryInfos?->getMac(),
				$dataPoints,
				Types\MessageSource::get(Types\MessageSource::SOURCE_CLOUD_DISCOVERY),
			);

			$processedDevices[] = $message;

			$this->consumer->append($message);
		}

		return $processedDevices;
	}

}
