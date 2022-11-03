<?php declare(strict_types = 1);

/**
 * Database.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 * @since          0.73.0
 *
 * @date           26.10.22
 */

namespace FastyBird\Module\Devices\Utilities;

use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Library\Bootstrap;
use FastyBird\Module\Devices\Exceptions;
use Nette;
use Throwable;

/**
 * Useful database utilities
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Database
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Bootstrap\Helpers\Database $database,
		private readonly Persistence\ManagerRegistry $managerRegistry,
	)
	{
	}

	/**
	 * @param callable(): T $callback
	 *
	 * @return T
	 *
	 * @throws Exceptions\InvalidState
	 *
	 * @template T
	 */
	public function query(callable $callback)
	{
		try {
			$this->pingAndReconnect();

			return $callback();
		} catch (Throwable $ex) {
			throw new Exceptions\InvalidState('An error occurred: ' . $ex->getMessage(), $ex->getCode(), $ex);
		}
	}

	/**
	 * @param callable(): T $callback
	 *
	 * @return T
	 *
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 *
	 * @template T
	 */
	public function transaction(callable $callback)
	{
		try {
			$this->pingAndReconnect();

			// Start transaction connection to the database
			$this->getConnection()->beginTransaction();

			$result = $callback();

			// Commit all changes into database
			$this->getConnection()->commit();

			return $result;
		} catch (Throwable $ex) {
			// Revert all changes when error occur
			if ($this->getConnection()->isTransactionActive()) {
				$this->getConnection()->rollBack();
			}

			throw new Exceptions\InvalidState('An error occurred: ' . $ex->getMessage(), $ex->getCode(), $ex);
		}
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function getConnection(): DBAL\Connection
	{
		$em = $this->getEntityManager();

		if ($em instanceof ORM\EntityManagerInterface) {
			return $em->getConnection();
		}

		throw new Exceptions\Runtime('Entity manager could not be loaded');
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	private function pingAndReconnect(): void
	{
		try {
			// Check if ping to DB is possible...
			if (!$this->database->ping()) {
				// ...if not, try to reconnect
				$this->database->reconnect();

				// ...and ping again
				if (!$this->database->ping()) {
					throw new Exceptions\Runtime('Connection to database could not be established');
				}

				$this->database->clear();
			}
		} catch (Throwable $ex) {
			throw new Exceptions\Runtime(
				'Connection to database could not be reestablished',
				$ex->getCode(),
				$ex,
			);
		}
	}

	private function getEntityManager(): ORM\EntityManagerInterface|null
	{
		$em = $this->managerRegistry->getManager();

		if ($em instanceof ORM\EntityManagerInterface) {
			if (!$em->isOpen()) {
				$this->managerRegistry->resetManager();

				$em = $this->managerRegistry->getManager();
			}

			if ($em instanceof ORM\EntityManagerInterface) {
				return $em;
			}
		}

		return null;
	}

}
