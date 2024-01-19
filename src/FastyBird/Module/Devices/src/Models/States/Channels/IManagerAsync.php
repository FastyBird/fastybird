<?php declare(strict_types = 1);

/**
 * IManagerAsync.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           19.01.23
 */

namespace FastyBird\Module\Devices\Models\States\Channels;

use FastyBird\Module\Devices\States;
use Nette\Utils;
use Ramsey\Uuid;
use React\Promise;

/**
 * Asynchronous channel properties manager interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IManagerAsync
{

	/**
	 * @return Promise\PromiseInterface<States\ChannelProperty>
	 */
	public function create(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
	): Promise\PromiseInterface;

	/**
	 * @return Promise\PromiseInterface<States\ChannelProperty|false>
	 */
	public function update(
		Uuid\UuidInterface $id,
		Utils\ArrayHash $values,
	): Promise\PromiseInterface;

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function delete(Uuid\UuidInterface $id): Promise\PromiseInterface;

}
