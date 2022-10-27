<?php declare(strict_types = 1);

/**
 * DeviceConnection.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 * @since          0.73.0
 *
 * @date           19.07.22
 */

namespace FastyBird\Module\Devices\Utilities;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Device connection states manager
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceConnection
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Devices\DevicesRepository $devicesRepository,
		private readonly Models\DataStorage\DevicePropertiesRepository $repository,
		private readonly Models\Devices\Properties\PropertiesManager $manager,
		private readonly DevicePropertiesStates $propertiesStates,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function setState(
		Entities\Devices\Device|MetadataEntities\DevicesModule\Device $device,
		MetadataTypes\ConnectionState $state,
	): bool
	{
		$property = $this->repository->findByIdentifier(
			$device->getId(),
			MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATE,
		);

		if ($property === null) {
			if (!$device instanceof Entities\Devices\Device) {
				$findDeviceQuery = new Queries\FindDevices();
				$findDeviceQuery->byId($device->getId());

				$device = $this->devicesRepository->findOneBy($findDeviceQuery);

				if ($device === null) {
					throw new Exceptions\InvalidState('Connector could not be loaded');
				}
			}

			$property = $this->manager->create(Utils\ArrayHash::from([
				'device' => $device,
				'entity' => Entities\Devices\Properties\Dynamic::class,
				'identifier' => MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_STATE,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'unit' => null,
				'format' => [
					MetadataTypes\ConnectionState::STATE_CONNECTED,
					MetadataTypes\ConnectionState::STATE_DISCONNECTED,
					MetadataTypes\ConnectionState::STATE_INIT,
					MetadataTypes\ConnectionState::STATE_READY,
					MetadataTypes\ConnectionState::STATE_RUNNING,
					MetadataTypes\ConnectionState::STATE_SLEEPING,
					MetadataTypes\ConnectionState::STATE_STOPPED,
					MetadataTypes\ConnectionState::STATE_LOST,
					MetadataTypes\ConnectionState::STATE_ALERT,
					MetadataTypes\ConnectionState::STATE_UNKNOWN,
				],
				'settable' => false,
				'queryable' => false,
			]));
		}

		assert(
			$property instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty
			|| $property instanceof Entities\Devices\Properties\Dynamic,
		);

		$this->propertiesStates->setValue(
			$property,
			Utils\ArrayHash::from([
				'actualValue' => $state->getValue(),
				'expectedValue' => null,
				'pending' => false,
				'valid' => true,
			]),
		);

		return false;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getState(
		Entities\Devices\Device|MetadataEntities\DevicesModule\Device $device,
	): MetadataTypes\ConnectionState
	{
		$stateProperty = $this->repository->findByIdentifier(
			$device->getId(),
			MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATE,
		);

		if (
			$stateProperty instanceof MetadataEntities\DevicesModule\DeviceDynamicProperty
			&& $stateProperty->getActualValue() !== null
			&& MetadataTypes\ConnectionState::isValidValue($stateProperty->getActualValue())
		) {
			return MetadataTypes\ConnectionState::get($stateProperty->getActualValue());
		}

		return MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_UNKNOWN);
	}

}
