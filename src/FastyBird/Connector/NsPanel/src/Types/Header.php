<?php declare(strict_types = 1);

/**
 * Header.php
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

use Consistence;
use function strval;

/**
 * Requests & Responses header name types
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Header extends Consistence\Enum\Enum
{

	public const RESPONSE = 'Response';

	public const ERROR_RESPONSE = 'ErrorResponse';

	public const UPDATE_DEVICE_STATES_RESPONSE = 'UpdateDeviceStatesResponse';

	public const DISCOVERY_REQUEST = 'DiscoveryRequest';

	public const DEVICE_STATES_CHANGE_REPORT = 'DeviceStatesChangeReport';

	public const DEVICE_ONLINE_CHANGE_REPORT = 'DeviceOnlineChangeReport';

	public const UPDATE_DEVICE_STATES = 'UpdateDeviceStates';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
