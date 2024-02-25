<?php declare(strict_types = 1);

/**
 * Category.php
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

/**
 * Device category types
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Category: string
{

	case UNKNOWN = 'unknown';

	case PLUG = 'plug';

	case SWITCH = 'switch';

	case LIGHT = 'light';

	case CURTAIN = 'curtain';

	case CONTACT_SENSOR = 'contactSensor';

	case MOTION_SENSOR = 'motionSensor';

	case TEMPERATURE_SENSOR = 'temperatureSensor';

	case HUMIDITY_SENSOR = 'humiditySensor';

	case TEMPERATURE_HUMIDITY_SENSOR = 'temperatureAndHumiditySensor';

	case WATTER_LEAK_DETECTOR = 'waterLeakDetector';

	case SMOKE_DETECTOR = 'smokeDetector';

	case BUTTON = 'button';

	case CAMERA = 'camera';

	case SENSOR = 'sensor';

}
