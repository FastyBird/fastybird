<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           17.10.23
 */

namespace FastyBird\Connector\Virtual\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Helpers;
use FastyBird\Connector\Virtual\Types;
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
 * @package        FastyBird:VirtualConnector!
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
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\VirtualDevice) {
			return;
		}

		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($entity);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::STATE);

		$stateProperty = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($stateProperty !== null && !$stateProperty instanceof DevicesEntities\Devices\Properties\Dynamic) {
			$this->propertiesManager->delete($stateProperty);

			$stateProperty = null;
		}

		if ($stateProperty !== null) {
			$this->propertiesManager->update($stateProperty, Utils\ArrayHash::from([
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'unit' => null,
				'format' => [
					MetadataTypes\ConnectionState::STATE_CONNECTED,
					MetadataTypes\ConnectionState::STATE_DISCONNECTED,
					MetadataTypes\ConnectionState::STATE_ALERT,
					MetadataTypes\ConnectionState::STATE_UNKNOWN,
				],
				'settable' => false,
				'queryable' => false,
			]));
		} else {
			$this->propertiesManager->create(Utils\ArrayHash::from([
				'device' => $entity,
				'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
				'identifier' => Types\DevicePropertyIdentifier::STATE,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'unit' => null,
				'format' => [
					MetadataTypes\ConnectionState::STATE_CONNECTED,
					MetadataTypes\ConnectionState::STATE_DISCONNECTED,
					MetadataTypes\ConnectionState::STATE_ALERT,
					MetadataTypes\ConnectionState::STATE_UNKNOWN,
				],
				'settable' => false,
				'queryable' => false,
			]));
		}

		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($entity);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::MANUFACTURER);

		$manufacturerProperty = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

		if (
			$manufacturerProperty !== null
			&& !$manufacturerProperty instanceof DevicesEntities\Devices\Properties\Variable
		) {
			$this->propertiesManager->delete($manufacturerProperty);

			$manufacturerProperty = null;
		}

		if ($manufacturerProperty === null) {
			$this->propertiesManager->create(Utils\ArrayHash::from([
				'connector' => $entity,
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::MANUFACTURER,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'unit' => null,
				'format' => null,
				'settable' => false,
				'queryable' => false,
				'value' => Virtual\Constants::MANUFACTURER,
			]));
		}

		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($entity);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::MAC_ADDRESS);

		$macAddressProperty = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

		if (
			$macAddressProperty !== null
			&& !$macAddressProperty instanceof DevicesEntities\Devices\Properties\Variable
		) {
			$this->propertiesManager->delete($macAddressProperty);

			$macAddressProperty = null;
		}

		if ($macAddressProperty === null) {
			$this->propertiesManager->create(Utils\ArrayHash::from([
				'connector' => $entity,
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::MAC_ADDRESS,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'unit' => null,
				'format' => null,
				'settable' => false,
				'queryable' => false,
				'value' => Helpers\Drivers::generateMacAddress(),
			]));
		}
	}

}
