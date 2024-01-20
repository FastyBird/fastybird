<?php declare(strict_types = 1);

/**
 * IRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           09.01.22
 */

namespace FastyBird\Module\Devices\Models\States\Channels\Async;

use FastyBird\Module\Devices\States;
use Ramsey\Uuid;
use React\Promise;

/**
 * Asynchronous channel property repository interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IRepository
{

	/**
	 * @return Promise\PromiseInterface<States\ChannelProperty|null>
	 */
	public function find(Uuid\UuidInterface $id): Promise\PromiseInterface;

}
