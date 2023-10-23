<?php declare(strict_types = 1);

/**
 * VirtualChannel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Schemas
 * @since          1.0.0
 *
 * @date           20.10.23
 */

namespace FastyBird\Connector\Virtual\Schemas;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Module\Devices\Schemas as DevicesSchemas;

/**
 * Virtual channel entity schema
 *
 * @template T of Entities\VirtualChannel
 * @extends  DevicesSchemas\Channels\Channel<T>
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Schemas
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class VirtualChannel extends DevicesSchemas\Channels\Channel
{

}
