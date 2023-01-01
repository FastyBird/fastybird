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
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Clients;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use Psr\Log;
use React\Promise;
use Throwable;

/**
 * Local devices client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Local implements Client
{

	use Nette\SmartObject;

	private Clients\Local\Coap|null $coapClient = null;

	private Clients\Local\Http|null $httpClient = null;

	private Clients\Local\Mqtt|null $mqttClient = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Entities\ShellyConnector $connector,
		private readonly Clients\Local\CoapFactory $coapClientFactory,
		private readonly Clients\Local\HttpFactory $httpClientFactory,
		private readonly Clients\Local\MqttFactory $mqttClientFactory,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Terminate
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function connect(): void
	{
		$mode = $this->connector->getClientMode();

		if ($mode->equalsValue(Types\ClientMode::MODE_LOCAL)) {
			$this->coapClient = $this->coapClientFactory->create($this->connector);
			$this->httpClient = $this->httpClientFactory->create($this->connector);

			try {
				$this->coapClient->connect();
			} catch (Throwable $ex) {
				$this->logger->error(
					'CoAP client could not be started',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'gen1-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
					],
				);

				throw new DevicesExceptions\Terminate(
					'CoAP client could not be started',
					$ex->getCode(),
					$ex,
				);
			}

			try {
				$this->httpClient->connect();
			} catch (Throwable $ex) {
				$this->logger->error(
					'Http api client could not be started',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'gen1-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
					],
				);

				throw new DevicesExceptions\Terminate(
					'Http api client could not be started',
					$ex->getCode(),
					$ex,
				);
			}
		} elseif ($mode->equalsValue(Types\ClientMode::MODE_MQTT)) {
			$this->mqttClient = $this->mqttClientFactory->create($this->connector);

			try {
				$this->mqttClient->connect();
			} catch (Throwable $ex) {
				$this->logger->error(
					'MQTT client could not be started',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'gen1-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
					],
				);

				throw new DevicesExceptions\Terminate(
					'MQTT client could not be started',
					$ex->getCode(),
					$ex,
				);
			}
		} else {
			throw new DevicesExceptions\Terminate('Client mode is not configured');
		}
	}

	public function disconnect(): void
	{
		try {
			$this->coapClient?->disconnect();
		} catch (Throwable $ex) {
			$this->logger->error(
				'CoAP client could not be disconnected',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'gen1-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);
		}

		try {
			$this->httpClient?->disconnect();
		} catch (Throwable $ex) {
			$this->logger->error(
				'Http api client could not be disconnected',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'gen1-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);
		}

		try {
			$this->mqttClient?->disconnect();
		} catch (Throwable $ex) {
			$this->logger->error(
				'MQTT client could not be disconnected',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'gen1-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Terminate
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeChannelProperty(
		Entities\ShellyDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic $property,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$this->httpClient?->writeChannelProperty($device, $channel, $property)
			->then(static function () use ($deferred): void {
				$deferred->resolve();
			});

		$this->mqttClient?->writeChannelProperty($device, $channel, $property)
			->then(static function () use ($deferred): void {
				$deferred->resolve();
			});

		return $deferred->promise();
	}

}
