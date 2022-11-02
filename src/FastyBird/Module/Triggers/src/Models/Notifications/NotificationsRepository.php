<?php declare(strict_types = 1);

/**
 * NotificationsRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Models\Notifications;

use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Queries;
use FastyBird\Module\Triggers\Utilities;
use IPub\DoctrineOrmQuery;
use Nette;
use function is_array;

/**
 * Notification repository
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class NotificationsRepository
{

	use Nette\SmartObject;

	/** @var Array<ORM\EntityRepository<Entities\Notifications\Notification>> */
	private array $repository = [];

	public function __construct(
		private readonly Utilities\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @phpstan-param class-string<Entities\Notifications\Notification> $type
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findOneBy(
		Queries\FindNotifications $queryObject,
		string $type = Entities\Notifications\Notification::class,
	): Entities\Notifications\Notification|null
	{
		return $this->database->query(
			fn (): Entities\Notifications\Notification|null => $queryObject->fetchOne($this->getRepository($type)),
		);
	}

	/**
	 * @phpstan-param class-string<Entities\Notifications\Notification> $type
	 *
	 * @phpstan-return Array<Entities\Notifications\Notification>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function findAllBy(
		Queries\FindNotifications $queryObject,
		string $type = Entities\Notifications\Notification::class,
	): array
	{
		return $this->database->query(
			function () use ($queryObject, $type): array {
				/** @var Array<Entities\Notifications\Notification>|DoctrineOrmQuery\ResultSet<Entities\Notifications\Notification> $result */
				$result = $queryObject->fetch($this->getRepository($type));

				if (is_array($result)) {
					return $result;
				}

				/** @var Array<Entities\Notifications\Notification> $data */
				$data = $result->toArray();

				return $data;
			},
		);
	}

	/**
	 * @phpstan-param class-string<Entities\Notifications\Notification> $type
	 *
	 * @phpstan-return DoctrineOrmQuery\ResultSet<Entities\Notifications\Notification>
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function getResultSet(
		Queries\FindNotifications $queryObject,
		string $type = Entities\Notifications\Notification::class,
	): DoctrineOrmQuery\ResultSet
	{
		return $this->database->query(
			function () use ($queryObject, $type): DoctrineOrmQuery\ResultSet {
				/** @var DoctrineOrmQuery\ResultSet<Entities\Notifications\Notification> $result */
				$result = $queryObject->fetch($this->getRepository($type));

				return $result;
			},
		);
	}

	/**
	 * @param class-string<Entities\Notifications\Notification> $type
	 *
	 * @return ORM\EntityRepository<Entities\Notifications\Notification>
	 */
	private function getRepository(string $type): ORM\EntityRepository
	{
		if (!isset($this->repository[$type])) {
			$this->repository[$type] = $this->managerRegistry->getRepository($type);
		}

		return $this->repository[$type];
	}

}
