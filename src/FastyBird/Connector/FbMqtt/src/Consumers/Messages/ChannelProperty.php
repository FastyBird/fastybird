<?php declare(strict_types = 1);

/**
 * ChannelProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 * @since          0.4.0
 *
 * @date           05.02.22
 */

namespace FastyBird\Connector\FbMqtt\Consumers\Messages;

use Doctrine\DBAL;
use Exception;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Consumers;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use Psr\Log;
use function count;
use function sprintf;

/**
 * Device channel property MQTT message consumer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelProperty implements Consumers\Consumer
{

	use Nette\SmartObject;
	use TProperty;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModels\Devices\DevicesRepository $deviceRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly DevicesUtilities\Database $databaseHelper,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Exception
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\ChannelProperty) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getDevice());

		$device = $this->deviceRepository->findOneBy($findDeviceQuery, Entities\FbMqttDevice::class);

		if ($device === null) {
			$this->logger->error(
				sprintf('Device "%s" is not registered', $entity->getDevice()),
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
					'type' => 'channel-property-message-consumer',
					'device' => [
						'identifier' => $entity->getDevice(),
					],
				],
			);

			return true;
		}

		$channel = $device->findChannel($entity->getChannel());

		if ($channel === null) {
			$this->logger->error(
				sprintf('Device channel "%s" is not registered', $entity->getChannel()),
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
					'type' => 'channel-property-message-consumer',
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

		$property = $channel->findProperty($entity->getProperty());

		if ($property === null) {
			$this->logger->error(
				sprintf('Property "%s" is not registered', $entity->getProperty()),
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
					'type' => 'channel-property-message-consumer',
					'device' => [
						'identifier' => $entity->getDevice(),
					],
					'channel' => [
						'identifier' => $entity->getChannel(),
					],
					'property' => [
						'identifier' => $entity->getProperty(),
					],
				],
			);

			return true;
		}

		if ($entity->getValue() !== FbMqtt\Constants::VALUE_NOT_SET) {
			if ($property instanceof DevicesEntities\Channels\Properties\Variable) {
				$this->databaseHelper->transaction(
					fn (): DevicesEntities\Channels\Properties\Property => $this->propertiesManager->update(
						$property,
						Utils\ArrayHash::from([
							'value' => $entity->getValue(),
						]),
					),
				);
			} elseif ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
				$actualValue = DevicesUtilities\ValueHelper::flattenValue(
					DevicesUtilities\ValueHelper::normalizeValue(
						$property->getDataType(),
						$entity->getValue(),
						$property->getFormat(),
						$property->getInvalid(),
					),
				);

				$this->channelPropertiesStates->setValue(
					$property,
					Utils\ArrayHash::from([
						DevicesStates\Property::ACTUAL_VALUE_KEY => $actualValue,
						DevicesStates\Property::VALID_KEY => true,
					]),
				);
			}
		} else {
			if (count($entity->getAttributes()) > 0) {
				$this->databaseHelper->transaction(function () use ($entity, $property): void {
					$toUpdate = $this->handlePropertyConfiguration($entity);

					$this->propertiesManager->update($property, Utils\ArrayHash::from($toUpdate));
				});
			}
		}

		$this->logger->debug(
			'Consumed channel property message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
				'type' => 'channel-property-message-consumer',
				'device' => [
					'identifier' => $entity->getDevice(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
