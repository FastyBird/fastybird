<?php declare(strict_types = 1);

/**
 * DiscoveryFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 * @since          0.13.0
 *
 * @date           27.08.22
 */

namespace FastyBird\Connector\Tuya\Clients;

use FastyBird\Connector\Tuya\Entities;

/**
 * Local devices client factory
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface DiscoveryFactory
{

	public function create(Entities\TuyaConnector $connector): Discovery;

}
