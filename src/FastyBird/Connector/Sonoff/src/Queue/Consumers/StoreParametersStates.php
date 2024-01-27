<?php declare(strict_types = 1);

/**
 * StoreParametersStates.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           27.05.23
 */

namespace FastyBird\Connector\Sonoff\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\Sonoff;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Queue\Consumer;
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
use function assert;
use function React\Async\await;

/**
 * Device state message consumer
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreParametersStates implements Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Sonoff\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\StoreParametersStates) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->startWithIdentifier($entity->getIdentifier());
		$findDeviceQuery->byType(Entities\SonoffDevice::TYPE);

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			return true;
		}

		foreach ($entity->getParameters() as $parameter) {
			if ($parameter instanceof Entities\Messages\States\DeviceParameterState) {
				$findDevicePropertyQuery = new DevicesQueries\Configuration\FindDeviceProperties();
				$findDevicePropertyQuery->forDevice($device);
				$findDevicePropertyQuery->byIdentifier($parameter->getName());

				$property = $this->devicesPropertiesConfigurationRepository->findOneBy($findDevicePropertyQuery);

				if ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
					await($this->devicePropertiesStatesManager->set(
						$property,
						Utils\ArrayHash::from([
							DevicesStates\Property::ACTUAL_VALUE_FIELD => $parameter->getValue(),
						]),
						MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SONOFF),
					));
				} elseif ($property instanceof MetadataDocuments\DevicesModule\DeviceVariableProperty) {
					$this->databaseHelper->transaction(
						function () use ($property, $parameter): void {
							$property = $this->devicesPropertiesRepository->find(
								$property->getId(),
								DevicesEntities\Devices\Properties\Variable::class,
							);
							assert($property instanceof DevicesEntities\Devices\Properties\Variable);

							$this->devicesPropertiesManager->update(
								$property,
								Utils\ArrayHash::from([
									'value' => $parameter->getValue(),
								]),
							);
						},
					);
				}
			} elseif ($parameter instanceof Entities\Messages\States\ChannelParameterState) {
				$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
				$findChannelQuery->forDevice($device);
				$findChannelQuery->byIdentifier($parameter->getGroup());
				$findChannelQuery->byType(Entities\SonoffChannel::TYPE);

				$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

				if ($channel !== null) {
					$findChannelPropertyQuery = new DevicesQueries\Configuration\FindChannelProperties();
					$findChannelPropertyQuery->forChannel($channel);
					$findChannelPropertyQuery->byIdentifier($parameter->getName());

					$property = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertyQuery);

					if ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
						await($this->channelPropertiesStatesManager->set(
							$property,
							Utils\ArrayHash::from([
								DevicesStates\Property::ACTUAL_VALUE_FIELD => $parameter->getValue(),
							]),
							MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SONOFF),
						));
					} elseif ($property instanceof MetadataDocuments\DevicesModule\ChannelVariableProperty) {
						$this->databaseHelper->transaction(
							function () use ($property, $parameter): void {
								$property = $this->channelsPropertiesRepository->find(
									$property->getId(),
									DevicesEntities\Channels\Properties\Variable::class,
								);
								assert($property instanceof DevicesEntities\Channels\Properties\Variable);

								$this->channelsPropertiesManager->update(
									$property,
									Utils\ArrayHash::from([
										'value' => $parameter->getValue(),
									]),
								);
							},
						);
					}
				}
			}
		}

		$this->logger->debug(
			'Consumed store device state message',
			[
				'source' => MetadataTypes\ConnectorSource::SONOFF,
				'type' => 'status-parameters-states-message-consumer',
				'connector' => [
					'id' => $entity->getConnector()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
