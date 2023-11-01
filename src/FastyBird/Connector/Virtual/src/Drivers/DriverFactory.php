<?php declare(strict_types = 1);

/**
 * DriverFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Services
 * @since          1.0.0
 *
 * @date           16.10.23
 */

namespace FastyBird\Connector\Virtual\Drivers;

use FastyBird\Connector\Virtual\Entities;

/**
 * Driver factory
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Services
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface DriverFactory
{

	public const DRIVER_DEVICE = Entities\VirtualDevice::class;

	public function create(Entities\VirtualDevice $device): Driver;

}
