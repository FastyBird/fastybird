<?php declare(strict_types = 1);

/**
 * StoreDeviceState.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           18.07.23
 */

namespace FastyBird\Connector\NsPanel\Queue\Consumers;

use Doctrine\DBAL;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Queue\Messages\CapabilityState;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
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
 * Store device state message consumer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class StoreDeviceState implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly NsPanel\Logger $logger,
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
	 * @throws DevicesExceptions\InvalidState
	 */
	public function consume(Queue\Messages\Message $message): bool
	{
		if (!$message instanceof Queue\Messages\StoreDeviceState) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
		$findDeviceQuery->byConnectorId($message->getConnector());
		$findDeviceQuery->byIdentifier($message->getIdentifier());

		$device = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

		if ($device === null) {
			$this->logger->error(
				'Device could not be loaded',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL,
					'type' => 'store-device-state-message-consumer',
					'connector' => [
						'id' => $message->getConnector()->toString(),
					],
					'device' => [
						'identifier' => $message->getIdentifier(),
					],
					'data' => $message->toArray(),
				],
			);

			return true;
		}

		if ($device->getType() === Entities\Devices\ThirdPartyDevice::TYPE) {
			$this->processThirdPartyDevice($device, $message->getState());
		} elseif ($device->getType() === Entities\Devices\SubDevice::TYPE) {
			$this->processSubDevice($device, $message->getState());
		}

		$this->logger->debug(
			'Consumed store device state message',
			[
				'source' => MetadataTypes\Sources\Connector::NS_PANEL,
				'type' => 'store-device-state-message-consumer',
				'connector' => [
					'id' => $message->getConnector()->toString(),
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
	 * @param array<CapabilityState> $state
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	private function processSubDevice(
		MetadataDocuments\DevicesModule\Device $device,
		array $state,
	): void
	{
		foreach ($state as $item) {
			$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier(
				Helpers\Name::convertCapabilityToChannel($item->getCapability(), $item->getIdentifier()),
			);

			$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

			if ($channel === null) {
				continue;
			}

			$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
			$findChannelPropertiesQuery->forChannel($channel);
			$findChannelPropertiesQuery->byIdentifier(Helpers\Name::convertProtocolToProperty($item->getProtocol()));

			$property = $this->channelsPropertiesConfigurationRepository->findOneBy(
				$findChannelPropertiesQuery,
				MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
			);

			if ($property === null) {
				continue;
			}

			await($this->channelPropertiesStatesManager->set(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_FIELD => $item->getValue(),
				]),
				MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::NS_PANEL),
			));
		}
	}

	/**
	 * @param array<CapabilityState> $state
	 *
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 */
	private function processThirdPartyDevice(
		MetadataDocuments\DevicesModule\Device $device,
		array $state,
	): void
	{
		foreach ($state as $item) {
			$findChannelQuery = new DevicesQueries\Configuration\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier(
				Helpers\Name::convertCapabilityToChannel($item->getCapability(), $item->getIdentifier()),
			);

			$channel = $this->channelsConfigurationRepository->findOneBy($findChannelQuery);

			if ($channel === null) {
				continue;
			}

			$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelProperties();
			$findChannelPropertiesQuery->forChannel($channel);
			$findChannelPropertiesQuery->byIdentifier(Helpers\Name::convertProtocolToProperty($item->getProtocol()));

			$property = $this->channelsPropertiesConfigurationRepository->findOneBy($findChannelPropertiesQuery);

			if ($property === null) {
				continue;
			}

			$this->writeThirdPartyProperty(
				$device,
				$channel,
				$property,
				MetadataUtilities\Value::flattenValue($item->getValue()),
			);
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 */
	private function writeThirdPartyProperty(
		MetadataDocuments\DevicesModule\Device $device,
		MetadataDocuments\DevicesModule\Channel $channel,
		MetadataDocuments\DevicesModule\ChannelProperty $property,
		float|int|string|bool|null $value,
	): void
	{
		if ($property instanceof MetadataDocuments\DevicesModule\ChannelVariableProperty) {
			$this->databaseHelper->transaction(
				function () use ($property, $value): void {
					$property = $this->channelsPropertiesRepository->find(
						$property->getId(),
						DevicesEntities\Channels\Properties\Variable::class,
					);
					assert($property instanceof DevicesEntities\Channels\Properties\Variable);

					$this->channelsPropertiesManager->update(
						$property,
						Utils\ArrayHash::from([
							'value' => $value,
						]),
					);
				},
			);

		} elseif ($property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty) {
			await($this->channelPropertiesStatesManager->set(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_FIELD => $value,
				]),
				MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::NS_PANEL),
			));

		} elseif ($property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty) {
			await($this->channelPropertiesStatesManager->write(
				$property,
				Utils\ArrayHash::from([
					DevicesStates\Property::EXPECTED_VALUE_FIELD => $value,
				]),
				MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::NS_PANEL),
			));
		}
	}

}
