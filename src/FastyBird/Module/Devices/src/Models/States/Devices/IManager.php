<?php declare(strict_types = 1);

/**
 * IManager.php
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
use Nette\Utils;
use Ramsey\Uuid;
use React\Promise;

/**
 * Device properties manager interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IManager
{

	/**
	 * @return States\DeviceProperty|Promise\PromiseInterface<States\DeviceProperty>
	 */
	public function create(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
	): States\DeviceProperty|Promise\PromiseInterface;

	/**
	 * @return States\DeviceProperty|Promise\PromiseInterface<States\DeviceProperty>|false
	 */
	public function update(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
	): States\DeviceProperty|Promise\PromiseInterface|false;

	/**
	 * @return Promise\PromiseInterface<bool>|bool
	 */
	public function delete(Uuid\UuidInterface $id): Promise\PromiseInterface|bool;

}
