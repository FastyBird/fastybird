<?php declare(strict_types = 1);

/**
 * FindChannelDynamicProperties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           25.11.18
 */

namespace FastyBird\Module\Devices\Queries;

use FastyBird\Module\Devices\Entities;

/**
 * Find channel dynamic properties entities query
 *
 * @template T of Entities\Channels\Properties\Dynamic
 * @extends  FindChannelProperties<T>
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Queries
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindChannelDynamicProperties extends FindChannelProperties
{

}
