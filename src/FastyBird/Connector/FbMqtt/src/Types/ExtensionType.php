<?php declare(strict_types = 1);

/**
 * ExtensionType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           05.07.22
 */

namespace FastyBird\Connector\FbMqtt\Types;

/**
 * Extension types
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ExtensionType: string
{

	case FASTYBIRD_HARDWARE = 'com.fastybird.hardware';

	case FASTYBIRD_FIRMWARE = 'com.fastybird.firmware';

}
