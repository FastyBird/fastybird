<?php declare(strict_types = 1);

/**
 * ChannelsRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           23.04.17
 */

namespace FastyBird\Module\Devices\Models\Channels;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Utilities;
use IPub\DoctrineOrmQuery;
use Nette;
use function is_array;

/**
 * Channel repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelsRepository
{

	use Nette\SmartObject;

	/** @var ORM\EntityRepository<Entities\Channels\Channel>|null */
	private ORM\EntityRepository|null $repository = null;

	public function __construct(
		private readonly Utilities\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(Queries\FindChannels $queryObject): Entities\Channels\Channel|null
	{
		return $this->database->query(
			fn (): Entities\Channels\Channel|null => $queryObject->fetchOne($this->getRepository()),
		);
	}

	/**
	 * @phpstan-return array<Entities\Channels\Channel>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(Queries\FindChannels $queryObject): array
	{
		return $this->database->query(
			function () use ($queryObject): array {
				/** @var array<Entities\Channels\Channel>|DoctrineOrmQuery\ResultSet<Entities\Channels\Channel> $result */
				$result = $queryObject->fetch($this->getRepository());

				if (is_array($result)) {
					return $result;
				}

				/** @var array<Entities\Channels\Channel> $data */
				$data = $result->toArray();

				return $data;
			},
		);
	}

	/**
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Channels\Channel>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getResultSet(
		Queries\FindChannels $queryObject,
	): DoctrineOrmQuery\ResultSet
	{
		return $this->database->query(
			function () use ($queryObject): DoctrineOrmQuery\ResultSet {
				/** @var DoctrineOrmQuery\ResultSet<Entities\Channels\Channel> $result */
				$result = $queryObject->fetch($this->getRepository());

				return $result;
			},
		);
	}

	/**
	 * @param class-string<Entities\Channels\Channel> $type
	 *
	 * @return ORM\EntityRepository<Entities\Channels\Channel>
	 */
	private function getRepository(string $type = Entities\Channels\Channel::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			$this->repository = $this->managerRegistry->getRepository($type);
		}

		return $this->repository;
	}

}
