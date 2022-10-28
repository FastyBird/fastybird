<?php declare(strict_types = 1);

/**
 * AccountsModuleExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           30.11.20
 */

namespace FastyBird\Module\Accounts\DI;

use Doctrine\Persistence;
use FastyBird\Bootstrap;
use FastyBird\Module\Accounts\Commands;
use FastyBird\Module\Accounts\Controllers;
use FastyBird\Module\Accounts\Entities;
use FastyBird\Module\Accounts\Helpers;
use FastyBird\Module\Accounts\Hydrators;
use FastyBird\Module\Accounts\Middleware;
use FastyBird\Module\Accounts\Models;
use FastyBird\Module\Accounts\Router;
use FastyBird\Module\Accounts\Schemas;
use FastyBird\Module\Accounts\Security;
use FastyBird\Module\Accounts\Subscribers;
use IPub\DoctrineCrud;
use IPub\SlimRouter\Routing as SlimRouterRouting;
use Nette;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use stdClass;
use function assert;
use function ucfirst;
use const DIRECTORY_SEPARATOR;

/**
 * Accounts module extension container
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class AccountsExtension extends DI\CompilerExtension
{

	public const NAME = 'fbAccountsModule';

	public static function register(
		Nette\Configurator|Bootstrap\Boot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator|Bootstrap\Boot\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new AccountsExtension());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'apiPrefix' => Schema\Expect::bool(false),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition($this->prefix('middlewares.access'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Access::class);

		$builder->addDefinition($this->prefix('middlewares.urlFormat'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\UrlFormat::class)
			->addTag('middleware');

		$builder->addDefinition($this->prefix('router.routes'), new DI\Definitions\ServiceDefinition())
			->setType(Router\Routes::class)
			->setArguments(['usePrefix' => $configuration->apiPrefix]);

		$builder->addDefinition($this->prefix('router.validator'), new DI\Definitions\ServiceDefinition())
			->setType(Router\Validator::class);

		$builder->addDefinition($this->prefix('commands.create'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Accounts\Create::class);

		$builder->addDefinition($this->prefix('commands.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Initialize::class);

		$builder->addDefinition($this->prefix('models.accountsRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Accounts\AccountsRepository::class);

		$builder->addDefinition($this->prefix('models.emailsRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Emails\EmailsRepository::class);

		$builder->addDefinition($this->prefix('models.identitiesRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Identities\IdentitiesRepository::class);

		$builder->addDefinition($this->prefix('models.rolesRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Roles\RolesRepository::class);

		// Database managers
		$builder->addDefinition($this->prefix('models.accountsManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Accounts\AccountsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('models.emailsManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Emails\EmailsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('models.identitiesManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Identities\IdentitiesManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('models.rolesManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Roles\RolesManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('subscribers.entities'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\ModuleEntities::class);

		$builder->addDefinition($this->prefix('subscribers.accountEntity'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\AccountEntity::class);

		$builder->addDefinition($this->prefix('subscribers.emailEntity'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\EmailEntity::class);

		$builder->addDefinition($this->prefix('controllers.session'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\SessionV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.account'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\AccountV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.accountEmails'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\AccountEmailsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.accountIdentities'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\AccountIdentitiesV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.accounts'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\AccountsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.emails'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\EmailsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.identities'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\IdentitiesV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.roles'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\RolesV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.roleChildren'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\RoleChildrenV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.public'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\PublicV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('schemas.account'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Accounts\Account::class);

		$builder->addDefinition($this->prefix('schemas.email'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Emails\Email::class);

		$builder->addDefinition($this->prefix('schemas.identity'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Identities\Identity::class);

		$builder->addDefinition($this->prefix('schemas.role'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Roles\Role::class);

		$builder->addDefinition($this->prefix('schemas.session'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Sessions\Session::class);

		$builder->addDefinition($this->prefix('hydrators.accounts.profile'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Accounts\ProfileAccount::class);

		$builder->addDefinition($this->prefix('hydrators.accounts'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Accounts\Account::class);

		$builder->addDefinition($this->prefix('hydrators.emails.profile'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Emails\ProfileEmail::class);

		$builder->addDefinition($this->prefix('hydrators.emails.email'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Emails\Email::class);

		$builder->addDefinition($this->prefix('hydrators.identities.profile'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Identities\Identity::class);

		$builder->addDefinition($this->prefix('hydrators.role'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Roles\Role::class);

		$builder->addDefinition($this->prefix('security.hash'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\SecurityHash::class);

		$builder->addDefinition($this->prefix('security.identityFactory'), new DI\Definitions\ServiceDefinition())
			->setType(Security\IdentityFactory::class);

		$builder->addDefinition($this->prefix('security.authenticator'), new DI\Definitions\ServiceDefinition())
			->setType(Security\Authenticator::class);

		$builder->addDefinition('security.user', new DI\Definitions\ServiceDefinition())
			->setType(Security\User::class);
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * Doctrine entities
		 */

		$ormAnnotationDriverService = $builder->getDefinition('nettrineOrmAnnotations.annotationDriver');

		if ($ormAnnotationDriverService instanceof DI\Definitions\ServiceDefinition) {
			$ormAnnotationDriverService->addSetup(
				'addPaths',
				[[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities']],
			);
		}

		$ormAnnotationDriverChainService = $builder->getDefinitionByType(
			Persistence\Mapping\Driver\MappingDriverChain::class,
		);

		if ($ormAnnotationDriverChainService instanceof DI\Definitions\ServiceDefinition) {
			$ormAnnotationDriverChainService->addSetup('addDriver', [
				$ormAnnotationDriverService,
				'FastyBird\Module\Accounts\Entities',
			]);
		}

		/**
		 * Routes
		 */

		$routerService = $builder->getDefinitionByType(SlimRouterRouting\Router::class);

		if ($routerService instanceof DI\Definitions\ServiceDefinition) {
			$routerService->addSetup(
				'?->registerRoutes(?)',
				[$builder->getDefinitionByType(Router\Routes::class), $routerService],
			);
		}
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	public function afterCompile(PhpGenerator\ClassType $class): void
	{
		$builder = $this->getContainerBuilder();

		$entityFactoryServiceName = $builder->getByType(DoctrineCrud\Crud\IEntityCrudFactory::class, true);

		$accountsManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__accountsManager',
		);
		$accountsManagerService->setBody(
			'return new ' . Models\Accounts\AccountsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Accounts\Account::class . '\'));',
		);

		$emailsManagerService = $class->getMethod('createService' . ucfirst($this->name) . '__models__emailsManager');
		$emailsManagerService->setBody(
			'return new ' . Models\Emails\EmailsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Emails\Email::class . '\'));',
		);

		$identitiesManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__identitiesManager',
		);
		$identitiesManagerService->setBody(
			'return new ' . Models\Identities\IdentitiesManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Identities\Identity::class . '\'));',
		);

		$rolesManagerService = $class->getMethod('createService' . ucfirst($this->name) . '__models__rolesManager');
		$rolesManagerService->setBody(
			'return new ' . Models\Roles\RolesManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Roles\Role::class . '\'));',
		);
	}

}
