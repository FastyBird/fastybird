<?php declare(strict_types = 1);

/**
 * ComponentType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           25.12.22
 */

namespace FastyBird\Connector\Shelly\Types;

/**
 * Generation 2 devices component types
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ComponentType: string
{

	case SWITCH = 'switch';

	case COVER = 'cover';

	case LIGHT = 'light';

	case INPUT = 'input';

	case TEMPERATURE = 'temperature';

	case HUMIDITY = 'humidity';

	case VOLTMETER = 'voltmeter';

	case SCRIPT = 'script';

	case DEVICE_POWER = 'devicepower';

	case SMOKE = 'smoke';

	case ETHERNET = 'ethernet';

	case WIFI = 'wifi';

}
