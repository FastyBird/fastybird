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

	public const ONLINE = 'online';

	public const OFFLINE = 'offline';

	public const NAME_UPDATE = 'nameUpdate';

	public const DP_NAME_UPDATE = 'dpNameUpdate';

	public const BIND_USER = 'bindUser';

	public const DELETE = 'delete';

	public const UPGRADE_STATUS = 'upgradeStatus';

	public const OUTDOORS_FENCE_ALARM = 'outdoorsFenceAlarm';

	public const AUTOMATION_EXTERNAL_ACTION = 'automationExternalAction';

	public const SIM_STOP = 'simStop';

	public const SIM_LIMIT_ALARM = 'simLimitAlarm';

	public const TEXT_TO_SPEECH = 'textToSpeech';

	public const RESET = 'reset';

	public const CUSTOM_QA_CHANGE = 'customQAChange';

	public const HOTEL_PMS_CUSTOMER_CHECKIN = 'hotelPmsCustomerCheckin';

	public const HOTEL_PMS_CUSTOMER_CHECKOUT = 'hotelPmsCustomerCheckout';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
