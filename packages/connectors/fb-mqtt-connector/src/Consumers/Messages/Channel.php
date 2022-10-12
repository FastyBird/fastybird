<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 * @since          0.4.0
 *
 * @date           05.02.22
 */

namespace FastyBird\FbMqttConnector\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\DevicesModule\Queries as DevicesModuleQueries;
use FastyBird\FbMqttConnector\Consumers;
use FastyBird\FbMqttConnector\Entities;
use FastyBird\FbMqttConnector\Helpers;
use FastyBird\Metadata;
use FastyBird\Metadata\Types as MetadataTypes;
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
		private readonly DevicesModuleModels\Devices\DevicesRepository $deviceRepository,
		private readonly DevicesModuleModels\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModuleModels\Channels\Properties\PropertiesManager $channelPropertiesManager,
		private readonly DevicesModuleModels\Channels\Controls\ControlsManager $channelControlManager,
		private readonly Helpers\Database $databaseHelper,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws DBAL\Exception
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\ChannelAttribute) {
			return false;
		}

		$device = $this->databaseHelper->query(function () use ($entity): DevicesModuleEntities\Devices\Device|null {
			$findDeviceQuery = new DevicesModuleQueries\FindDevices();
			$findDeviceQuery->byIdentifier($entity->getDevice());

			return $this->deviceRepository->findOneBy($findDeviceQuery);
		});

		if ($device === null) {
			$this->logger->error(
				sprintf('Device "%s" is not registered', $entity->getDevice()),
				[
					'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
					'type' => 'channel-message-consumer',
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
					'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
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
				'source' => Metadata\Constants::CONNECTOR_FB_MQTT_SOURCE,
				'type' => 'channel-message-consumer',
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

	/**
	 * @phpstan-param Utils\ArrayHash<string> $properties
	 */
	private function setChannelProperties(
		DevicesModuleEntities\Channels\Channel $channel,
		Utils\ArrayHash $properties,
	): void
	{
		foreach ($properties as $propertyName) {
			if ($channel->findProperty($propertyName) === null) {
				$this->channelPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesModuleEntities\Channels\Properties\Dynamic::class,
					'channel' => $channel,
					'identifier' => $propertyName,
					'settable' => false,
					'queryable' => false,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UNKNOWN),
				]));
			}
		}

		// Cleanup for unused properties
		foreach ($channel->getProperties() as $property) {
			if (!in_array($property->getIdentifier(), (array) $properties, true)) {
				$this->channelPropertiesManager->delete($property);
			}
		}
	}

	/**
	 * @phpstan-param Utils\ArrayHash<string> $controls
	 */
	private function setChannelControls(
		DevicesModuleEntities\Channels\Channel $channel,
		Utils\ArrayHash $controls,
	): void
	{
		foreach ($controls as $controlName) {
			if ($channel->findControl($controlName) === null) {
				$this->channelControlManager->create(Utils\ArrayHash::from([
					'channel' => $channel,
					'name' => $controlName,
				]));
			}
		}

		// Cleanup for unused control
		foreach ($channel->getControls() as $control) {
			if (!in_array($control->getName(), (array) $controls, true)) {
				$this->channelControlManager->delete($control);
			}
		}
	}

}
