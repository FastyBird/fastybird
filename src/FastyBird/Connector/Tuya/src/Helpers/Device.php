<?php declare(strict_types = 1);

/**
 * Device.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Helpers
 * @since          0.13.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Connector\Tuya\Helpers;

use DateTimeInterface;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use function explode;
use function is_bool;
use function is_int;
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

	private const STATUS_READING_DELAY = 120.0;

	/**
	 * @return float|bool|int|string|array<int, string>|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|DateTimeInterface|null
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getConfiguration(
		Entities\TuyaDevice $device,
		Types\DevicePropertyIdentifier $type,
	): float|bool|int|string|array|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|DateTimeInterface|null
	{
		$configuration = $device->findProperty(strval($type->getValue()));

		if ($configuration instanceof DevicesEntities\Devices\Properties\Variable) {
			if ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS) {
				return is_string($configuration->getValue()) ? $configuration->getValue() : null;
			} elseif (
				$type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_PROTOCOL_VERSION
				&& !Types\DeviceProtocolVersion::isValidValue($configuration->getValue())
			) {
				return Types\DeviceProtocolVersion::VERSION_V33;
			} elseif ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_LOCAL_KEY) {
				return is_string($configuration->getValue()) ? $configuration->getValue() : null;
			} elseif ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_ENCRYPTED) {
				return is_bool($configuration->getValue()) ? $configuration->getValue() : false;
			} elseif ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_STATUS_READING_DELAY) {
				return is_int($configuration->getValue()) ? $configuration->getValue() : self::STATUS_READING_DELAY;
			} elseif ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_READ_STATE_EXCLUDE_DPS) {
				return is_string($configuration->getValue()) ? explode(',', $configuration->getValue()) : [];
			}

			return $configuration->getValue();
		}

		if ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_PROTOCOL_VERSION) {
			return Types\DeviceProtocolVersion::VERSION_V33;
		}

		if ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_STATUS_READING_DELAY) {
			return self::STATUS_READING_DELAY;
		}

		if ($type->getValue() === Types\DevicePropertyIdentifier::IDENTIFIER_READ_STATE_EXCLUDE_DPS) {
			return [];
		}

		return null;
	}

}
