<?php declare(strict_types = 1);

/**
 * Gen1.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          0.37.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Clients;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use Psr\Log;
use Throwable;

/**
 * Generation 1 devices client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gen1 implements Client
{

	use Nette\SmartObject;

	private Clients\Gen1\Coap|null $coapClient = null;

	private Clients\Gen1\Mdns|null $mdnsClient = null;

	private Clients\Gen1\Http|null $httpClient = null;

	private Clients\Gen1\Mqtt|null $mqttClient = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Entities\ShellyConnector $connector,
		private readonly Clients\Gen1\CoapFactory $coapClientFactory,
		private readonly Clients\Gen1\MdnsFactory $mdnsClientFactory,
		private readonly Clients\Gen1\HttpFactory $httpClientFactory,
		private readonly Clients\Gen1\MqttFactory $mqttClientFactory,
		private readonly Helpers\Connector $connectorHelper,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 */
	public function discover(): void
	{
		$this->coapClient = $this->coapClientFactory->create($this->connector);
		$this->mdnsClient = $this->mdnsClientFactory->create($this->connector);

		try {
			$this->coapClient->connect(true);
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
			$this->mdnsClient->connect();
		} catch (Throwable $ex) {
			$this->logger->error(
				'mDNS client could not be started',
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
				'mDNS client could not be started',
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Terminate
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function connect(): void
	{
		$mode = $this->connectorHelper->getConfiguration(
			$this->connector,
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE),
		);

		if ($mode === null) {
			throw new DevicesExceptions\Terminate('Connector client mode is not configured');
		}

		if ($mode === Types\ClientMode::TYPE_GEN_1_CLASSIC) {
			$this->coapClient = $this->coapClientFactory->create($this->connector);
			$this->mdnsClient = $this->mdnsClientFactory->create($this->connector);
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
				$this->mdnsClient->connect();
			} catch (Throwable $ex) {
				$this->logger->error(
					'mDNS client could not be started',
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
					'mDNS client could not be started',
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
		} elseif ($mode === Types\ClientMode::TYPE_GEN_1_MQTT) {
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
			$this->mdnsClient?->disconnect();
		} catch (Throwable $ex) {
			$this->logger->error(
				'mDNS client could not be disconnected',
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

}
