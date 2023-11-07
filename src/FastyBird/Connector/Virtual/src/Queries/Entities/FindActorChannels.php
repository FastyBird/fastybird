<?php declare(strict_types = 1);

/**
 * FindActorChannels.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           26.10.23
 */

namespace FastyBird\Connector\Virtual\Queries\Entities;

use FastyBird\Connector\Virtual\Entities;

/**
 * Find device actors channels entities query
 *
 * @template T of Entities\Channels\Actors
 * @extends  FindChannels<T>
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindActorChannels extends FindChannels
{

}
