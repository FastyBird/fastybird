<?php declare(strict_types = 1);

/**
 * DevicePropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Types;

use Consistence;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use function strval;

/**
 * Device property identifier types
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertyIdentifier extends Consistence\Enum\Enum
{

	public const STATE = MetadataTypes\DevicePropertyIdentifier::STATE;

	public const IP_ADDRESS = MetadataTypes\DevicePropertyIdentifier::IP_ADDRESS;

	public const DOMAIN = MetadataTypes\DevicePropertyIdentifier::DOMAIN;

	public const MANUFACTURER = MetadataTypes\DevicePropertyIdentifier::HARDWARE_MANUFACTURER;

	public const MODEL = MetadataTypes\DevicePropertyIdentifier::HARDWARE_MODEL;

	public const MAC_ADDRESS = MetadataTypes\DevicePropertyIdentifier::HARDWARE_MAC_ADDRESS;

	public const FIRMWARE_VERSION = MetadataTypes\DevicePropertyIdentifier::FIRMWARE_VERSION;

	public const ACCESS_TOKEN = 'access_token';

	public const CATEGORY = 'category';

	public const GATEWAY_IDENTIFIER = 'gateway_identifier';

	public const STATE_READING_DELAY = MetadataTypes\DevicePropertyIdentifier::STATE_READING_DELAY;

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
