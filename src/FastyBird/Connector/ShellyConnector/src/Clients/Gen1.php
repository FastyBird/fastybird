<?php declare(strict_types = 1);

/**
 * Gen1.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          0.37.0
 *
 * @date           17.07.22
 */

namespace FastyBird\ShellyConnector\Clients;

use FastyBird\DevicesModule\Exceptions as DevicesModuleExceptions;
use FastyBird\Metadata;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\ShellyConnector\Clients;
use FastyBird\ShellyConnector\Helpers;
use FastyBird\ShellyConnector\Types;
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
		private readonly MetadataEntities\DevicesModule\Connector $connector,
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
	 * @throws DevicesModuleExceptions\Terminate
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
					'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
					'type' => 'gen1-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			throw new DevicesModuleExceptions\Terminate(
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
					'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
					'type' => 'gen1-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			throw new DevicesModuleExceptions\Terminate(
				'mDNS client could not be started',
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @throws DevicesModuleExceptions\InvalidState
	 * @throws DevicesModuleExceptions\Terminate
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function connect(): void
	{
		$mode = $this->connectorHelper->getConfiguration(
			$this->connector->getId(),
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE),
		);

		if ($mode === null) {
			throw new DevicesModuleExceptions\Terminate('Connector client mode is not configured');
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
						'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
						'type' => 'gen1-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				throw new DevicesModuleExceptions\Terminate(
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
						'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
						'type' => 'gen1-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				throw new DevicesModuleExceptions\Terminate(
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
						'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
						'type' => 'gen1-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				throw new DevicesModuleExceptions\Terminate(
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
						'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
						'type' => 'gen1-client',
						'exception' => [
							'message' => $ex->getMessage(),
							'code' => $ex->getCode(),
						],
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
					],
				);

				throw new DevicesModuleExceptions\Terminate(
					'MQTT client could not be started',
					$ex->getCode(),
					$ex,
				);
			}
		} else {
			throw new DevicesModuleExceptions\Terminate('Client mode is not configured');
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
					'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
					'type' => 'gen1-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'connector' => [
						'id' => $this->connector->getId()->toString(),
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
					'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
					'type' => 'gen1-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'connector' => [
						'id' => $this->connector->getId()->toString(),
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
					'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
					'type' => 'gen1-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'connector' => [
						'id' => $this->connector->getId()->toString(),
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
					'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
					'type' => 'gen1-client',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);
		}
	}

}
