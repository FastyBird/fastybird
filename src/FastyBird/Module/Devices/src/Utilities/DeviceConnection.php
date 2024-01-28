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
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
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

	public function __construct(
		private readonly Models\Entities\Devices\DevicesRepository $devicesEntitiesRepository,
		private readonly Models\Entities\Devices\Properties\PropertiesManager $devicesPropertiesEntitiesManager,
		private readonly Models\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly Models\States\DevicePropertiesManager $propertiesStatesManager,
		private readonly ApplicationHelpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function setState(
		Entities\Devices\Device|MetadataDocuments\DevicesModule\Device $device,
		MetadataTypes\ConnectionState $state,
	): bool
	{
		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertyQuery->byDeviceId($device->getId());
		$findDevicePropertyQuery->byIdentifier(MetadataTypes\DevicePropertyIdentifier::STATE);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
		);

		if ($property === null) {
			$property = $this->databaseHelper->transaction(
				function () use ($device): Entities\Devices\Properties\Dynamic {
					if (!$device instanceof Entities\Devices\Device) {
						$device = $this->devicesEntitiesRepository->find($device->getId());
						assert($device instanceof Entities\Devices\Device);
					}

					$property = $this->devicesPropertiesEntitiesManager->create(Utils\ArrayHash::from([
						'device' => $device,
						'entity' => Entities\Devices\Properties\Dynamic::class,
						'identifier' => MetadataTypes\ConnectorPropertyIdentifier::STATE,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::ENUM),
						'unit' => null,
						'format' => [
							MetadataTypes\ConnectionState::CONNECTED,
							MetadataTypes\ConnectionState::DISCONNECTED,
							MetadataTypes\ConnectionState::RUNNING,
							MetadataTypes\ConnectionState::SLEEPING,
							MetadataTypes\ConnectionState::STOPPED,
							MetadataTypes\ConnectionState::LOST,
							MetadataTypes\ConnectionState::ALERT,
							MetadataTypes\ConnectionState::UNKNOWN,
						],
						'settable' => false,
						'queryable' => false,
					]));
					assert($property instanceof Entities\Devices\Properties\Dynamic);

					return $property;
				},
			);
		}

		$property = $this->devicesPropertiesConfigurationRepository->find($property->getId());
		assert($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty);

		$this->propertiesStatesManager->set(
			$property,
			Utils\ArrayHash::from([
				States\Property::ACTUAL_VALUE_FIELD => $state->getValue(),
				States\Property::EXPECTED_VALUE_FIELD => null,
			]),
			MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
		);

		return false;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function getState(
		Entities\Devices\Device|MetadataDocuments\DevicesModule\Device $device,
	): MetadataTypes\ConnectionState
	{
		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertyQuery->byDeviceId($device->getId());
		$findDevicePropertyQuery->byIdentifier(MetadataTypes\DevicePropertyIdentifier::STATE);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
		);

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
			$state = $this->propertiesStatesManager->readState($property);

			if (
				$state?->getRead()->getActualValue() !== null
				&& MetadataTypes\ConnectionState::isValidValue($state->getRead()->getActualValue())
			) {
				return MetadataTypes\ConnectionState::get($state->getRead()->getActualValue());
			}
		}

		return MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::UNKNOWN);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function getLostAt(
		Entities\Devices\Device|MetadataDocuments\DevicesModule\Device $device,
	): DateTimeInterface|null
	{
		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertyQuery->byDeviceId($device->getId());
		$findDevicePropertyQuery->byIdentifier(MetadataTypes\DevicePropertyIdentifier::STATE);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
		);

		if ($property instanceof MetadataDocuments\DevicesModule\DeviceDynamicProperty) {
			$state = $this->propertiesStatesManager->readState($property);

			if (
				$state?->getRead()->getActualValue() !== null
				&& MetadataTypes\ConnectionState::isValidValue($state->getRead()->getActualValue())
				&& $state->getRead()->getActualValue() === MetadataTypes\ConnectionState::LOST
			) {
				return $state->getUpdatedAt();
			}
		}

		return null;
	}

}
