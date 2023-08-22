<?php declare(strict_types = 1);

/**
 * FindConnectors.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           13.08.23
 */

namespace FastyBird\Connector\Tuya\Queries;

use FastyBird\Connector\Tuya\Entities;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Find connectors entities query
 *
 * @template T of Entities\TuyaConnector
 * @extends  DevicesQueries\FindConnectors<T>
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindConnectors extends DevicesQueries\FindConnectors
{

}
