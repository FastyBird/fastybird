<?php declare(strict_types = 1);

/**
 * DevicesFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Devices
 * @since          1.0.0
 *
 * @date           17.10.23
 */

namespace FastyBird\Connector\Virtual\Devices;

use FastyBird\Connector\Virtual\Entities;

/**
 * Devices factory
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Devices
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface DevicesFactory
{

	public function create(Entities\VirtualConnector $connector): Devices;

}
