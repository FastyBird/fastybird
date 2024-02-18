<?php declare(strict_types = 1);

/**
 * InputType.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           27.12.22
 */

namespace FastyBird\Connector\Shelly\Types;

/**
 * Input component input types
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum InputType: string
{

	case SWITCH = 'switch';

	case BUTTON = 'button';

	case ANALOG = 'analog';

}
