<?php declare(strict_types = 1);

/**
 * CloudFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           03.01.23
 */

namespace FastyBird\Connector\Shelly\Clients;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;

/**
 * Cloud client factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface CloudFactory extends ClientFactory
{

	public const MODE = Types\ClientMode::MODE_CLOUD;

	public function create(Entities\ShellyConnector $connector): Cloud;

}
