<?php declare(strict_types = 1);

/**
 * CloudFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           06.05.23
 */

namespace FastyBird\Connector\Sonoff\Clients;

use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Types;

/**
 * Cloud client factory
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface CloudFactory extends ClientFactory
{

	public const MODE = Types\ClientMode::MODE_CLOUD;

	public function create(Entities\SonoffConnector $connector): Cloud;

}
