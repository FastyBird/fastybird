<?php declare(strict_types = 1);

/**
 * Repository.php
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

namespace FastyBird\Module\Devices\Models\States\Devices\Async;

use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Caching;
use Ramsey\Uuid;
use React\Promise;
use Throwable;
use function React\Async\async;
use function React\Async\await;

/**
 * Asynchronous device property repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Repository
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\States\Devices\Repository $fallback,
		private readonly Caching\Cache $cache,
		private readonly IRepository|null $repository = null,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<States\DeviceProperty|null>
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

		try {
			/** @phpstan-var States\DeviceProperty|null $state */
			$state = $this->cache->load(
				$id->toString(),
				async(function () use ($id): States\DeviceProperty|null {
					if ($this->repository === null) {
						return null;
					}

					return await($this->repository->find($id));
				}),
				[
					Caching\Cache::Tags => [$id->toString()],
				],
			);

			return Promise\resolve($state);
		} catch (Throwable $ex) {
			return Promise\reject($ex);
		}
	}

}
