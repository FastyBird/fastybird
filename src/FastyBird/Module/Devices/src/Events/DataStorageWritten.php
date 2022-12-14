<?php declare(strict_types = 1);

/**
 * DataStorageWritten.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 * @since          0.65.0
 *
 * @date           29.06.22
 */

namespace FastyBird\Module\Devices\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * Event fired after data storage configuration has been written
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DataStorageWritten extends EventDispatcher\Event
{

}
