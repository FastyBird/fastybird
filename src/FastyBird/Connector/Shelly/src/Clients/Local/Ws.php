<?php declare(strict_types = 1);

/**
 * Local.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Shelly\Clients\Local;

use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Consumers;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use Psr\Log;
use React\Promise;
use Throwable;
use function array_key_exists;
use function assert;

/**
 * Websockets client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Ws
{

	use Gen2;
	use Nette\SmartObject;

	private const RECONNECT_COOL_DOWN_TIME = 300.0;

	/** @var array<string, API\WsApi> */
	private array $devicesClients = [];

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly API\WsApiFactory $wsApiFactory,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		protected readonly Consumers\Messages $consumer,
		protected readonly DevicesModels\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		protected readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		protected readonly DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @param array<DevicesEntities\Devices\Device> $devices
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function connect(array $devices): void
	{
		foreach ($devices as $device) {
			assert($device instanceof Entities\ShellyDevice);

			$this->createDeviceClient($device);
		}
	}

	public function disconnect(): void
	{
		foreach ($this->devicesClients as $client) {
			$client->disconnect();
		}
	}

	public function writeChannelProperty(
		Entities\ShellyDevice $device,
		DevicesEntities\Channels\Properties\Dynamic $property,
		bool|float|int|string $value,
	): Promise\PromiseInterface
	{
		$client = $this->getDeviceClient($device);

		if ($client === null) {
			return Promise\reject(new Exceptions\InvalidArgument('For provided device is not created client'));
		}

		return $client->writeState(
			$property->getIdentifier(),
			$value,
		);
	}

	/**
	 * @param callable(Entities\API\Gen2\DeviceStatus $status): void|null $onFulfilled
	 * @param callable(Throwable $ex): void|null $onRejected
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function readDeviceStates(
		Entities\ShellyDevice $device,
		callable|null $onFulfilled = null,
		callable|null $onRejected = null,
	): bool
	{
		$client = $this->getDeviceClient($device);

		if ($client === null) {
			$this->createDeviceClient($device);

			return false;
		}

		if (!$client->isConnected()) {
			if (!$client->isConnecting()) {
				if (
					$client->getLastConnectAttempt() === null
					|| (
						// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
						$this->dateTimeFactory->getNow()->getTimestamp() - $client->getLastConnectAttempt()->getTimestamp() >= self::RECONNECT_COOL_DOWN_TIME
					)
				) {
					$client->connect();

				} else {
					$this->consumer->append(
						new Entities\Messages\DeviceState(
							$device->getConnector()->getId(),
							$device->getIdentifier(),
							MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_DISCONNECTED),
						),
					);
				}
			}

			return false;
		}

		$client->readStates()
			->then(function (Entities\API\Gen2\DeviceStatus $status) use ($device, $onFulfilled): void {
				$this->processDeviceStatus($device, $status);

				if ($onFulfilled !== null) {
					$onFulfilled($status);
				}
			})
			->otherwise(function (Throwable $ex) use ($device, $onRejected): void {
				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$device->getConnector()->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_DISCONNECTED),
					),
				);

				if ($onRejected !== null) {
					$onRejected($ex);
				}
			});

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function createDeviceClient(Entities\ShellyDevice $device): void
	{
		if (!$device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_2)) {
			return;
		}

		if ($device->getIpAddress() === null && $device->getDomain() === null) {
			return;
		}

		$client = $this->wsApiFactory->create(
			$device->getIdentifier(),
			$device->getIpAddress(),
			$device->getDomain(),
			$device->getUsername(),
			$device->getPassword(),
		);

		$client->on(
			'message',
			function (Entities\API\Entity $message) use ($device): void {
				if ($message instanceof Entities\API\Gen2\DeviceStatus) {
					$this->processDeviceStatus($device, $message);
				}
			},
		);

		$client->on(
			'connected',
			function () use ($client, $device): void {
				$this->logger->debug(
					'Connected to device',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'ws-client',
						'device' => [
							'id' => $device->getPlainId(),
						],
					],
				);

				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$device->getConnector()->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_CONNECTED),
					),
				);

				$client->readStates()
					->then(function (Entities\API\Gen2\DeviceStatus $status) use ($device): void {
						$this->processDeviceStatus($device, $status);
					})
					->otherwise(function (Throwable $ex) use ($device): void {
						$this->consumer->append(
							new Entities\Messages\DeviceState(
								$device->getConnector()->getId(),
								$device->getIdentifier(),
								MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_DISCONNECTED),
							),
						);

						$this->logger->error(
							'An error occurred on initial device state reading',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
								'type' => 'ws-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'device' => [
									'identifier' => $device->getIdentifier(),
								],
							],
						);
					});
			},
		);

		$client->on(
			'disconnected',
			function () use ($device): void {
				$this->logger->debug(
					'Disconnected from device',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'ws-client',
						'device' => [
							'id' => $device->getPlainId(),
						],
					],
				);

				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$device->getConnector()->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_DISCONNECTED),
					),
				);
			},
		);

		$client->on(
			'error',
			function (Throwable $ex) use ($device): void {
				$this->logger->warning(
					'Connection with device failed',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'ws-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'device' => [
							'id' => $device->getPlainId(),
						],
					],
				);

				$this->consumer->append(
					new Entities\Messages\DeviceState(
						$device->getConnector()->getId(),
						$device->getIdentifier(),
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_DISCONNECTED),
					),
				);
			},
		);

		$this->devicesClients[$device->getPlainId()] = $client;
	}

	private function getDeviceClient(Entities\ShellyDevice $device): API\WsApi|null
	{
		return array_key_exists(
			$device->getPlainId(),
			$this->devicesClients,
		)
			? $this->devicesClients[$device->getPlainId()]
			: null;
	}

}
