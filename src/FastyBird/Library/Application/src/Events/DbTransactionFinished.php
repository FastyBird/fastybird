<?php declare(strict_types = 1);

/**
 * DbTransactionFinished.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApplicationLibrary!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           11.09.24
 */

namespace FastyBird\Library\Application\Events;

use Symfony\Contracts\EventDispatcher;

/**
 * Database transaction finished event
 *
 * @package        FastyBird:ApplicationLibrary!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DbTransactionFinished extends EventDispatcher\Event
{

}
