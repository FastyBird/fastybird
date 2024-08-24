<?php declare(strict_types = 1);

/**
 * DeviceType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           04.03.23
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Types;

/**
 * Device mapping group type
 *
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum DeviceType: string
{

	case LIGHT = 'light';

	case SWITCH = 'switch';

	case ROLLER = 'roller';

	case INPUT = 'input';

}
