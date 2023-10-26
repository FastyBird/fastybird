<?php declare(strict_types = 1);

/**
 * Drivers.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           26.10.23
 */

namespace FastyBird\Connector\Virtual\Helpers;

use function count;
use function implode;
use function shuffle;

/**
 * Drivers helper
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Drivers
{

	public static function generateMacAddress(): string
	{
		$allowedValues = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'];

		$mac = [];

		while (count($mac) < 7) {
			shuffle($allowedValues);

			$mac[] = $allowedValues[0] . $allowedValues[1];
		}

		return implode(':', $mac);
	}

}
