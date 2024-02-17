<?php declare(strict_types = 1);

/**
 * ChannelPropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           06.10.22
 */

namespace FastyBird\Connector\HomeKit\Types;

/**
 * Channel property identifier types
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ChannelPropertyIdentifier: string
{

	case VERSION = 'version';

	case NAME = 'name';

	case SERIAL_NUMBER = 'serial_number';

	case FIRMWARE_REVISION = 'firmware_revision';

	case MANUFACTURER = 'manufacturer';

	case MODEL = 'model';

	case IDENTIFY = 'identify';

}
