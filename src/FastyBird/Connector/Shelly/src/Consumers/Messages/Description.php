<?php declare(strict_types = 1);

/**
 * Description.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           20.07.22
 */

namespace FastyBird\Connector\Shelly\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\Shelly\Consumers\Consumer;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Mappers;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use Psr\Log;
use function assert;
use function sprintf;
use function strval;

/**
 * Device description message consumer
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Description implements Consumer
{

	use Nette\SmartObject;
	use TConsumeDeviceAttribute;
	use TConsumeDeviceProperty;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModels\Devices\Attributes\AttributesRepository $attributesRepository,
		private readonly DevicesModels\Devices\Attributes\AttributesManager $attributesManager,
		private readonly DevicesModels\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly Mappers\Sensor $sensorMapper,
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\DeviceDescription) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\ShellyDevice::class);

		if ($device === null) {
			return true;
		}

		if ($device->getName() === null && $device->getName() !== $entity->getType()) {
			$findDeviceQuery = new DevicesQueries\FindDevices();
			$findDeviceQuery->byId($device->getId());

			$device = $this->devicesRepository->findOneBy(
				$findDeviceQuery,
				Entities\ShellyDevice::class,
			);
			assert($device instanceof Entities\ShellyDevice || $device === null);

			if ($device === null) {
				return true;
			}

			$this->databaseHelper->transaction(
				function () use ($entity, $device): void {
					$this->devicesManager->update($device, Utils\ArrayHash::from([
						'name' => $entity->getType(),
					]));
				},
			);
		}

		$this->setDeviceProperty(
			$device->getId(),
			$entity->getIpAddress(),
			Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
		);
		$this->setDeviceAttribute(
			$device->getId(),
			$entity->getType(),
			Types\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MODEL,
		);

		foreach ($entity->getBlocks() as $block) {
			$channel = $device->findChannel($block->getIdentifier() . '_' . $block->getDescription());

			if ($channel === null) {
				$this->databaseHelper->transaction(function () use ($block, $device): void {
					$this->channelsManager->create(Utils\ArrayHash::from([
						'device' => $device,
						'identifier' => sprintf('%d_%s', $block->getIdentifier(), $block->getDescription()),
						'name' => $block->getDescription(),
					]));
				});
			}

			foreach ($block->getSensors() as $sensor) {
				$channelProperty = $this->sensorMapper->findProperty(
					$entity->getConnector(),
					$entity->getIdentifier(),
					$sensor->getIdentifier(),
				);

				if ($channelProperty === null) {
					$channelProperty = $this->databaseHelper->transaction(
						fn (): DevicesEntities\Channels\Properties\Property => $this->channelsPropertiesManager->create(
							Utils\ArrayHash::from([
								'channel' => $channel,
								'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
								'identifier' => sprintf(
									'%d_%s_%s',
									$sensor->getIdentifier(),
									strval($sensor->getType()->getValue()),
									$sensor->getDescription(),
								),
								'name' => $sensor->getDescription(),
								'unit' => $sensor->getUnit()?->getValue(),
								'dataType' => $sensor->getDataType(),
								'format' => $sensor->getFormat(),
								'invalid' => $sensor->getInvalid(),
								'queryable' => $sensor->isQueryable(),
								'settable' => $sensor->isSettable(),
							]),
						),
					);

					$this->logger->debug(
						'Device sensor was created',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'description-message-consumer',
							'device' => [
								'id' => $device->getPlainId(),
							],
							'channel' => [
								'id' => $channelProperty->getChannel()->getPlainId(),
							],
							'property' => [
								'id' => $channelProperty->getPlainId(),
							],
						],
					);

				} else {
					$channelProperty = $this->databaseHelper->transaction(
						fn (): DevicesEntities\Channels\Properties\Property => $this->channelsPropertiesManager->update(
							$channelProperty,
							Utils\ArrayHash::from([
								'identifier' => sprintf(
									'%d_%s_%s',
									$sensor->getIdentifier(),
									strval($sensor->getType()->getValue()),
									$sensor->getDescription(),
								),
								'name' => $sensor->getDescription(),
								'unit' => $sensor->getUnit()?->getValue(),
								'dataType' => $sensor->getDataType(),
								'format' => $sensor->getFormat(),
								'invalid' => $sensor->getInvalid(),
								'queryable' => $sensor->isQueryable(),
								'settable' => $sensor->isSettable(),
							]),
						),
					);

					$this->logger->debug(
						'Device sensor was updated',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'description-message-consumer',
							'device' => [
								'id' => $device->getPlainId(),
							],
							'channel' => [
								'id' => $channelProperty->getChannel()->getPlainId(),
							],
							'property' => [
								'id' => $channelProperty->getPlainId(),
							],
						],
					);
				}
			}
		}

		$this->logger->debug(
			'Consumed device description message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
				'type' => 'description-message-consumer',
				'device' => [
					'id' => $device->getPlainId(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
