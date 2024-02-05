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
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use React\EventLoop;
use React\Promise;
use stdClass;
use Throwable;
use function assert;
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

	private const DISCOVERY_TIMEOUT = 100;

	public const DISCOVERY_TOPIC = '%s/bridge/request/permit_join';

	private MetadataDocuments\DevicesModule\Device|null $onlyBridge = null;

	private Clients\Subscribers\Bridge $bridgeSubscriber;

	private bool $subscribed = false;

	public function __construct(
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
		private readonly Clients\Subscribers\BridgeFactory $bridgeSubscriberFactory,
		private readonly API\ConnectionManager $connectionManager,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Helpers\Devices\Bridge $bridgeHelper,
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesUtilities\ConnectorConnection $connectorConnectionManager,
		private readonly EventLoop\LoopInterface $eventLoop,
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
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function discover(MetadataDocuments\DevicesModule\Device|null $onlyBridge = null): void
	{
		$this->onlyBridge = $onlyBridge;

		$client = $this->getClient();

		$client->on('connect', [$this, 'onConnect']);

		if (!$this->isRunning()) {
			$this->bridgeSubscriber->subscribe($client);

			$this->subscribed = true;
		}

		$client->connect();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function onConnect(): void
	{
		$promises = [];

		if ($this->onlyBridge !== null) {
			$this->logger->debug(
				'Starting sub-devices discovery for selected Zigbee2MQTT bridge',
				[
					'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
					'type' => 'discovery-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'device' => [
						'id' => $this->onlyBridge->getId()->toString(),
					],
				],
			);

			$promises[] = $this->discoverSubDevices($this->onlyBridge);

		} else {
			$this->logger->debug(
				'Starting sub-devices discovery for all registered Zigbee2MQTT bridges',
				[
					'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
					'type' => 'discovery-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
			$findDevicesQuery->forConnector($this->connector);
			$findDevicesQuery->byType(Entities\Devices\Bridge::TYPE);

			$bridges = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);

			foreach ($bridges as $bridge) {
				$promises[] = $this->discoverSubDevices($bridge);
			}
		}

		Promise\all($promises)
			->then(function (): void {
				$this->eventLoop->addTimer(self::DISCOVERY_TIMEOUT, function (): void {
					$this->emit('finished');
				});
			})
			->catch(function (): void {
				$this->emit('finished');
			});
	}

	/**
	 * @return Promise\PromiseInterface<true>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function discoverSubDevices(
		MetadataDocuments\DevicesModule\Device $bridge,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($this->subscribed) {
			$topic = sprintf(Zigbee2Mqtt\Constants::BRIDGE_TOPIC, $this->bridgeHelper->getBaseTopic($bridge));
			$topic = new NetMqtt\DefaultSubscription($topic);

			$this->getClient()
				->subscribe($topic)
				->then(
					function (mixed $subscription) use ($deferred, $bridge): void {
						assert($subscription instanceof NetMqtt\Subscription);

						$this->logger->info(
							sprintf('Subscribed to: %s', $subscription->getFilter()),
							[
								'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
								'type' => 'discovery-client',
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
							],
						);

						$this->publishDiscoveryRequest($bridge)
							->then(static function () use ($deferred): void {
								$deferred->resolve(true);
							})
							->catch(static function (Throwable $ex) use ($deferred): void {
								$deferred->reject($ex);
							});
					},
					function (Throwable $ex): void {
						$this->logger->error(
							$ex->getMessage(),
							[
								'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
								'type' => 'discovery-client',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $this->connector->getId()->toString(),
								],
							],
						);
					},
				);
		} else {
			$this->publishDiscoveryRequest($bridge)
				->then(static function () use ($deferred): void {
					$deferred->resolve(true);
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});
		}

		return $deferred->promise();
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function isRunning(): bool
	{
		return $this->connectorConnectionManager->isRunning($this->connector);
	}

	/**
	 * @return Promise\PromiseInterface<true>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function publishDiscoveryRequest(
		MetadataDocuments\DevicesModule\Device $bridge,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$topic = sprintf(self::DISCOVERY_TOPIC, $this->bridgeHelper->getBaseTopic($bridge));

		$payload = new stdClass();
		$payload->value = true;
		$payload->time = self::DISCOVERY_TIMEOUT;

		try {
			$this->getClient()->publish(
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

		return $deferred->promise();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function getClient(): API\Client
	{
		return $this->connectionManager->getClient(
			$this->connector->getId()->toString(),
			$this->connectorHelper->getServerAddress($this->connector),
			$this->connectorHelper->getServerPort($this->connector),
			$this->connectorHelper->getUsername($this->connector),
			$this->connectorHelper->getPassword($this->connector),
		);
	}

}
