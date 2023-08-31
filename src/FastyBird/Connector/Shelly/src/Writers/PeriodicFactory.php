<?php declare(strict_types = 1);

/**
 * PeriodicFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           30.08.23
 */

namespace FastyBird\Connector\Shelly\Writers;

use FastyBird\Connector\Shelly\Entities;

/**
 * Event loop device state periodic writer factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface PeriodicFactory extends WriterFactory
{

	public function create(Entities\ShellyConnector $connector): Periodic;

}
