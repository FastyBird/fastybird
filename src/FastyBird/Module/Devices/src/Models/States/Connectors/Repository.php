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

namespace FastyBird\Module\Devices\Models\States\Connectors;

use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\States;
use Nette;
use Nette\Caching;
use Ramsey\Uuid;

/**
 * Connector property repository
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
		private readonly Caching\Cache $cache,
		private readonly IRepository|null $repository = null,
	)
	{
	}

	/**
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function find(Uuid\UuidInterface $id): States\ConnectorProperty|null
	{
		if ($this->repository === null) {
			throw new Exceptions\NotImplemented('Connector properties state repository is not registered');
		}

		/** @phpstan-var States\ConnectorProperty|null $state */
		$state = $this->cache->load(
			$id->toString(),
			function () use ($id): States\ConnectorProperty|null {
				if ($this->repository === null) {
					return null;
				}

				return $this->repository->find($id);
			},
			[
				Caching\Cache::Tags => [$id->toString()],
			],
		);

		return $state;
	}

}
