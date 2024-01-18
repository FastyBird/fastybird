<?php declare(strict_types = 1);

/**
 * Database.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           15.04.20
 */

namespace FastyBird\Library\Application\Helpers;

use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Library\Application\Exceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;
use Psr\Log;
use Throwable;
use function gc_collect_cycles;

/**
 * Database connection helpers
 *
 * @package        FastyBird:Application!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Database
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Persistence\ManagerRegistry|null $managerRegistry = null,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function ping(): bool
	{
		$connection = $this->getConnection();

		if ($connection !== null) {
			try {
				$connection->executeQuery($connection->getDatabasePlatform()
					->getDummySelectSQL(), [], []);

			} catch (DBAL\Exception) {
				return false;
			}

			return true;
		}

		throw new Exceptions\InvalidState('Database connection not found');
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DBAL\Exception
	 */
	public function reconnect(): void
	{
		$connection = $this->getConnection();

		if ($connection !== null) {
			$connection->close();
			$connection->connect();

			return;
		}

		throw new Exceptions\InvalidState('Invalid database connection');
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function clear(): void
	{
		if ($this->managerRegistry === null) {
			throw new Exceptions\InvalidState('Doctrine Manager registry service is missing');
		}

		foreach ($this->managerRegistry->getManagers() as $name => $manager) {
			if (!$manager instanceof ORM\EntityManagerInterface) {
				continue;
			}

			// Flushing and then clearing Doctrine's entity manager allows
			// for more memory to be released by PHP
			try {
				if ($manager->isOpen()) {
					$manager->flush();
				}
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error('An unhandled error occurred during flushing entity manager', [
					'source' => MetadataTypes\ModuleSource::NOT_SPECIFIED,
					'type' => 'helper',
					'exception' => Logger::buildException($ex),
				]);
			}

			try {
				$manager->getConnection()->close();
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error('An unhandled error occurred during closing entity manager', [
					'source' => MetadataTypes\ModuleSource::NOT_SPECIFIED,
					'type' => 'helper',
					'exception' => Logger::buildException($ex),
				]);
			}

			try {
				$manager->clear();
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error('An unhandled error occurred during clearing entity manager', [
					'source' => MetadataTypes\ModuleSource::NOT_SPECIFIED,
					'type' => 'helper',
					'exception' => Logger::buildException($ex),
				]);
			}

			if (!$manager->isOpen()) {
				$this->managerRegistry->resetManager($name);
			}
		}

		// Just in case PHP would choose not to run garbage collection,
		// we run it manually at the end of each batch so that memory is
		// regularly released
		gc_collect_cycles();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getConnection(): DBAL\Connection|null
	{
		$em = $this->getEntityManager();

		if ($em instanceof ORM\EntityManagerInterface) {
			return $em->getConnection();
		}

		return null;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getEntityManager(): ORM\EntityManagerInterface|null
	{
		if ($this->managerRegistry === null) {
			throw new Exceptions\InvalidState('Doctrine Manager registry service is missing');
		}

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
