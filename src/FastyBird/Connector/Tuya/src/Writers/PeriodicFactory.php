<?php declare(strict_types = 1);

/**
 * PeriodicFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           16.08.23
 */

namespace FastyBird\Connector\Tuya\Writers;

use FastyBird\Connector\Tuya\Entities;

/**
 * Event loop device state periodic writer factory
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface PeriodicFactory extends WriterFactory
{

	public function create(Entities\TuyaConnector $connector): Periodic;

}
