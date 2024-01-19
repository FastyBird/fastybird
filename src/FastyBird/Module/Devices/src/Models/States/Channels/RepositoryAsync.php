<?php declare(strict_types = 1);

/**
 * RepositoryAsync.php
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

namespace FastyBird\Module\Devices\Models\States\Channels;

use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\States;
use Nette;
use Ramsey\Uuid;
use React\Promise;

/**
 * Asynchronous channel property repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class RepositoryAsync
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Repository $fallback,
		private readonly IRepositoryAsync|null $repository = null,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<States\ChannelProperty|null>
	 *
	 * @interal
	 */
	public function find(Uuid\UuidInterface $id): Promise\PromiseInterface
	{
		if ($this->repository === null) {
			try {
				return Promise\resolve($this->fallback->find($id));
			} catch (Exceptions\NotImplemented $ex) {
				return Promise\reject($ex);
			}
		}

		return $this->repository->find($id);
	}

}
