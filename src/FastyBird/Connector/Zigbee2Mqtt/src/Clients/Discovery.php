<?php declare(strict_types = 1);

/**
 * Discovery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           31.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Clients;

use BinSoul\Net\Mqtt as NetMqtt;
use Evenement;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\API;
use FastyBird\Connector\Zigbee2Mqtt\Clients;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Connector\Zigbee2Mqtt\Queries;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use React\Promise;
use stdClass;
use Throwable;
use function sprintf;

/**
 * Connector sub-devices discovery client
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Discovery implements Evenement\EventEmitterInterface
{

	use Nette\SmartObject;
	use Evenement\EventEmitterTrait;

	private const DISCOVERY_TIMEOUT = 20;

	private Entities\Devices\Bridge|null $onlyBridge = null;

	private Clients\Subscribers\Bridge $bridgeSubscriber;

	private bool $subscribed = false;

	public function __construct(
		private readonly Entities\Zigbee2MqttConnector $connector,
		private readonly Clients\Subscribers\BridgeFactory $bridgeSubscriberFactory,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Configuration\Connectors\Properties\Repository $connectorsPropertiesConfigurationRepository,
		private readonly DevicesUtilities\ConnectorPropertiesStates $connectorPropertiesStatesManager,
	)
	{
		$this->bridgeSubscriber = $this->bridgeSubscriberFactory->create($this->connector);
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function discover(Entities\Devices\Bridge|null $onlyBridge = null): void
	{
		$this->onlyBridge = $onlyBridge;

		$client = $this->getClient();

		$client->on('connect', [$this, 'onConnect']);

		$findConnectorProperty = new DevicesQueries\Configuration\FindConnectorDynamicProperties();
		$findConnectorProperty->byConnectorId($this->connector->getId());
		$findConnectorProperty->byIdentifier(Types\ConnectorPropertyIdentifier::STATE);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findConnectorProperty,
			MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class,
		);

		$state = $property !== null ? $this->connectorPropertiesStatesManager->readValue($property) : null;

		if ($state === null || $state->getActualValue() !== MetadataTypes\ConnectionState::STATE_RUNNING) {
			$this->bridgeSubscriber->subscribe($client);

			$this->subscribed = true;
		}

		$client->connect();
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function disconnect(): void
	{
		$client = $this->getClient();

		$client->disconnect();

		$client->removeListener('connect', [$this, 'onConnect']);

		if ($this->subscribed) {
			$this->bridgeSubscriber->unsubscribe($client);
		}
	}

	/**
	 * @return Promise\PromiseInterface<true>
	 *
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function discoverSubDevices(
		API\Client $connection,
		Entities\Devices\Bridge $bridge,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$topic = sprintf('%s/bridge/request/permit_join', $bridge->getBaseTopic());

		$connection->subscribe(new NetMqtt\DefaultSubscription($topic))
			->then(static function () use ($deferred, $connection, $topic): void {
				$payload = new stdClass();
				$payload->value = true;
				$payload->time = self::DISCOVERY_TIMEOUT;

				try {
					$connection->publish(
						$topic,
						Utils\Json::encode($payload),
					)
						->then(static function () use ($deferred): void {
							$deferred->resolve(true);
						})
						->catch(static function (Throwable $ex) use ($deferred): void {
							$deferred->reject($ex);
						});
				} catch (Utils\JsonException $ex) {
					$deferred->reject(
						new Exceptions\InvalidState('Discovery action could not be published', $ex->getCode(), $ex),
					);
				}
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function onConnect(): void
	{
		$client = $this->getClient();

		$promises = [];

		if ($this->onlyBridge !== null) {
			$this->logger->debug(
				'Starting sub-devices discovery for selected Zigbee2MQTT bridge',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
					'type' => 'discovery-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'device' => [
						'id' => $this->onlyBridge->getId()->toString(),
					],
				],
			);

			$promises[] = $this->discoverSubDevices($client, $this->onlyBridge);

		} else {
			$this->logger->debug(
				'Starting sub-devices discovery for all registered Zigbee2MQTT bridges',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_ZIGBEE2MQTT,
					'type' => 'discovery-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$findDevicesQuery = new Queries\Entities\FindBridgeDevices();
			$findDevicesQuery->forConnector($this->connector);

			$bridges = $this->devicesRepository->findAllBy(
				$findDevicesQuery,
				Entities\Devices\Bridge::class,
			);

			foreach ($bridges as $bridge) {
				$promises[] = $this->discoverSubDevices($client, $bridge);
			}
		}

		Promise\all($promises)
			->then(function (): void {
				$this->emit('finished');
			})
			->catch(function (): void {
				$this->emit('finished', [[]]);
			});
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function getClient(): API\Client
	{
		return $this->connectionManager->getClient(
			$this->connector->getId()->toString(),
			$this->connector->getServerAddress(),
			$this->connector->getServerPort(),
			$this->connector->getUsername(),
			$this->connector->getPassword(),
		);
	}

}
