<?php declare(strict_types = 1);

/**
 * ClientFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Connector\Tuya\Clients;

use FastyBird\Connector\Tuya\Entities;

/**
 * Base client factory
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ClientFactory
{

	public const MODE_CONSTANT_NAME = 'MODE';

	public function create(Entities\TuyaConnector $connector): Client;

}
