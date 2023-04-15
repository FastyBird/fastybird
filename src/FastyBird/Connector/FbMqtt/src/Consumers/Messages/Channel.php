<?php declare(strict_types = 1);

/**
 * Channel.php
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

namespace FastyBird\Connector\FbMqtt\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\FbMqtt\Consumers;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette;
use Nette\Utils;
use Psr\Log;
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
final class Channel implements Consumers\Consumer
{

	use Nette\SmartObject;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelPropertiesManager,
		private readonly DevicesModels\Channels\Controls\ControlsRepository $channelControlsRepository,
		private readonly DevicesModels\Channels\Controls\ControlsManager $channelControlsManager,
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
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\ChannelAttribute) {
			return false;
		}

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byIdentifier($entity->getDevice());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\FbMqttDevice::class);

		if ($device === null) {
			$this->logger->error(
				sprintf('Device "%s" is not registered', $entity->getDevice()),
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
					'type' => 'channel-message-consumer',
					'device' => [
						'identifier' => $entity->getDevice(),
					],
				],
			);

			return true;
		}

		$findChannelQuery = new DevicesQueries\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier($entity->getChannel());

		$channel = $this->channelsRepository->findOneBy($findChannelQuery);

		if ($channel === null) {
			$this->logger->error(
				sprintf('Device channel "%s" is not registered', $entity->getChannel()),
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
					'type' => 'channel-message-consumer',
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
			'Consumed channel message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
				'type' => 'channel-message-consumer',
				'device' => [
					'id' => $device->getPlainId(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

	/**
	 * @phpstan-param Utils\ArrayHash<string> $properties
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	private function setChannelProperties(
		DevicesEntities\Channels\Channel $channel,
		Utils\ArrayHash $properties,
	): void
	{
		foreach ($properties as $propertyName) {
			$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier($propertyName);

			if ($this->channelPropertiesRepository->findOneBy($findChannelPropertyQuery) === null) {
				$this->channelPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'channel' => $channel,
					'identifier' => $propertyName,
					'settable' => false,
					'queryable' => false,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UNKNOWN),
				]));
			}
		}

		$findChannelPropertiesQuery = new DevicesQueries\FindChannelProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		// Cleanup for unused properties
		foreach ($this->channelPropertiesRepository->findAllBy($findChannelPropertiesQuery) as $property) {
			if (!in_array($property->getIdentifier(), (array) $properties, true)) {
				$this->channelPropertiesManager->delete($property);
			}
		}
	}

	/**
	 * @phpstan-param Utils\ArrayHash<string> $controls
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	private function setChannelControls(
		DevicesEntities\Channels\Channel $channel,
		Utils\ArrayHash $controls,
	): void
	{
		foreach ($controls as $controlName) {
			$findChannelControlQuery = new DevicesQueries\FindChannelControls();
			$findChannelControlQuery->forChannel($channel);
			$findChannelControlQuery->byName($controlName);

			if ($this->channelControlsRepository->findOneBy($findChannelControlQuery) === null) {
				$this->channelControlsManager->create(Utils\ArrayHash::from([
					'channel' => $channel,
					'name' => $controlName,
				]));
			}
		}

		$findChannelControlQuery = new DevicesQueries\FindChannelControls();
		$findChannelControlQuery->forChannel($channel);

		// Cleanup for unused control
		foreach ($this->channelControlsRepository->findAllBy($findChannelControlQuery) as $control) {
			if (!in_array($control->getName(), (array) $controls, true)) {
				$this->channelControlsManager->delete($control);
			}
		}
	}

}
