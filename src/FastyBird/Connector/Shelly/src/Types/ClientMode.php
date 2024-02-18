<?php declare(strict_types = 1);

/**
 * ClientMode.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           04.08.22
 */

namespace FastyBird\Connector\Shelly\Types;

/**
 * Connector client modes
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ClientMode: string
{

	case LOCAL = 'local';

	case CLOUD = 'cloud';

	case INTEGRATOR = 'integrator';

	case MQTT = 'mqtt';

}
