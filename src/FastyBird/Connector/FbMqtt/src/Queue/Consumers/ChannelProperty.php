<?php declare(strict_types = 1);

/**
 * ChannelProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           05.02.22
 */

namespace FastyBird\Connector\FbMqtt\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use Nette;
use Nette\Utils;
use Throwable;
use function assert;
use function count;
use function React\Async\await;
use function sprintf;

/**
 * Device channel property MQTT message consumer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelProperty implements Queue\Consumer
{

	use Nette\SmartObject;
	use TProperty;

	public function __construct(
		private readonly FbMqtt\Logger $logger,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly ApplicationHelpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\ParseMessage
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 * @throws Throwable
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\ChannelProperty) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getDevice());
		$findDeviceQuery->byType(Entities\FbMqttDevice::TYPE);

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->warning(
				sprintf('Device "%s" is not registered', $entity->getDevice()),
				[
					'source' => MetadataTypes\Sources\Connector::FB_MQTT,
					'type' => 'channel-property-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'identifier' => $entity->getDevice(),
					],
					'channel' => [
						'identifier' => $entity->getChannel(),
					],
				],
			);

			return true;
		}

		$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier($entity->getChannel());
		$findChannelQuery->byType(Entities\FbMqttChannel::TYPE);

		$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			$this->logger->warning(
				sprintf('Device channel "%s" is not registered', $entity->getChannel()),
				[
					'source' => MetadataTypes\Sources\Connector::FB_MQTT,
					'type' => 'channel-property-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'identifier' => $entity->getChannel(),
					],
				],
			);

			return true;
		}

		$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byIdentifier($entity->getProperty());

		$property = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertyQuery);

		if ($property === null) {
			$this->logger->warning(
				sprintf('Property "%s" is not registered', $entity->getProperty()),
				[
					'source' => MetadataTypes\Sources\Connector::FB_MQTT,
					'type' => 'channel-property-message-consumer',
					'connector' => [
						'id' => $entity->getConnector()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
					'channel' => [
						'id' => $channel->getId()->toString(),
					],
					'property' => [
						'identifier' => $entity->getProperty(),
					],
				],
			);

			return true;
		}

		if ($entity->getValue() !== FbMqtt\Constants::VALUE_NOT_SET) {
			if ($property instanceof MetadataDocuments\DevicesModule\ChannelVariableProperty) {
				$this->databaseHelper->transaction(function () use ($entity, $property): void {
					$property = $this->channelsPropertiesRepository->find($property->getId());
					assert($property instanceof DevicesEntities\Channels\Properties\Property);

					$this->channelsPropertiesManager->update(
						$property,
						Utils\ArrayHash::from([
							'value' => $entity->getValue(),
						]),
					);
				});
			} elseif ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
				await($this->channelPropertiesStatesManager->set(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_FIELD => $entity->getValue(),
					]),
					MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::FB_MQTT),
				));
			}
		} else {
			if (count($entity->getAttributes()) > 0) {
				$this->databaseHelper->transaction(function () use ($entity, $property): void {
					$property = $this->channelsPropertiesRepository->find($property->getId());
					assert($property instanceof DevicesEntities\Channels\Properties\Property);

					$toUpdate = $this->handlePropertyConfiguration($entity);

					$this->channelsPropertiesManager->update($property, Utils\ArrayHash::from($toUpdate));
				});
			}
		}

		$this->logger->debug(
			'Consumed channel property message',
			[
				'source' => MetadataTypes\Sources\Connector::FB_MQTT,
				'type' => 'channel-property-message-consumer',
				'connector' => [
					'id' => $entity->getConnector()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'channel' => [
					'id' => $channel->getId()->toString(),
				],
				'property' => [
					'id' => $property->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
