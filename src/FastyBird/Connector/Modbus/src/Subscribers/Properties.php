<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           04.08.22
 */

namespace FastyBird\Connector\Modbus\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\Modbus\Entities;
use FastyBird\Connector\Modbus\Types;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Types as DevicesTypes;
use IPub\DoctrineCrud;
use Nette;
use Nette\Utils;
use function sprintf;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Properties implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $propertiesManager,
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
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if ($entity instanceof Entities\Devices\Device) {
			$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
			$findDevicePropertyQuery->forDevice($entity);
			$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::STATE);

			$stateProperty = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

			if ($stateProperty !== null && !$stateProperty instanceof DevicesEntities\Devices\Properties\Dynamic) {
				$this->propertiesManager->delete($stateProperty);

				$stateProperty = null;
			}

			if ($stateProperty !== null) {
				$this->propertiesManager->update($stateProperty, Utils\ArrayHash::from([
					'dataType' => MetadataTypes\DataType::ENUM,
					'unit' => null,
					'format' => [
						DevicesTypes\ConnectionState::CONNECTED->value,
						DevicesTypes\ConnectionState::DISCONNECTED->value,
						DevicesTypes\ConnectionState::LOST->value,
						DevicesTypes\ConnectionState::ALERT->value,
						DevicesTypes\ConnectionState::UNKNOWN->value,
					],
					'settable' => false,
					'queryable' => false,
				]));
			} else {
				$this->propertiesManager->create(Utils\ArrayHash::from([
					'device' => $entity,
					'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
					'identifier' => Types\DevicePropertyIdentifier::STATE,
					'dataType' => MetadataTypes\DataType::ENUM,
					'unit' => null,
					'format' => [
						DevicesTypes\ConnectionState::CONNECTED->value,
						DevicesTypes\ConnectionState::DISCONNECTED->value,
						DevicesTypes\ConnectionState::LOST->value,
						DevicesTypes\ConnectionState::ALERT->value,
						DevicesTypes\ConnectionState::UNKNOWN->value,
					],
					'settable' => false,
					'queryable' => false,
				]));
			}
		} elseif (
			$entity instanceof DevicesEntities\Connectors\Properties\Variable
			&& $entity->getConnector() instanceof Entities\Connectors\Connector
		) {
			if (
				(
					$entity->getIdentifier() === Types\ConnectorPropertyIdentifier::CLIENT_MODE
					&& !Types\ClientMode::isValidValue($entity->getValue())
				) || (
					$entity->getIdentifier() === Types\ConnectorPropertyIdentifier::RTU_BYTE_SIZE
					&& !Types\ByteSize::isValidValue($entity->getValue())
				) || (
					$entity->getIdentifier() === Types\ConnectorPropertyIdentifier::RTU_BAUD_RATE
					&& !Types\BaudRate::isValidValue($entity->getValue())
				) || (
					$entity->getIdentifier() === Types\ConnectorPropertyIdentifier::RTU_PARITY
					&& !Types\Parity::isValidValue($entity->getValue())
				) || (
					$entity->getIdentifier() === Types\ConnectorPropertyIdentifier::RTU_STOP_BITS
					&& !Types\StopBits::isValidValue($entity->getValue())
				)
			) {
				throw new DevicesExceptions\InvalidArgument(sprintf(
					'Provided value for connector property: %s is not in valid range',
					$entity->getIdentifier(),
				));
			}
		} elseif (
			$entity instanceof DevicesEntities\Devices\Properties\Variable
			&& $entity->getDevice() instanceof Entities\Devices\Device
		) {
			if (
				(
					$entity->getIdentifier() === Types\DevicePropertyIdentifier::BYTE_ORDER
					&& !Types\ByteOrder::isValidValue($entity->getValue())
				)
			) {
				throw new DevicesExceptions\InvalidArgument(sprintf(
					'Provided value for device property: %s is not in valid range',
					$entity->getIdentifier(),
				));
			}
		}
	}

}
