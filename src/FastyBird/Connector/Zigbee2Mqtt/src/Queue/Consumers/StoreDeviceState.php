<?php declare(strict_types = 1);

/**
 * StoreDeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           01.01.24
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Queue\Consumers;

use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use function array_merge;
use function implode;
use function preg_match;
use function React\Async\await;
use function sprintf;

/**
 * Store device state message consumer
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Zigbee2Mqtt\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreDeviceState) {
			return false;
		}

		$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findDevicePropertyQuery->byIdentifier(Zigbee2Mqtt\Types\DevicePropertyIdentifier::BASE_TOPIC);
		$findDevicePropertyQuery->byValue($message->getBaseTopic());

		$baseTopicProperty = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
		);

		if ($baseTopicProperty === null) {
			return true;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byId($baseTopicProperty->getDevice());
		$findDeviceQuery->byType(Entities\Devices\Bridge::TYPE);

		$bridge = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($bridge === null) {
			return true;
		}

		$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findDevicePropertyQuery->byValue($message->getDevice());

		if (preg_match(Zigbee2Mqtt\Constants::IEEE_ADDRESS_REGEX, $message->getDevice()) === 1) {
			$findDevicePropertyQuery->byIdentifier(Zigbee2Mqtt\Types\DevicePropertyIdentifier::IEEE_ADDRESS);

		} else {
			$findDevicePropertyQuery->byIdentifier(Zigbee2Mqtt\Types\DevicePropertyIdentifier::FRIENDLY_NAME);
		}

		$deviceTypeProperty = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			MetadataDocuments\DevicesModule\DeviceVariableProperty::class,
		);

		if ($deviceTypeProperty === null) {
			return true;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byId($deviceTypeProperty->getDevice());
		$findDeviceQuery->forParent($bridge);
		$findDeviceQuery->byType(Entities\Devices\SubDevice::TYPE);

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			return true;
		}

		$this->processStates($bridge, $device, $message->getStates());

		$this->logger->debug(
			'Consumed device state message',
			[
				'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
				'type' => 'store-device-state-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
				],
				'bridge' => [
					'id' => $bridge->getId()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $message->toArray(),
			],
		);

		return true;
	}

	/**
	 * @param array<Queue\Messages\SingleExposeData|Queue\Messages\CompositeExposeData> $states
	 * @param array<string> $identifiers
	 *
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function processStates(
		MetadataDocuments\DevicesModule\Device $bridge,
		MetadataDocuments\DevicesModule\Device $device,
		array $states,
		array $identifiers = [],
	): void
	{
		foreach ($states as $state) {
			if ($state instanceof Queue\Messages\SingleExposeData) {
				$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
				$findChannelQuery->forDevice($device);
				$findChannelQuery->endWithIdentifier(
					sprintf(
						'_%s',
						implode('_', array_merge($identifiers, [$state->getIdentifier()])),
					),
				);
				$findChannelQuery->byType(Entities\Channels\Channel::TYPE);

				$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

				if ($channel === null) {
					$this->logger->debug(
						'Channel for storing device state could not be loaded',
						[
							'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
							'type' => 'store-device-state-message-consumer',
							'connector' => [
								'id' => $device->getConnector()->toString(),
							],
							'bridge' => [
								'id' => $bridge->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'data' => $state->toArray(),
						],
					);

					continue;
				}

				$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
				$findChannelPropertyQuery->forChannel($channel);
				$findChannelPropertyQuery->byIdentifier($state->getIdentifier());

				$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
					$findChannelPropertyQuery,
					MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
				);

				if ($property === null) {
					$this->logger->warning(
						'Channel property for storing device state could not be loaded',
						[
							'source' => MetadataTypes\Sources\Connector::ZIGBEE2MQTT,
							'type' => 'store-device-state-message-consumer',
							'connector' => [
								'id' => $device->getConnector()->toString(),
							],
							'bridge' => [
								'id' => $bridge->getId()->toString(),
							],
							'device' => [
								'id' => $device->getId()->toString(),
							],
							'data' => $state->toArray(),
						],
					);

					continue;
				}

				await($this->channelPropertiesStatesManager->set(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $state->getValue(),
					]),
					MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::ZIGBEE2MQTT),
				));

			} else {
				$this->processStates(
					$bridge,
					$device,
					$state->getStates(),
					array_merge($identifiers, [$state->getIdentifier()]),
				);
			}
		}
	}

}
