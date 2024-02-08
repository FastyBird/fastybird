<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           25.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Types;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use IPub\DoctrineCrud;
use Nette;
use Nette\Utils;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Properties implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (
			$entity instanceof Entities\Devices\Bridge
			|| $entity instanceof Entities\Devices\SubDevice
		) {
			$this->processDeviceProperties($entity);
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 */
	private function processDeviceProperties(Entities\Zigbee2MqttDevice $device): void
	{
		$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::STATE);

		$stateProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($stateProperty !== null && !$stateProperty instanceof DevicesEntities\Devices\Properties\Dynamic) {
			$this->devicesPropertiesManager->delete($stateProperty);

			$stateProperty = null;
		}

		if ($stateProperty !== null) {
			$this->devicesPropertiesManager->update($stateProperty, Utils\ArrayHash::from([
				'dataType' => MetadataTypes\DataType::ENUM,
				'unit' => null,
				'format' => [
					MetadataTypes\ConnectionState::CONNECTED,
					MetadataTypes\ConnectionState::DISCONNECTED,
					MetadataTypes\ConnectionState::ALERT,
					MetadataTypes\ConnectionState::UNKNOWN,
				],
				'settable' => false,
				'queryable' => false,
			]));
		} else {
			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'device' => $device,
				'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
				'identifier' => Types\DevicePropertyIdentifier::STATE,
				'name' => DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::STATE),
				'dataType' => MetadataTypes\DataType::ENUM,
				'unit' => null,
				'format' => [
					MetadataTypes\ConnectionState::CONNECTED,
					MetadataTypes\ConnectionState::DISCONNECTED,
					MetadataTypes\ConnectionState::ALERT,
					MetadataTypes\ConnectionState::UNKNOWN,
				],
				'settable' => false,
				'queryable' => false,
			]));
		}
	}

}
