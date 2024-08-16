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

namespace FastyBird\Module\Devices\Models\States\Connectors\Async;

use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\States;
use Nette;
use Ramsey\Uuid;
use React\Promise;
use Throwable;

/**
 * Asynchronous connector property repository
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
		private readonly Models\States\Connectors\Repository $fallback,
		private readonly IRepository|null $repository = null,
	)
	{
	}

	/**
	 * @return Promise\PromiseInterface<States\ConnectorProperty|null>
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

		$deferred = new Promise\Deferred();

		$this->repository->find($id)
			->then(static function (States\ConnectorProperty|null $state) use ($deferred): void {
				$deferred->resolve($state);
			})
			->catch(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
			});

		return $deferred->promise();
	}

}
