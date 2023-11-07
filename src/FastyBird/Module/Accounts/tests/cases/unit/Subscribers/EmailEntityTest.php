<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Subscribers;

use Error;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Queries;
use FastyBird\Module\Accounts\Tests\Cases\Unit\DbTestCase;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use Nette\Utils;
use RuntimeException;

final class EmailEntityTest extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testChangeDefault(): void
	{
		$repository = $this->getContainer()->getByType(Models\Entities\Emails\EmailsRepository::class);

		$manager = $this->getContainer()->getByType(Models\Entities\Emails\EmailsManager::class);

		$defaultEmail = $repository->findOneByAddress('john.doe@fastybird.com');

		self::assertNotNull($defaultEmail);
		self::assertTrue($defaultEmail->isDefault());

		$email = $repository->findOneByAddress('john.doe@fastybird.ovh');

		self::assertNotNull($email);

		$manager->update($email, Utils\ArrayHash::from([
			'default' => true,
		]));

		$defaultEmail = $repository->findOneByAddress('john.doe@fastybird.com');

		self::assertNotNull($defaultEmail);
		self::assertFalse($defaultEmail->isDefault());

		$findEntityQuery = new Queries\Entities\FindIdentities();
		$findEntityQuery->forAccount($defaultEmail->getAccount());

		$repository = $this->getContainer()->getByType(Models\Entities\Identities\IdentitiesRepository::class);

		$identity = $repository->findOneBy($findEntityQuery);

		self::assertNotNull($identity);
		self::assertNotNull($identity->getAccount()->getEmail());
		self::assertSame('john.doe@fastybird.ovh', $identity->getAccount()->getEmail()->getAddress());
		self::assertSame('john.doe@fastybird.com', $identity->getUid());
	}

}
