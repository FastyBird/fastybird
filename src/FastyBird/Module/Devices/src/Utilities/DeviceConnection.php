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
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Types;
use Nette;
use Nette\Utils;
use function assert;
use function strval;

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
		Entities\Devices\Device|Documents\Devices\Device $device,
		Types\ConnectionState $state,
	): bool
	{
		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertyQuery->byDeviceId($device->getId());
		$findDevicePropertyQuery->byIdentifier(MetadataTypes\DevicePropertyIdentifier::STATE);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			Documents\Devices\Properties\Dynamic::class,
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
						'dataType' => MetadataTypes\DataType::ENUM,
						'unit' => null,
						'format' => [
							Types\ConnectionState::CONNECTED->value,
							Types\ConnectionState::DISCONNECTED->value,
							Types\ConnectionState::RUNNING->value,
							Types\ConnectionState::SLEEPING->value,
							Types\ConnectionState::STOPPED->value,
							Types\ConnectionState::LOST->value,
							Types\ConnectionState::ALERT->value,
							Types\ConnectionState::UNKNOWN->value,
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
		assert($property instanceof Documents\Devices\Properties\Dynamic);

		$this->propertiesStatesManager->set(
			$property,
			Utils\ArrayHash::from([
				States\Property::ACTUAL_VALUE_FIELD => $state->value,
				States\Property::EXPECTED_VALUE_FIELD => null,
			]),
			MetadataTypes\Sources\Module::get(MetadataTypes\Sources\Module::DEVICES),
		);

		return false;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Mapping
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function getState(
		Entities\Devices\Device|Documents\Devices\Device $device,
	): Types\ConnectionState
	{
		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertyQuery->byDeviceId($device->getId());
		$findDevicePropertyQuery->byIdentifier(MetadataTypes\DevicePropertyIdentifier::STATE);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			Documents\Devices\Properties\Dynamic::class,
		);

		if ($property instanceof Documents\Devices\Properties\Dynamic) {
			$state = $this->propertiesStatesManager->readState($property);

			if (
				$state?->getRead()->getActualValue() !== null
				&& Types\ConnectionState::tryFrom(
					strval(MetadataUtilities\Value::flattenValue($state->getRead()->getActualValue())),
				) !== null
			) {
				return Types\ConnectionState::tryFrom(
					strval(MetadataUtilities\Value::flattenValue($state->getRead()->getActualValue())),
				);
			}
		}

		return Types\ConnectionState::UNKNOWN;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Mapping
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function getLostAt(
		Entities\Devices\Device|Documents\Devices\Device $device,
	): DateTimeInterface|null
	{
		$findDevicePropertyQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertyQuery->byDeviceId($device->getId());
		$findDevicePropertyQuery->byIdentifier(MetadataTypes\DevicePropertyIdentifier::STATE);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			Documents\Devices\Properties\Dynamic::class,
		);

		if ($property instanceof Documents\Devices\Properties\Dynamic) {
			$state = $this->propertiesStatesManager->readState($property);

			if (
				$state?->getRead()->getActualValue() !== null
				&& Types\ConnectionState::tryFrom(
					strval(MetadataUtilities\Value::flattenValue($state->getRead()->getActualValue())),
				) !== null
				&& $state->getRead()->getActualValue() === Types\ConnectionState::LOST->value
			) {
				return $state->getUpdatedAt();
			}
		}

		return null;
	}

}
