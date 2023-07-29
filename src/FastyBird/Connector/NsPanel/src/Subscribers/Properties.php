<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           12.07.23
 */

namespace FastyBird\Connector\NsPanel\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use IPub\DoctrineCrud;
use Nette;
use Nette\Utils;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Properties implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $propertiesManager,
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 * @throws Exceptions\InvalidState
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (
			$entity instanceof Entities\Devices\Gateway
			|| $entity instanceof Entities\Devices\SubDevice
			|| $entity instanceof Entities\Devices\ThirdPartyDevice
		) {
			$this->processDeviceProperties($entity);

			return;
		}

		if ($entity instanceof Entities\NsPanelChannel && $entity->getDevice() instanceof Entities\Devices\SubDevice) {
			$this->processSubDeviceChannelProperties($entity);

			return;
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 */
	private function processDeviceProperties(Entities\NsPanelDevice $device): void
	{
		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IDENTIFIER_STATE);

		$stateProperty = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($stateProperty !== null && !$stateProperty instanceof DevicesEntities\Devices\Properties\Dynamic) {
			$this->propertiesManager->delete($stateProperty);

			$stateProperty = null;
		}

		$enumValues = $device instanceof Entities\Devices\ThirdPartyDevice ? [
			MetadataTypes\ConnectionState::STATE_RUNNING,
			MetadataTypes\ConnectionState::STATE_STOPPED,
			MetadataTypes\ConnectionState::STATE_UNKNOWN,
		] : [
			MetadataTypes\ConnectionState::STATE_CONNECTED,
			MetadataTypes\ConnectionState::STATE_DISCONNECTED,
			MetadataTypes\ConnectionState::STATE_STOPPED,
			MetadataTypes\ConnectionState::STATE_LOST,
			MetadataTypes\ConnectionState::STATE_UNKNOWN,
		];

		if ($stateProperty !== null) {
			$this->propertiesManager->update($stateProperty, Utils\ArrayHash::from([
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'unit' => null,
				'format' => $enumValues,
				'settable' => false,
				'queryable' => false,
			]));
		} else {
			$this->propertiesManager->create(Utils\ArrayHash::from([
				'device' => $device,
				'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_STATE,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_STATE),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'unit' => null,
				'format' => $enumValues,
				'settable' => false,
				'queryable' => false,
			]));
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function processSubDeviceChannelProperties(Entities\NsPanelChannel $channel): void
	{
		if ($channel->getCapability()->equalsValue(Types\Capability::POWER)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::TOGGLE)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::BRIGHTNESS)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::COLOR_TEMPERATURE)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::COLOR_RGB)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::PERCENTAGE)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::MOTOR_CONTROL)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::MOTOR_REVERSE)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::MOTOR_CALIBRATION)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::STARTUP)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::CAMERA_STREAM)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::DETECT)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::HUMIDITY)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::TEMPERATURE)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::BATTERY)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::PRESS)) {

		}

		if ($channel->getCapability()->equalsValue(Types\Capability::RSSI)) {

		}
	}

}
