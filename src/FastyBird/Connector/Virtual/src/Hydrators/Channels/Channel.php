<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           20.10.23
 */

namespace FastyBird\Connector\Virtual\Hydrators\Channels;

use FastyBird\Connector\Virtual\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * Virtual channel entity hydrator
 *
 * @template  T of Entities\Channels\Channel
 * @extends   DevicesHydrators\Channels\Channel<T>
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Channel extends DevicesHydrators\Channels\Channel
{

}
