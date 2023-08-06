<?php declare(strict_types = 1);

/**
 * FindConnectorDynamicProperties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           07.08.23
 */

namespace FastyBird\Module\Devices\Queries;

use FastyBird\Module\Devices\Entities;

/**
 * Find connector dynamic properties entities query
 *
 * @template T of Entities\Connectors\Properties\Dynamic
 * @extends  FindConnectorProperties<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindConnectorDynamicProperties extends FindConnectorProperties
{

}
