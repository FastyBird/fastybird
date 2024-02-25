<?php declare(strict_types = 1);

/**
 * ConnectorPropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           08.02.22
 */

namespace FastyBird\Module\Devices\Types;

/**
 * Connector property identifier types
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ConnectorPropertyIdentifier: string
{

	case STATE = 'state';

	case SERVER = 'server';

	case PORT = 'port';

	case SECURED_PORT = 'secured_port';

	case BAUD_RATE = 'baud_rate';

	case INTERFACE = 'interface';

	case ADDRESS = 'address';

}
