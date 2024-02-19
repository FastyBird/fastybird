<?php declare(strict_types = 1);

/**
 * OpenPulsarEndpoint.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Tuya\Types;

/**
 * OpenPulsar endpoint types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum OpenPulsarEndpoint: string
{

	case CHINA = 'wss://mqe.tuyacn.com:8285/';

	case AMERICA = 'wss://mqe.tuyaus.com:8285/';

	case EUROPE = 'wss://mqe.tuyaeu.com:8285/';

	case INDIA = 'wss://mqe.tuyain.com:8285/';

}
