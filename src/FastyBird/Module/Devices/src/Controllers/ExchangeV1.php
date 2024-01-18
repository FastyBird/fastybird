<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 * @since          1.0.0
 *
 * @date           17.04.23
 */

namespace FastyBird\Module\Devices\Controllers;

use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Exchange\Documents as ExchangeEntities;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Loaders as MetadataLoaders;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use IPub\WebSockets;
use IPub\WebSocketsWAMP;
use Nette\Utils;
use Throwable;
use function array_key_exists;
use function array_merge;
use function is_array;

/**
 * Exchange sockets controller
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Controllers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ExchangeV1 extends WebSockets\Application\Controller\Controller
{

	public function __construct(
		private readonly Models\Entities\Connectors\Properties\PropertiesRepository $connectorPropertiesRepository,
		private readonly Models\Entities\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly Models\Entities\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Devices\Logger $logger,
		private readonly MetadataLoaders\SchemaLoader $schemaLoader,
		private readonly MetadataSchemas\Validator $jsonValidator,
		private readonly ExchangeEntities\DocumentFactory $entityFactory,
	)
	{
		parent::__construct();
	}

	/**
	 * @param WebSocketsWAMP\Entities\Topics\ITopic<mixed> $topic
	 */
	public function actionSubscribe(
		WebSocketsWAMP\Entities\Clients\IClient $client,
		WebSocketsWAMP\Entities\Topics\ITopic $topic,
	): void
	{
		$this->logger->debug(
			'Client subscribed to topic',
			[
				'source' => MetadataTypes\ModuleSource::DEVICES,
				'type' => 'exchange-controller',
				'client' => $client->getId(),
				'topic' => $topic->getId(),
			],
		);

		try {
			$findDevicesProperties = new Queries\Entities\FindDeviceProperties();

			$devicesProperties = $this->devicePropertiesRepository->getResultSet($findDevicesProperties);

			foreach ($devicesProperties as $deviceProperty) {
				$dynamicData = [];

				if (
					$deviceProperty instanceof Entities\Devices\Properties\Dynamic
					|| $deviceProperty instanceof Entities\Devices\Properties\Mapped
				) {
					$state = $this->devicePropertiesStatesManager->read($deviceProperty);

					if ($state instanceof States\DeviceProperty) {
						$dynamicData = $state->toArray();
					}
				}

				$client->send(Utils\Json::encode([
					WebSocketsWAMP\Application\Application::MSG_EVENT,
					$topic->getId(),
					Utils\Json::encode([
						'routing_key' => MetadataTypes\RoutingKey::DEVICE_PROPERTY_DOCUMENT_REPORTED,
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'data' => array_merge(
							$deviceProperty->toArray(),
							$dynamicData,
						),
					]),
				]));
			}

			$findChannelsProperties = new Queries\Entities\FindChannelProperties();

			$channelsProperties = $this->channelPropertiesRepository->getResultSet($findChannelsProperties);

			foreach ($channelsProperties as $channelProperty) {
				$dynamicData = [];

				if (
					$channelProperty instanceof Entities\Channels\Properties\Dynamic
					|| $channelProperty instanceof Entities\Channels\Properties\Mapped
				) {
					$state = $this->channelPropertiesStatesManager->read($channelProperty);

					if ($state instanceof States\ChannelProperty) {
						$dynamicData = $state->toArray();
					}
				}

				$client->send(Utils\Json::encode([
					WebSocketsWAMP\Application\Application::MSG_EVENT,
					$topic->getId(),
					Utils\Json::encode([
						'routing_key' => MetadataTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_REPORTED,
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'data' => array_merge(
							$channelProperty->toArray(),
							$dynamicData,
						),
					]),
				]));
			}

			$findConnectorsProperties = new Queries\Entities\FindConnectorProperties();

			$connectorsProperties = $this->connectorPropertiesRepository->getResultSet($findConnectorsProperties);

			foreach ($connectorsProperties as $connectorProperty) {
				$dynamicData = [];

				if ($connectorProperty instanceof Entities\Connectors\Properties\Dynamic) {
					$state = $this->connectorPropertiesStatesManager->read($connectorProperty);

					if ($state instanceof States\ConnectorProperty) {
						$dynamicData = $state->toArray();
					}
				}

				$client->send(Utils\Json::encode([
					WebSocketsWAMP\Application\Application::MSG_EVENT,
					$topic->getId(),
					Utils\Json::encode([
						'routing_key' => MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_REPORTED,
						'source' => MetadataTypes\ModuleSource::DEVICES,
						'data' => array_merge(
							$connectorProperty->toArray(),
							$dynamicData,
						),
					]),
				]));
			}
		} catch (Throwable $ex) {
			$this->logger->error('State could not be sent to subscriber', [
				'source' => MetadataTypes\ModuleSource::DEVICES,
				'type' => 'subscriber',
				'exception' => ApplicationHelpers\Logger::buildException($ex),
			]);
		}
	}

	/**
	 * @param array<string, mixed> $args
	 * @param WebSocketsWAMP\Entities\Topics\ITopic<mixed> $topic
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws Utils\JsonException
	 */
	public function actionCall(
		array $args,
		WebSocketsWAMP\Entities\Clients\IClient $client,
		WebSocketsWAMP\Entities\Topics\ITopic $topic,
	): void
	{
		$this->logger->debug(
			'Received RPC call from client',
			[
				'source' => MetadataTypes\ModuleSource::DEVICES,
				'type' => 'exchange-controller',
				'client' => $client->getId(),
				'topic' => $topic->getId(),
				'data' => $args,
			],
		);

		if (!array_key_exists('routing_key', $args) || !array_key_exists('source', $args)) {
			throw new Exceptions\InvalidArgument('Provided message has invalid format');
		}

		switch ($args['routing_key']) {
			case Metadata\Constants::MESSAGE_BUS_DEVICE_CONTROL_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_DEVICE_PROPERTY_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CHANNEL_CONTROL_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CHANNEL_PROPERTY_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CONNECTOR_CONTROL_ACTION_ROUTING_KEY:
			case Metadata\Constants::MESSAGE_BUS_CONNECTOR_PROPERTY_ACTION_ROUTING_KEY:
				$schema = $this->schemaLoader->loadByRoutingKey(
					MetadataTypes\RoutingKey::get($args['routing_key']),
				);

				/** @var array<string, mixed>|null $data */
				$data = isset($args['data']) && is_array($args['data']) ? $args['data'] : null;
				$data = $data !== null ? $this->parseData($data, $schema) : null;

				$entity = $this->entityFactory->create(
					Utils\Json::encode($data),
					MetadataTypes\RoutingKey::get($args['routing_key']),
				);

				if ($entity instanceof MetadataDocuments\Actions\ActionConnectorProperty) {
					$this->handleConnectorAction($client, $topic, $entity);
				} elseif ($entity instanceof MetadataDocuments\Actions\ActionDeviceProperty) {
					$this->handleDeviceAction($client, $topic, $entity);
				} elseif ($entity instanceof MetadataDocuments\Actions\ActionChannelProperty) {
					$this->handleChannelAction($client, $topic, $entity);
				}

				break;
			default:
				throw new Exceptions\InvalidArgument('Provided message has unsupported routing key');
		}

		$this->payload->data = [
			'response' => 'accepted',
		];
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private function parseData(array $data, string $schema): Utils\ArrayHash
	{
		try {
			return $this->jsonValidator->validate(Utils\Json::encode($data), $schema);
		} catch (Utils\JsonException $ex) {
			$this->logger->error('Received message could not be validated', [
				'source' => MetadataTypes\ModuleSource::DEVICES,
				'type' => 'exchange-controller',
				'exception' => ApplicationHelpers\Logger::buildException($ex),
			]);

			throw new Exceptions\InvalidArgument('Provided data are not valid json format', 0, $ex);
		} catch (MetadataExceptions\InvalidData $ex) {
			$this->logger->debug('Received message is not valid', [
				'source' => MetadataTypes\ModuleSource::DEVICES,
				'type' => 'exchange-controller',
				'exception' => ApplicationHelpers\Logger::buildException($ex),
			]);

			throw new Exceptions\InvalidArgument('Provided data are not in valid structure', 0, $ex);
		} catch (Throwable $ex) {
			$this->logger->error('Received message is not valid', [
				'source' => MetadataTypes\ModuleSource::DEVICES,
				'type' => 'exchange-controller',
				'exception' => ApplicationHelpers\Logger::buildException($ex),
			]);

			throw new Exceptions\InvalidArgument('Provided data could not be validated', 0, $ex);
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws Utils\JsonException
	 */
	private function handleConnectorAction(
		WebSocketsWAMP\Entities\Clients\IClient $client,
		WebSocketsWAMP\Entities\Topics\ITopic $topic,
		MetadataDocuments\Actions\ActionConnectorProperty $entity,
	): void
	{
		if ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::SET)) {
			$property = $this->connectorPropertiesRepository->find($entity->getProperty());

			if (!$property instanceof Entities\Connectors\Properties\Dynamic) {
				return;
			}

			$this->connectorPropertiesStatesManager->write(
				$property,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => $entity->getExpectedValue(),
				]),
			);
		} elseif ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::GET)) {
			$property = $this->connectorPropertiesRepository->find($entity->getProperty());

			if ($property === null) {
				return;
			}

			$state = $property instanceof Entities\Connectors\Properties\Dynamic
				? $this->connectorPropertiesStatesManager->read($property)
				: null;

			$publishRoutingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_REPORTED,
			);

			$responseEntity = $this->entityFactory->create(
				Utils\Json::encode(
					array_merge(
						$property->toArray(),
						$state?->toArray() ?? [],
					),
				),
				$publishRoutingKey,
			);

			$client->send(Utils\Json::encode([
				WebSocketsWAMP\Application\Application::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_REPORTED,
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'data' => $responseEntity->toArray(),
				]),
			]));
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws Utils\JsonException
	 */
	private function handleDeviceAction(
		WebSocketsWAMP\Entities\Clients\IClient $client,
		WebSocketsWAMP\Entities\Topics\ITopic $topic,
		MetadataDocuments\Actions\ActionDeviceProperty $entity,
	): void
	{
		if ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::SET)) {
			$property = $this->devicePropertiesRepository->find($entity->getProperty());

			if (
				!$property instanceof Entities\Devices\Properties\Dynamic
				&& !$property instanceof Entities\Devices\Properties\Mapped
			) {
				return;
			}

			$this->devicePropertiesStatesManager->write(
				$property,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => $entity->getExpectedValue(),
				]),
			);
		} elseif ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::GET)) {
			$property = $this->devicePropertiesRepository->find($entity->getProperty());

			if ($property === null) {
				return;
			}

			$state = $property instanceof Entities\Devices\Properties\Dynamic
			|| $property instanceof Entities\Devices\Properties\Mapped
				? $this->devicePropertiesStatesManager->read($property) : null;

			$publishRoutingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::DEVICE_PROPERTY_DOCUMENT_REPORTED,
			);

			$responseEntity = $this->entityFactory->create(
				Utils\Json::encode(
					array_merge(
						$property->toArray(),
						$state?->toArray() ?? [],
					),
				),
				$publishRoutingKey,
			);

			$client->send(Utils\Json::encode([
				WebSocketsWAMP\Application\Application::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_REPORTED,
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'data' => $responseEntity->toArray(),
				]),
			]));
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws ExchangeExceptions\InvalidArgument
	 * @throws ExchangeExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws Utils\JsonException
	 */
	private function handleChannelAction(
		WebSocketsWAMP\Entities\Clients\IClient $client,
		WebSocketsWAMP\Entities\Topics\ITopic $topic,
		MetadataDocuments\Actions\ActionChannelProperty $entity,
	): void
	{
		if ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::SET)) {
			$property = $this->channelPropertiesRepository->find($entity->getProperty());

			if (
				!$property instanceof Entities\Channels\Properties\Dynamic
				&& !$property instanceof Entities\Channels\Properties\Mapped
			) {
				return;
			}

			$this->channelPropertiesStatesManager->write(
				$property,
				Utils\ArrayHash::from([
					States\Property::EXPECTED_VALUE_FIELD => $entity->getExpectedValue(),
				]),
			);
		} elseif ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::GET)) {
			$property = $this->channelPropertiesRepository->find($entity->getProperty());

			if ($property === null) {
				return;
			}

			$state = $property instanceof Entities\Channels\Properties\Dynamic
			|| $property instanceof Entities\Channels\Properties\Mapped
				? $this->channelPropertiesStatesManager->read($property) : null;

			$publishRoutingKey = MetadataTypes\RoutingKey::get(
				MetadataTypes\RoutingKey::CHANNEL_PROPERTY_DOCUMENT_REPORTED,
			);

			$responseEntity = $this->entityFactory->create(
				Utils\Json::encode(
					array_merge(
						$property->toArray(),
						$state?->toArray() ?? [],
					),
				),
				$publishRoutingKey,
			);

			$client->send(Utils\Json::encode([
				WebSocketsWAMP\Application\Application::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_REPORTED,
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'data' => $responseEntity->toArray(),
				]),
			]));
		}
	}

}
