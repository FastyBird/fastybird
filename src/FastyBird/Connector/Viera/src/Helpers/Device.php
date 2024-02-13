<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           21.11.23
 */

namespace FastyBird\Connector\Viera\Helpers;

use FastyBird\Connector\Viera\Documents;
use FastyBird\Connector\Viera\Entities;
use FastyBird\Connector\Viera\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use function assert;
use function floatval;
use function is_bool;
use function is_int;
use function is_numeric;
use function is_string;

/**
 * Device helper
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device
{

	public function __construct(
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getIpAddress(Documents\Devices\Device $device): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IP_ADDRESS);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getPort(Documents\Devices\Device $device): int
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::PORT);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return Entities\Devices\Device::DEFAULT_PORT;
		}

		$value = $property->getValue();
		assert(is_int($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function isEncrypted(Documents\Devices\Device $device): bool
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::ENCRYPTED);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return false;
		}

		$value = $property->getValue();
		assert(is_bool($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getAppId(Documents\Devices\Device $device): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::APP_ID);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getEncryptionKey(Documents\Devices\Device $device): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::ENCRYPTION_KEY);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getModel(Documents\Devices\Device $device): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::MODEL);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getManufacturer(Documents\Devices\Device $device): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::MANUFACTURER);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getMacAddress(Documents\Devices\Device $device): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::MAC_ADDRESS);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getSerialNumber(Documents\Devices\Device $device): string|null
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::SERIAL_NUMBER);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return null;
		}

		$value = $property->getValue();
		assert(is_string($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getStateReadingDelay(Documents\Devices\Device $device): float
	{
		$findPropertyQuery = new DevicesQueries\Configuration\FindDeviceVariableProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::STATE_READING_DELAY);

		$property = $this->devicesPropertiesConfigurationRepository->findOneBy(
			$findPropertyQuery,
			DevicesDocuments\Devices\Properties\Variable::class,
		);

		if ($property?->getValue() === null) {
			return Entities\Devices\Device::STATE_READING_DELAY;
		}

		$value = $property->getValue();
		assert(is_numeric($value));

		return floatval($value);
	}

}
