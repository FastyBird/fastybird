<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Commands;

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Module\Accounts\Commands;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Helpers;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Tests\Cases\Unit\DbTestCase;
use FastyBird\SimpleAuth;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use RuntimeException;
use Symfony\Component\Console;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateAccountTest extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Console\Exception\CommandNotFoundException
	 * @throws Console\Exception\LogicException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testExecute(): void
	{
		$accountsManager = $this->getContainer()->getByType(Models\Accounts\AccountsManager::class);

		$emailsRepository = $this->getContainer()->getByType(Models\Emails\EmailsRepository::class);

		$emailsManager = $this->getContainer()->getByType(Models\Emails\EmailsManager::class);

		$identitiesManager = $this->getContainer()->getByType(Models\Identities\IdentitiesManager::class);

		$rolesRepository = $this->getContainer()->getByType(Models\Roles\RolesRepository::class);

		$identitiesRepository = $this->getContainer()->getByType(Models\Identities\IdentitiesRepository::class);

		$translator = $this->getContainer()->getByType(Translation\Translator::class);

		$managerRegistry = $this->getContainer()->getByType(Persistence\ManagerRegistry::class);

		$application = new Application();
		$application->add(new Commands\Accounts\Create(
			$accountsManager,
			$emailsRepository,
			$emailsManager,
			$identitiesManager,
			$rolesRepository,
			$translator,
			$managerRegistry,
		));

		$command = $application->get(Commands\Accounts\Create::NAME);

		$commandTester = new CommandTester($command);
		$result = $commandTester->execute([
			'lastName' => 'Balboa',
			'firstName' => 'Rocky',
			'email' => 'rocky@balboa.com',
			'password' => 'someRandomPassword',
			'role' => SimpleAuth\Constants::ROLE_USER,
		]);

		self::assertSame(0, $result);

		$findEmailQuery = new Queries\FindEmails();
		$findEmailQuery->byAddress('rocky@balboa.com');

		$email = $emailsRepository->findOneBy($findEmailQuery);

		self::assertNotNull($email);
		self::assertSame('Balboa Rocky', $email->getAccount()->getName());

		$findIdentity = new Queries\FindIdentities();
		$findIdentity->byUid('rocky@balboa.com');

		$identity = $identitiesRepository->findOneBy($findIdentity);

		self::assertNotNull($identity);

		$password = new Helpers\Password(
			null,
			'someRandomPassword',
			$identity->getSalt(),
		);

		self::assertSame($password->getHash(), $identity->getPassword()->getHash());
	}

}
