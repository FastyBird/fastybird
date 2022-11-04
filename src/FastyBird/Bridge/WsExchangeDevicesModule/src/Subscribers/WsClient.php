<?php declare(strict_types = 1);

/**
 * WsClient.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsExchangeDevicesModuleBridge!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           15.01.22
 */

namespace FastyBird\Bridge\WsExchangeDevicesModule\Subscribers;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Plugin\WsExchange\Events as WsExchangeEvents;
use IPub\WebSocketsWAMP;
use Nette\Utils;
use Psr\Log;
use Symfony\Component\EventDispatcher;
use Throwable;
use function array_merge;

/**
 * WS events subscriber
 *
 * @package         FastyBird:WsExchangeDevicesModuleBridge!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WsClient implements EventDispatcher\EventSubscriberInterface
{

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModels\Connectors\Properties\PropertiesRepository $connectorPropertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly DevicesModels\States\ConnectorPropertiesRepository $connectorPropertiesStatesRepository,
		private readonly DevicesModels\States\DevicePropertiesRepository $devicePropertiesStatesRepository,
		private readonly DevicesModels\States\ChannelPropertiesRepository $channelPropertiesStatesRepository,
		Log\LoggerInterface|null $logger,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public static function getSubscribedEvents(): array
	{
		return [
			WsExchangeEvents\ClientSubscribed::class => 'clientSubscribed',
			WsExchangeEvents\IncomingMessage::class => 'incomingMessage',
		];
	}

	public function clientSubscribed(
		WsExchangeEvents\ClientSubscribed $event,
	): void
	{
		try {
			$findDevicesProperties = new DevicesQueries\FindDeviceProperties();

			$devicesProperties = $this->devicePropertiesRepository->getResultSet($findDevicesProperties);

			foreach ($devicesProperties as $deviceProperty) {
				$dynamicData = [];

				if (
					$deviceProperty instanceof DevicesEntities\Devices\Properties\Dynamic
					|| $deviceProperty instanceof DevicesEntities\Devices\Properties\Mapped
				) {
					$state = $this->devicePropertiesStatesRepository->findOne($deviceProperty);

					if ($state instanceof DevicesStates\DeviceProperty) {
						$dynamicData = $state->toArray();
					}
				}

				$event->getClient()->send(Utils\Json::encode([
					WebSocketsWAMP\Application\Application::MSG_EVENT,
					$event->getTopic()->getId(),
					Utils\Json::encode([
						'routing_key' => MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_REPORTED,
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'data' => array_merge(
							$deviceProperty->toArray(),
							$dynamicData,
						),
					]),
				]));
			}

			$findChannelsProperties = new DevicesQueries\FindChannelProperties();

			$channelsProperties = $this->channelPropertiesRepository->getResultSet($findChannelsProperties);

			foreach ($channelsProperties as $channelProperty) {
				$dynamicData = [];

				if (
					$channelProperty instanceof DevicesEntities\Channels\Properties\Dynamic
					|| $channelProperty instanceof DevicesEntities\Channels\Properties\Mapped
				) {
					$state = $this->channelPropertiesStatesRepository->findOne($channelProperty);

					if ($state instanceof DevicesStates\ChannelProperty) {
						$dynamicData = $state->toArray();
					}
				}

				$event->getClient()->send(Utils\Json::encode([
					WebSocketsWAMP\Application\Application::MSG_EVENT,
					$event->getTopic()->getId(),
					Utils\Json::encode([
						'routing_key' => MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_REPORTED,
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'data' => array_merge(
							$channelProperty->toArray(),
							$dynamicData,
						),
					]),
				]));
			}

			$findConnectorsProperties = new DevicesQueries\FindConnectorProperties();

			$connectorsProperties = $this->connectorPropertiesRepository->getResultSet($findConnectorsProperties);

			foreach ($connectorsProperties as $connectorProperty) {
				$dynamicData = [];

				if ($connectorProperty instanceof DevicesEntities\Connectors\Properties\Dynamic) {
					$state = $this->connectorPropertiesStatesRepository->findOne($connectorProperty);

					if ($state instanceof DevicesStates\ConnectorProperty) {
						$dynamicData = $state->toArray();
					}
				}

				$event->getClient()->send(Utils\Json::encode([
					WebSocketsWAMP\Application\Application::MSG_EVENT,
					$event->getTopic()->getId(),
					Utils\Json::encode([
						'routing_key' => MetadataTypes\RoutingKey::ROUTE_CONNECTOR_PROPERTY_ENTITY_REPORTED,
						'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
						'data' => array_merge(
							$connectorProperty->toArray(),
							$dynamicData,
						),
					]),
				]));
			}
		} catch (Throwable $ex) {
			$this->logger->error('State could not be sent to subscriber', [
				'source' => 'ws-server-plugin-controller',
				'type' => 'subscribe',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);
		}
	}

}
