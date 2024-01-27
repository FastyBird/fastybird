<?php declare(strict_types = 1);

/**
 * ChannelAttribute.php
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
use FastyBird\Connector\FbMqtt\Queries;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette;
use Nette\Utils;
use function in_array;
use function is_array;
use function sprintf;

/**
 * Device channel attributes MQTT message consumer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelAttribute implements Queue\Consumer
{

	use Nette\SmartObject;

	public function __construct(
		private readonly FbMqtt\Logger $logger,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelPropertiesManager,
		private readonly DevicesModels\Entities\Channels\Controls\ControlsRepository $channelControlsRepository,
		private readonly DevicesModels\Entities\Channels\Controls\ControlsManager $channelControlsManager,
		private readonly ApplicationHelpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\ChannelAttribute) {
			return false;
		}

		$findDeviceQuery = new Queries\Entities\FindDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getDevice());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\FbMqttDevice::class);

		if ($device === null) {
			$this->logger->warning(
				sprintf('Device "%s" is not registered', $entity->getDevice()),
				[
					'source' => MetadataTypes\ConnectorSource::FB_MQTT,
					'type' => 'channel-attribute-message-consumer',
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

		$findChannelQuery = new Queries\Entities\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier($entity->getChannel());

		$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\FbMqttChannel::class);

		if ($channel === null) {
			$this->logger->warning(
				sprintf('Device channel "%s" is not registered', $entity->getChannel()),
				[
					'source' => MetadataTypes\ConnectorSource::FB_MQTT,
					'type' => 'channel-attribute-message-consumer',
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

		$this->databaseHelper->transaction(function () use ($entity, $channel): void {
			$toUpdate = [];

			if ($entity->getAttribute() === Entities\Messages\Attribute::NAME) {
				$toUpdate['name'] = $entity->getValue();
			}

			if ($entity->getAttribute() === Entities\Messages\Attribute::PROPERTIES && is_array($entity->getValue())) {
				$this->setChannelProperties($channel, Utils\ArrayHash::from($entity->getValue()));
			}

			if ($entity->getAttribute() === Entities\Messages\Attribute::CONTROLS && is_array($entity->getValue())) {
				$this->setChannelControls($channel, Utils\ArrayHash::from($entity->getValue()));
			}

			if ($toUpdate !== []) {
				$this->channelsManager->update($channel, Utils\ArrayHash::from($toUpdate));
			}
		});

		$this->logger->debug(
			'Consumed channel attribute message',
			[
				'source' => MetadataTypes\ConnectorSource::FB_MQTT,
				'type' => 'channel-attribute-message-consumer',
				'connector' => [
					'id' => $entity->getConnector()->toString(),
				],
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'channel' => [
					'id' => $channel->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

	/**
	 * @param Utils\ArrayHash<string> $properties
	 *
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	private function setChannelProperties(
		DevicesEntities\Channels\Channel $channel,
		Utils\ArrayHash $properties,
	): void
	{
		foreach ($properties as $propertyName) {
			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier($propertyName);

			if ($this->channelPropertiesRepository->findOneBy($findChannelPropertyQuery) === null) {
				$this->channelPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'channel' => $channel,
					'identifier' => $propertyName,
					'settable' => false,
					'queryable' => false,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::UNKNOWN),
				]));
			}
		}

		$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		// Cleanup for unused properties
		foreach ($this->channelPropertiesRepository->findAllBy($findChannelPropertiesQuery) as $property) {
			if (!in_array($property->getIdentifier(), (array) $properties, true)) {
				$this->channelPropertiesManager->delete($property);
			}
		}
	}

	/**
	 * @param Utils\ArrayHash<string> $controls
	 *
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	private function setChannelControls(
		DevicesEntities\Channels\Channel $channel,
		Utils\ArrayHash $controls,
	): void
	{
		foreach ($controls as $controlName) {
			$findChannelControlQuery = new DevicesQueries\Entities\FindChannelControls();
			$findChannelControlQuery->forChannel($channel);
			$findChannelControlQuery->byName($controlName);

			if ($this->channelControlsRepository->findOneBy($findChannelControlQuery) === null) {
				$this->channelControlsManager->create(Utils\ArrayHash::from([
					'channel' => $channel,
					'name' => $controlName,
				]));
			}
		}

		$findChannelControlQuery = new DevicesQueries\Entities\FindChannelControls();
		$findChannelControlQuery->forChannel($channel);

		// Cleanup for unused control
		foreach ($this->channelControlsRepository->findAllBy($findChannelControlQuery) as $control) {
			if (!in_array($control->getName(), (array) $controls, true)) {
				$this->channelControlsManager->delete($control);
			}
		}
	}

}
