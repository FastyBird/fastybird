<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\DI;

use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Module\Accounts\Commands;
use FastyBird\Module\Accounts\Controllers;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Hydrators;
use FastyBird\Module\Accounts\Middleware;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Router;
use FastyBird\Module\Accounts\Schemas;
use FastyBird\Module\Accounts\Subscribers;
use FastyBird\Module\Accounts\Tests\Cases\Unit\DbTestCase;
use Nette;
use RuntimeException;

final class AccountsModuleExtensionTests extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function XtestServicesRegistration(): void
	{
		self::assertNotNull($this->getContainer()->getByType(Middleware\Access::class, false));
		self::assertNotNull($this->getContainer()->getByType(Middleware\UrlFormat::class, false));

		self::assertNotNull($this->getContainer()->getByType(Commands\Accounts\Create::class, false));
		self::assertNotNull($this->getContainer()->getByType(Commands\Initialize::class, false));

		self::assertNotNull($this->getContainer()->getByType(Subscribers\ModuleEntities::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\AccountEntity::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\EmailEntity::class, false));

		self::assertNotNull($this->getContainer()->getByType(Models\Accounts\AccountsRepository::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\Emails\EmailsRepository::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\Identities\IdentitiesRepository::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\Roles\RolesRepository::class, false));

		self::assertNotNull($this->getContainer()->getByType(Models\Accounts\AccountsManager::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\Emails\EmailsManager::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\Identities\IdentitiesManager::class, false));
		self::assertNotNull($this->getContainer()->getByType(Models\Roles\RolesManager::class, false));

		self::assertNotNull($this->getContainer()->getByType(Controllers\AccountV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\AccountEmailsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\SessionV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\AccountIdentitiesV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\AccountsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\EmailsV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\IdentitiesV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\RolesV1::class, false));
		self::assertNotNull($this->getContainer()->getByType(Controllers\RoleChildrenV1::class, false));

		self::assertNotNull($this->getContainer()->getByType(Router\Validator::class, false));
		self::assertNotNull($this->getContainer()->getByType(Router\Routes::class, false));

		self::assertNotNull($this->getContainer()->getByType(Schemas\Accounts\Account::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Emails\Email::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Sessions\Session::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Identities\Identity::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Roles\Role::class, false));

		self::assertNotNull($this->getContainer()->getByType(Hydrators\Accounts\ProfileAccount::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Accounts\Account::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Identities\Identity::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Emails\ProfileEmail::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Emails\Email::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Roles\Role::class, false));
	}

}
