<?php declare(strict_types = 1);

/**
 * TemperatureScale.php
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

namespace FastyBird\Connector\NsPanel\Types\Payloads;

/**
 * Thermostat temperature unit payload types
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum TemperatureScale: string implements Payload
{

	case CELSIUS = 'c';

	case FAHRENHEIT = 'f';

}
