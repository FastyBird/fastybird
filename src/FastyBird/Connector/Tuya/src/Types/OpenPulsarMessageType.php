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

use Consistence;
use function strval;

/**
 * OpenPulsar message types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class OpenPulsarMessageType extends Consistence\Enum\Enum
{

	/**
	 * Define versions
	 */
	public const BIZ_CODE_ONLINE = 'online';

	public const BIZ_CODE_OFFLINE = 'offline';

	public const BIZ_CODE_NAME_UPDATE = 'nameUpdate';

	public const BIZ_CODE_DP_NAME_UPDATE = 'dpNameUpdate';

	public const BIZ_CODE_BIND_USER = 'bindUser';

	public const BIZ_CODE_DELETE = 'delete';

	public const BIZ_CODE_UPGRADE_STATUS = 'upgradeStatus';

	public const BIZ_CODE_OUTDOORS_FENCE_ALARM = 'outdoorsFenceAlarm';

	public const BIZ_CODE_AUTOMATION_EXTERNAL_ACTION = 'automationExternalAction';

	public const BIZ_CODE_SIM_STOP = 'simStop';

	public const BIZ_CODE_SIM_LIMIT_ALARM = 'simLimitAlarm';

	public const BIZ_CODE_TEXT_TO_SPEECH = 'textToSpeech';

	public const BIZ_CODE_RESET = 'reset';

	public const BIZ_CODE_CUSTOM_QA_CHANGE = 'customQAChange';

	public const BIZ_CODE_HOTEL_PMS_CUSTOMER_CHECKIN = 'hotelPmsCustomerCheckin';

	public const BIZ_CODE_HOTEL_PMS_CUSTOMER_CHECKOUT = 'hotelPmsCustomerCheckout';

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
