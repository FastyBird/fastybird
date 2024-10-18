<?php declare(strict_types = 1);

/**
 * Configuration.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           05.10.24
 */

namespace FastyBird\Connector\NsPanel\Types;

/**
 * Capability configuration types
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Configuration: string
{

	case STREAM_URL = 'streamUrl';

	case RANGE_MIN = 'range_min';

	case RANGE_MAX = 'range_max';

	case SUPPORTED_LOWER_SET_POINT_VALUE_VALUE = 'supported_lowerSetpoint_value_value';

	case SUPPORTED_LOWER_SET_POINT_VALUE_SCALE = 'supported_lowerSetpoint_value_scale';

	case SUPPORTED_UPPER_SET_POINT_VALUE_VALUE = 'supported_upperSetpoint_value_value';

	case SUPPORTED_UPPER_SET_POINT_VALUE_SCALE = 'supported_upperSetpoint_value_scale';

	case SUPPORTED_MODES = 'supportedModes';

	case TEMPERATURE_MIN = 'temperature_min';

	case TEMPERATURE_MAX = 'temperature_max';

	case TEMPERATURE_INCREMENT = 'temperature_increment';

	case TEMPERATURE_SCALE = 'temperature_scale';

	case MAPPING_MODE = 'mappingMode';

	case WEEKLY_SCHEDULE_MAX_ENTRY_PER_DAY = 'weeklySchedule_maxEntryPerDay';

}
