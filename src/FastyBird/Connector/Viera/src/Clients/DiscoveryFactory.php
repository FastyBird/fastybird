<?php declare(strict_types = 1);

/**
 * DiscoveryFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           04.07.23
 */

namespace FastyBird\Connector\Viera\Clients;

use FastyBird\Connector\Viera\Documents;

/**
 * Devices discovery client factory
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface DiscoveryFactory
{

	public function create(Documents\Connectors\Connector $connector): Discovery;

}
