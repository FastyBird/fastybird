<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           26.10.23
 */

namespace FastyBird\Addon\ThermostatDevice;

/**
 * Connector constants
 *
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	public const MANUFACTURER = 'FastyBird';

	public const PRESET_CHANNEL_PATTERN = '/^preset(_(?P<preset>[a-zA-Z_]+))?$/';

}
