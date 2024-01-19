<?php declare(strict_types = 1);

/**
 * IRepositoryAsync.php
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

namespace FastyBird\Module\Devices\Models\States\Devices;

use FastyBird\Module\Devices\States;
use Ramsey\Uuid;
use React\Promise;

/**
 * Asynchronous device property repository interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IRepositoryAsync
{

	/**
	 * @return Promise\PromiseInterface<States\DeviceProperty|null>
	 */
	public function find(Uuid\UuidInterface $id): Promise\PromiseInterface;

}
