<?php declare(strict_types = 1);

/**
 * OpenPulsarMessageType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           04.09.22
 */

namespace FastyBird\Connector\Tuya\Types;

/**
 * OpenPulsar message types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum OpenPulsarMessageType: string
{

	case ONLINE = 'online';

	case OFFLINE = 'offline';

	case NAME_UPDATE = 'nameUpdate';

	case DP_NAME_UPDATE = 'dpNameUpdate';

	case BIND_USER = 'bindUser';

	case DELETE = 'delete';

	case UPGRADE_STATUS = 'upgradeStatus';

	case OUTDOORS_FENCE_ALARM = 'outdoorsFenceAlarm';

	case AUTOMATION_EXTERNAL_ACTION = 'automationExternalAction';

	case SIM_STOP = 'simStop';

	case SIM_LIMIT_ALARM = 'simLimitAlarm';

	case TEXT_TO_SPEECH = 'textToSpeech';

	case RESET = 'reset';

	case CUSTOM_QA_CHANGE = 'customQAChange';

	case HOTEL_PMS_CUSTOMER_CHECKIN = 'hotelPmsCustomerCheckin';

	case HOTEL_PMS_CUSTOMER_CHECKOUT = 'hotelPmsCustomerCheckout';

}
