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
use Exception;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Queries;
use IPub\DoctrineOrmQuery;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
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

	public function __construct(private readonly Persistence\ManagerRegistry $managerRegistry)
	{
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findOneBy(Queries\FindChannels $queryObject): Entities\Channels\Channel|null
	{
		return $queryObject->fetchOne($this->getRepository());
	}

	/**
	 * @phpstan-return Array<Entities\Channels\Channel>
	 *
	 * @throws Exception
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function findAllBy(Queries\FindChannels $queryObject): array
	{
		/** @var Array<Entities\Channels\Channel>|DoctrineOrmQuery\ResultSet<Entities\Channels\Channel> $result */
		$result = $queryObject->fetch($this->getRepository());

		if (is_array($result)) {
			return $result;
		}

		/** @var Array<Entities\Channels\Channel> $data */
		$data = $result->toArray();

		return $data;
	}

	/**
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Channels\Channel>
	 *
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 */
	public function getResultSet(
		Queries\FindChannels $queryObject,
	): DoctrineOrmQuery\ResultSet
	{
		/** @var DoctrineOrmQuery\ResultSet<Entities\Channels\Channel> $result */
		$result = $queryObject->fetch($this->getRepository());

		return $result;
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
