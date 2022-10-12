<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Helpers
 * @since          0.13.0
 *
 * @date           24.08.22
 */

namespace FastyBird\TuyaConnector\Helpers;

use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use FastyBird\TuyaConnector\Types;
use Nette;
use Ramsey\Uuid;
use function is_bool;
use function is_string;
use function strval;

/**
 * Useful device helpers
 *
 * @package        FastyBird:TuyaConnector!
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
	 * @throws MetadataExceptions\FileNotFound
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
			} elseif (
				$type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_PROTOCOL_VERSION
				&& !Types\DeviceProtocolVersion::isValidValue($configuration->getValue())
			) {
				return Types\DeviceProtocolVersion::VERSION_V33;
			} elseif ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_LOCAL_KEY) {
				return is_string($configuration->getValue()) ? $configuration->getValue() : null;
			} elseif ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_USER_IDENTIFIER) {
				return is_string($configuration->getValue()) ? $configuration->getValue() : null;
			} elseif ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_ENCRYPTED) {
				return is_bool($configuration->getValue()) ? $configuration->getValue() : false;
			}

			return $configuration->getValue();
		}

		if ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_PROTOCOL_VERSION) {
			return Types\DeviceProtocolVersion::VERSION_V33;
		}

		if ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_LOCAL_KEY) {
			return '712aadb9520c1dc2';
		}

		return null;
	}

}
