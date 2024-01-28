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
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Loaders as MetadataLoaders;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use IPub\WebSockets;
use IPub\WebSocketsWAMP;
use Nette\Utils;
use Throwable;
use function array_key_exists;
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
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorPropertiesConfigurationRepository,
		private readonly Models\Configuration\Devices\Properties\Repository $devicePropertiesConfigurationRepository,
		private readonly Models\Configuration\Channels\Properties\Repository $channelPropertiesConfigurationRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Devices\Logger $logger,
		private readonly MetadataLoaders\SchemaLoader $schemaLoader,
		private readonly MetadataSchemas\Validator $jsonValidator,
		private readonly MetadataDocuments\DocumentFactory $documentFactory,
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
			$findDevicesProperties = new Queries\Configuration\FindDeviceProperties();

			$devicesProperties = $this->devicePropertiesConfigurationRepository->findAllBy(
				$findDevicesProperties,
			);

			foreach ($devicesProperties as $deviceProperty) {
				if (
					$deviceProperty instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
					|| $deviceProperty instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty
				) {
					$state = $this->devicePropertiesStatesManager->readState($deviceProperty);

					if ($state !== null) {
						$client->send(Utils\Json::encode([
							WebSocketsWAMP\Application\Application::MSG_EVENT,
							$topic->getId(),
							Utils\Json::encode([
								'routing_key' => MetadataTypes\RoutingKey::DEVICE_PROPERTY_DOCUMENT_REPORTED,
								'source' => MetadataTypes\ModuleSource::DEVICES,
								'data' => $state->toArray(),
							]),
						]));
					}
				}
			}

			$findChannelsProperties = new Queries\Configuration\FindChannelProperties();

			$channelsProperties = $this->channelPropertiesConfigurationRepository->findAllBy(
				$findChannelsProperties,
			);

			foreach ($channelsProperties as $channelProperty) {
				if (
					$channelProperty instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
					|| $channelProperty instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty
				) {
					$state = $this->channelPropertiesStatesManager->readState($channelProperty);

					if ($state !== null) {
						$client->send(Utils\Json::encode([
							WebSocketsWAMP\Application\Application::MSG_EVENT,
							$topic->getId(),
							Utils\Json::encode([
								'routing_key' => MetadataTypes\RoutingKey::CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED,
								'source' => MetadataTypes\ModuleSource::DEVICES,
								'data' => $state->toArray(),
							]),
						]));
					}
				}
			}

			$findConnectorsProperties = new Queries\Configuration\FindConnectorProperties();

			$connectorsProperties = $this->connectorPropertiesConfigurationRepository->findAllBy(
				$findConnectorsProperties,
			);

			foreach ($connectorsProperties as $connectorProperty) {
				if ($connectorProperty instanceof MetadataDocuments\DevicesModule\ConnectorDynamicProperty) {
					$state = $this->connectorPropertiesStatesManager->readState($connectorProperty);

					if ($state !== null) {
						$client->send(Utils\Json::encode([
							WebSocketsWAMP\Application\Application::MSG_EVENT,
							$topic->getId(),
							Utils\Json::encode([
								'routing_key' => MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_DOCUMENT_REPORTED,
								'source' => MetadataTypes\ModuleSource::DEVICES,
								'data' => $state->toArray(),
							]),
						]));
					}
				}
			}
		} catch (Throwable $ex) {
			$this->logger->error(
				'State could not be sent to subscriber',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'exchange-controller',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);
		}
	}

	/**
	 * @param array<string, mixed> $args
	 * @param WebSocketsWAMP\Entities\Topics\ITopic<mixed> $topic
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
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

				if ($data !== null) {
					if ($args['routing_key'] === MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_ACTION) {
						$document = $this->documentFactory->create(
							MetadataDocuments\Actions\ActionConnectorProperty::class,
							$data,
						);

						$this->handleConnectorAction($client, $topic, $document);
					} elseif ($args['routing_key'] === MetadataTypes\RoutingKey::DEVICE_PROPERTY_ACTION) {
						$document = $this->documentFactory->create(
							MetadataDocuments\Actions\ActionDeviceProperty::class,
							$data,
						);

						$this->handleDeviceAction($client, $topic, $document);
					} elseif ($args['routing_key'] === MetadataTypes\RoutingKey::CHANNEL_PROPERTY_ACTION) {
						$document = $this->documentFactory->create(
							MetadataDocuments\Actions\ActionChannelProperty::class,
							$data,
						);

						$this->handleChannelAction($client, $topic, $document);
					}
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
			$this->logger->error(
				'Received message could not be validated',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'exchange-controller',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			throw new Exceptions\InvalidArgument('Provided data are not valid json format', 0, $ex);
		} catch (MetadataExceptions\InvalidData $ex) {
			$this->logger->debug(
				'Received message is not valid',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'exchange-controller',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			throw new Exceptions\InvalidArgument('Provided data are not in valid structure', 0, $ex);
		} catch (Throwable $ex) {
			$this->logger->error(
				'Received message is not valid',
				[
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'exchange-controller',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			throw new Exceptions\InvalidArgument('Provided data could not be validated', 0, $ex);
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
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
			$property = $this->connectorPropertiesConfigurationRepository->find($entity->getProperty());

			if (!$property instanceof MetadataDocuments\DevicesModule\ConnectorDynamicProperty) {
				return;
			}

			if ($entity->getSet() !== null) {
				$data = [];

				if ($entity->getSet()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getSet()->getActualValue();
				}

				if ($entity->getSet()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getSet()->getExpectedValue();
				}

				if ($data !== []) {
					$this->connectorPropertiesStatesManager->set(
						$property,
						Utils\ArrayHash::from($data),
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
					);
				}
			} elseif ($entity->getWrite() !== null) {
				$data = [];

				if ($entity->getWrite()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getWrite()->getActualValue();
				}

				if ($entity->getWrite()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getWrite()->getExpectedValue();
				}

				if ($data !== []) {
					$this->connectorPropertiesStatesManager->write(
						$property,
						Utils\ArrayHash::from($data),
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
					);
				}
			}
		} elseif ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::GET)) {
			$property = $this->connectorPropertiesConfigurationRepository->find($entity->getProperty());

			if ($property === null) {
				return;
			}

			$state = $property instanceof MetadataDocuments\DevicesModule\ConnectorDynamicProperty
				? $this->connectorPropertiesStatesManager->readState($property)
				: null;

			if ($state === null) {
				return;
			}

			$client->send(Utils\Json::encode([
				WebSocketsWAMP\Application\Application::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => MetadataTypes\RoutingKey::CONNECTOR_PROPERTY_STATE_DOCUMENT_REPORTED,
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'data' => $state->toArray(),
				]),
			]));
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
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
			$property = $this->devicePropertiesConfigurationRepository->find($entity->getProperty());

			if (
				!$property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
				&& !$property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty
			) {
				return;
			}

			if ($entity->getSet() !== null) {
				$data = [];

				if ($entity->getSet()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getSet()->getActualValue();
				}

				if ($entity->getSet()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getSet()->getExpectedValue();
				}

				if ($data !== []) {
					$this->devicePropertiesStatesManager->set(
						$property,
						Utils\ArrayHash::from($data),
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
					);
				}
			} elseif ($entity->getWrite() !== null) {
				$data = [];

				if ($entity->getWrite()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getWrite()->getActualValue();
				}

				if ($entity->getWrite()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getWrite()->getExpectedValue();
				}

				if ($data !== []) {
					$this->devicePropertiesStatesManager->write(
						$property,
						Utils\ArrayHash::from($data),
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
					);
				}
			}
		} elseif ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::GET)) {
			$property = $this->devicePropertiesConfigurationRepository->find($entity->getProperty());

			if ($property === null) {
				return;
			}

			$state = $property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty
			|| $property instanceof MetadataDocuments\DevicesModule\DeviceMappedProperty
				? $this->devicePropertiesStatesManager->readState($property) : null;

			if ($state === null) {
				return;
			}

			$client->send(Utils\Json::encode([
				WebSocketsWAMP\Application\Application::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => MetadataTypes\RoutingKey::DEVICE_PROPERTY_STATE_DOCUMENT_REPORTED,
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'data' => $state->toArray(),
				]),
			]));
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
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
			$property = $this->channelPropertiesConfigurationRepository->find($entity->getProperty());

			if (
				!$property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
				&& !$property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty
			) {
				return;
			}

			if ($entity->getSet() !== null) {
				$data = [];

				if ($entity->getSet()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getSet()->getActualValue();
				}

				if ($entity->getSet()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getSet()->getExpectedValue();
				}

				if ($data !== []) {
					$this->channelPropertiesStatesManager->set(
						$property,
						Utils\ArrayHash::from($data),
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
					);
				}
			} elseif ($entity->getWrite() !== null) {
				$data = [];

				if ($entity->getWrite()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::ACTUAL_VALUE_FIELD] = $entity->getWrite()->getActualValue();
				}

				if ($entity->getWrite()->getActualValue() !== Metadata\Constants::VALUE_NOT_SET) {
					$data[States\Property::EXPECTED_VALUE_FIELD] = $entity->getWrite()->getExpectedValue();
				}

				if ($data !== []) {
					$this->channelPropertiesStatesManager->write(
						$property,
						Utils\ArrayHash::from($data),
						MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
					);
				}
			}
		} elseif ($entity->getAction()->equalsValue(MetadataTypes\PropertyAction::GET)) {
			$property = $this->channelPropertiesConfigurationRepository->find($entity->getProperty());

			if ($property === null) {
				return;
			}

			$state = $property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
			|| $property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty
				? $this->channelPropertiesStatesManager->readState($property) : null;

			if ($state === null) {
				return;
			}

			$client->send(Utils\Json::encode([
				WebSocketsWAMP\Application\Application::MSG_EVENT,
				$topic->getId(),
				Utils\Json::encode([
					'routing_key' => MetadataTypes\RoutingKey::CHANNEL_PROPERTY_STATE_DOCUMENT_REPORTED,
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'data' => $state->toArray(),
				]),
			]));
		}
	}

}
