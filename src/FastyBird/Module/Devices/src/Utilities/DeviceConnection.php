<?php declare(strict_types = 1);

/**
 * DeviceConnection.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           19.07.22
 */

namespace FastyBird\Module\Devices\Utilities;

use DateTimeInterface;
use Doctrine\DBAL;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
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

	/**
	 * @param Models\Configuration\Devices\Properties\Repository<MetadataDocuments\DevicesModule\DeviceDynamicProperty> $devicesPropertiesConfigurationRepository
	 */
	public function __construct(
		private readonly Models\Entities\Devices\DevicesRepository $devicesEntitiesRepository,
		private readonly Models\Entities\Devices\Properties\PropertiesManager $devicesPropertiesEntitiesManager,
		private readonly Models\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicePropertiesStates $propertiesStates,
		private readonly Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function setState(
		Entities\Devices\Device|MetadataDocuments\DevicesModule\Device $device,
		MetadataTypes\ConnectionState $state,
	): bool
	{
		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertyQuery->byDeviceId($device->getId());
		$findDevicePropertyQuery->byIdentifier(MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATE);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
		);

		if ($property === null) {
			$property = $this->databaseHelper->transaction(
				function () use ($device): Entities\Devices\Properties\Dynamic {
					if (!$device instanceof Entities\Devices\Device) {
						$findDeviceQuery = new Queries\Entities\FindDevices();
						$findDeviceQuery->byId($device->getId());

						$device = $this->devicesEntitiesRepository->findOneBy($findDeviceQuery);
						assert($device instanceof Entities\Devices\Device);
					}

					$property = $this->devicesPropertiesEntitiesManager->create(Utils\ArrayHash::from([
						'device' => $device,
						'entity' => Entities\Devices\Properties\Dynamic::class,
						'identifier' => MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_STATE,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
						'unit' => null,
						'format' => [
							MetadataTypes\ConnectionState::STATE_CONNECTED,
							MetadataTypes\ConnectionState::STATE_DISCONNECTED,
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
					assert($property instanceof Entities\Devices\Properties\Dynamic);

					return $property;
				},
			);
		}

		$this->propertiesStates->writeValue(
			$property,
			Utils\ArrayHash::from([
				States\Property::ACTUAL_VALUE_FIELD => $state->getValue(),
				States\Property::EXPECTED_VALUE_FIELD => null,
				States\Property::PENDING_FIELD => false,
				States\Property::VALID_FIELD => true,
			]),
		);

		return false;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getState(
		Entities\Devices\Device|MetadataDocuments\DevicesModule\Device $device,
	): MetadataTypes\ConnectionState
	{
		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertyQuery->byDeviceId($device->getId());
		$findDevicePropertyQuery->byIdentifier(MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATE);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
		);

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
			$state = $this->propertiesStates->readValue($property);

			if (
				$state?->getActualValue() !== null
				&& MetadataTypes\ConnectionState::isValidValue($state->getActualValue())
			) {
				return MetadataTypes\ConnectionState::get($state->getActualValue());
			}
		}

		return MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_UNKNOWN);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getLostAt(
		Entities\Devices\Device|MetadataDocuments\DevicesModule\Device $device,
	): DateTimeInterface|null
	{
		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertyQuery->byDeviceId($device->getId());
		$findDevicePropertyQuery->byIdentifier(MetadataTypes\DevicePropertyIdentifier::IDENTIFIER_STATE);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
		);

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
			$state = $this->propertiesStates->readValue($property);

			if (
				$state?->getActualValue() !== null
				&& MetadataTypes\ConnectionState::isValidValue($state->getActualValue())
				&& $state->getActualValue() === MetadataTypes\ConnectionState::STATE_LOST
			) {
				return $state->getUpdatedAt();
			}
		}

		return null;
	}

}
