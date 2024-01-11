<?php declare(strict_types = 1);

/**
 * DriverFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Drivers
 * @since          1.0.0
 *
 * @date           16.10.23
 */

namespace FastyBird\Connector\Virtual\Drivers;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;

/**
 * Driver factory
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Drivers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface DriverFactory
{

	public const DEVICE_TYPE = Entities\VirtualDevice::TYPE;

	public function create(MetadataDocuments\DevicesModule\Device $device): Driver;

}
