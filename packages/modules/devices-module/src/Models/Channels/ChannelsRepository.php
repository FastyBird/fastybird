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

namespace FastyBird\DevicesModule\Models\Channels;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Exceptions;
use FastyBird\DevicesModule\Queries;
use IPub\DoctrineOrmQuery;
use Nette;
use Throwable;
use function assert;
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

	public function findOneBy(Queries\FindChannels $queryObject): Entities\Channels\Channel|null
	{
		/** @var mixed $channel */
		$channel = $queryObject->fetchOne($this->getRepository());
		assert($channel instanceof Entities\Channels\Channel || $channel === null);

		return $channel;
	}

	/**
	 * @return Array<Entities\Channels\Channel>
	 *
	 * @throws Throwable
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
	 */
	public function getResultSet(
		Queries\FindChannels $queryObject,
	): DoctrineOrmQuery\ResultSet
	{
		$result = $queryObject->fetch($this->getRepository());

		if (!$result instanceof DoctrineOrmQuery\ResultSet) {
			throw new Exceptions\InvalidState('Result set for given query could not be loaded.');
		}

		return $result;
	}

	/**
	 * @param class-string $type
	 *
	 * @return ORM\EntityRepository<Entities\Channels\Channel>
	 */
	private function getRepository(string $type = Entities\Channels\Channel::class): ORM\EntityRepository
	{
		if ($this->repository === null) {
			/** @var ORM\EntityRepository<Entities\Channels\Channel> $repository */
			$repository = $this->managerRegistry->getRepository($type);

			if (!$repository instanceof ORM\EntityRepository) {
				throw new Exceptions\InvalidState('Entity repository could not be loaded');
			}

			$this->repository = $repository;
		}

		return $this->repository;
	}

}
