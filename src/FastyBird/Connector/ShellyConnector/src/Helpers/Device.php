<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Helpers
 * @since          0.37.0
 *
 * @date           04.08.22
 */

namespace FastyBird\ShellyConnector\Helpers;

use FastyBird\DevicesModule\Exceptions as DevicesModuleExceptions;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\ShellyConnector\Types;
use Nette;
use Ramsey\Uuid;
use function is_bool;
use function is_string;
use function strval;

/**
 * Useful device helpers
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Device
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModuleModels\DataStorage\DevicePropertiesRepository $propertiesRepository,
	)
	{
	}

	/**
	 * @throws DevicesModuleExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getConfiguration(
		Uuid\UuidInterface $deviceId,
		Types\DevicePropertyIdentifier $type,
	): float|bool|int|string|null
	{
		$configuration = $this->propertiesRepository->findByIdentifier($deviceId, strval($type->getValue()));

		if ($configuration instanceof MetadataEntities\DevicesModule\DeviceVariableProperty) {
			if ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS) {
				return is_string($configuration->getValue()) ? $configuration->getValue() : null;
			} elseif ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_USERNAME) {
				return is_string($configuration->getValue()) ? $configuration->getValue() : null;
			} elseif ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_PASSWORD) {
				return is_string($configuration->getValue()) ? $configuration->getValue() : null;
			} elseif ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_AUTH_ENABLED) {
				return is_bool($configuration->getValue()) ? $configuration->getValue() : null;
			}

			return $configuration->getValue();
		}

		return null;
	}

}
