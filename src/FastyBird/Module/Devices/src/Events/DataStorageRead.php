<?php declare(strict_types = 1);

/**
 * DataStorageRead.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Devices!
 * @subpackage     Events
 * @since          0.65.0
 *
 * @date           29.06.22
 */

namespace FastyBird\Module\Devices\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * Event fired after data storage configuration has been read
 *
 * @package        FastyBird:Devices!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DataStorageRead extends EventDispatcher\Event
{

}
