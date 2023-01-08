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

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Entities\ShellyConnector $connector,
		private readonly Clients\Local\CoapFactory $coapClientFactory,
		private readonly Clients\Local\HttpFactory $httpClientFactory,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 */
	public function connect(): void
	{
		$this->coapClient = $this->coapClientFactory->create($this->connector);
		$this->httpClient = $this->httpClientFactory->create($this->connector);

		try {
			$this->coapClient->connect();
		} catch (Throwable $ex) {
			$this->logger->error(
				'CoAP client could not be started',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'local-client',
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
					'type' => 'local-client',
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
					'type' => 'local-client',
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
					'type' => 'local-client',
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
	 * @throws Exceptions\HttpApiCall
	 * @throws DevicesExceptions\InvalidState
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

		return $deferred->promise();
	}

}
