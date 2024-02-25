<?php declare(strict_types = 1);

/**
 * ComponentActionAttribute.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           22.12.23
 */

namespace FastyBird\Connector\Shelly\Types;

/**
 * Generation 2 devices component action attribute
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum ComponentActionAttribute: string
{

	case ON = 'on';

	case POSITION = 'pos';

	case BRIGHTNESS = 'brightness';

}
