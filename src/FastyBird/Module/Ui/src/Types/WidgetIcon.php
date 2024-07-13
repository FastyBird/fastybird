<?php declare(strict_types = 1);

/**
 * WidgetIconType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           24.09.18
 */

namespace FastyBird\Module\Ui\Types;

/**
 * Widget supported icons
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum WidgetIcon: string
{

	case THERMOMETER = 'thermometer';

	case LIGHTING = 'lighting';

	case VALVE = 'valve';

	case MOTOR = 'motor';

	case LOCK = 'lock';

	case PLUG = 'plug';

	case BUTTON = 'button';

	case HUMIDITY = 'humidity';

	case LUMINOSITY = 'luminosity';

	case FAN = 'fan';

	case MIC = 'mic';

	case LED = 'led';

	case GAUGE = 'gauge';

	case KNOB = 'knob';

	case MOTION = 'motion';

}
