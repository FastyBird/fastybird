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

/**
 * Requests & Responses header name types
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum Header: string
{

	case RESPONSE = 'Response';

	case ERROR_RESPONSE = 'ErrorResponse';

	case UPDATE_DEVICE_STATES_RESPONSE = 'UpdateDeviceStatesResponse';

	case DISCOVERY_REQUEST = 'DiscoveryRequest';

	case DEVICE_STATES_CHANGE_REPORT = 'DeviceStatesChangeReport';

	case DEVICE_ONLINE_CHANGE_REPORT = 'DeviceOnlineChangeReport';

	case UPDATE_DEVICE_STATES = 'UpdateDeviceStates';

}
