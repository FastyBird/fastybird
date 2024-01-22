<?php declare(strict_types = 1);

/**
 * TriggersExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           29.11.20
 */

namespace FastyBird\Module\Triggers\DI;

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\Library\Application\Boot as ApplicationBoot;
use FastyBird\Module\Triggers\Commands;
use FastyBird\Module\Triggers\Controllers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Hydrators;
use FastyBird\Module\Triggers\Middleware;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Router;
use FastyBird\Module\Triggers\Schemas;
use FastyBird\Module\Triggers\Subscribers;
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
 * Triggers module extension container
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TriggersExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbTriggersModule';

	public const TRIGGER_TYPE_TAG = 'trigger_type';

	public static function register(
		ApplicationBoot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			ApplicationBoot\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'apiPrefix' => Schema\Expect::bool(true),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition($this->prefix('middleware.access'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Access::class);

		$builder->addDefinition($this->prefix('router.routes'), new DI\Definitions\ServiceDefinition())
			->setType(Router\ApiRoutes::class)
			->setArguments(['usePrefix' => $configuration->apiPrefix]);

		$builder->addDefinition($this->prefix('router.validator'), new DI\Definitions\ServiceDefinition())
			->setType(Router\Validator::class);

		$builder->addDefinition($this->prefix('models.triggersRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Triggers\TriggersRepository::class);

		$builder->addDefinition(
			$this->prefix('models.triggeControlsRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Triggers\Controls\ControlsRepository::class);

		$builder->addDefinition($this->prefix('models.actionsRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Actions\ActionsRepository::class);

		$builder->addDefinition($this->prefix('models.conditionsRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Conditions\ConditionsRepository::class);

		$builder->addDefinition($this->prefix('models.notificationsRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Notifications\NotificationsRepository::class);

		$builder->addDefinition($this->prefix('models.triggersManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Triggers\TriggersManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('models.triggersControlsManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Triggers\Controls\ControlsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('models.actionsManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Actions\ActionsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('models.conditionsManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Conditions\ConditionsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('models.notificationsManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Notifications\NotificationsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('subscribers.notificationEntity'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\NotificationEntity::class);

		$builder->addDefinition($this->prefix('subscribers.entities'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\ModuleEntities::class);

		$builder->addDefinition($this->prefix('controllers.triggers'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\TriggersV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.actions'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\ActionsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.conditions'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\ConditionsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.notifications'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\NotificationsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.triggersControls'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\TriggerControlsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('schemas.triggers.automatic'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Triggers\AutomaticTrigger::class);

		$builder->addDefinition($this->prefix('schemas.triggers.manual'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Triggers\ManualTrigger::class);

		$builder->addDefinition($this->prefix('schemas.trigger.control'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Triggers\Controls\Control::class);

		$builder->addDefinition($this->prefix('schemas.notifications.email'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Notifications\EmailNotification::class);

		$builder->addDefinition($this->prefix('schemas.notifications.sms'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Notifications\SmsNotification::class);

		$builder->addDefinition($this->prefix('hydrators.triggers.automatic'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Triggers\AutomaticTrigger::class);

		$builder->addDefinition($this->prefix('hydrators.triggers.manual'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Triggers\ManualTrigger::class);

		$builder->addDefinition($this->prefix('hydrators.notifications.email'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Notifications\EmailNotification::class);

		$builder->addDefinition($this->prefix('hydrators.notifications.sms'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Notifications\SmsNotification::class);

		$builder->addDefinition($this->prefix('states.repositories.actions'), new DI\Definitions\ServiceDefinition())
			->setType(Models\States\ActionsRepository::class);

		$builder->addDefinition($this->prefix('states.repositories.conditions'), new DI\Definitions\ServiceDefinition())
			->setType(Models\States\ConditionsRepository::class);

		$builder->addDefinition($this->prefix('states.managers.actions'), new DI\Definitions\ServiceDefinition())
			->setType(Models\States\ActionsManager::class);

		$builder->addDefinition($this->prefix('states.managers.conditions'), new DI\Definitions\ServiceDefinition())
			->setType(Models\States\ConditionsManager::class);

		$builder->addDefinition($this->prefix('commands.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Install::class);
	}

	/**
	 * @throws Nette\DI\MissingServiceException
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
				'FastyBird\Module\Triggers\Entities',
			]);
		}

		/**
		 * Routes
		 */

		$routerService = $builder->getDefinitionByType(SlimRouterRouting\Router::class);

		if ($routerService instanceof DI\Definitions\ServiceDefinition) {
			$routerService->addSetup(
				'?->registerRoutes(?)',
				[$builder->getDefinitionByType(Router\ApiRoutes::class), $routerService],
			);
		}
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function afterCompile(PhpGenerator\ClassType $class): void
	{
		$builder = $this->getContainerBuilder();

		$entityFactoryServiceName = $builder->getByType(DoctrineCrud\Crud\IEntityCrudFactory::class, true);

		$triggersManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__triggersManager',
		);
		$triggersManagerService->setBody(
			'return new ' . Models\Entities\Triggers\TriggersManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Triggers\Trigger::class . '\'));',
		);

		$triggersControlsManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__triggersControlsManager',
		);
		$triggersControlsManagerService->setBody(
			'return new ' . Models\Entities\Triggers\Controls\ControlsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Triggers\Controls\Control::class . '\'));',
		);

		$actionsManagerService = $class->getMethod('createService' . ucfirst($this->name) . '__models__actionsManager');
		$actionsManagerService->setBody(
			'return new ' . Models\Entities\Actions\ActionsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Actions\Action::class . '\'));',
		);

		$conditionsManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__conditionsManager',
		);
		$conditionsManagerService->setBody(
			'return new ' . Models\Entities\Conditions\ConditionsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Conditions\Condition::class . '\'));',
		);

		$notificationsManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__notificationsManager',
		);
		$notificationsManagerService->setBody(
			'return new ' . Models\Entities\Notifications\NotificationsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Notifications\Notification::class . '\'));',
		);
	}

	/**
	 * @return array<string>
	 */
	public function getTranslationResources(): array
	{
		return [
			__DIR__ . '/../Translations/',
		];
	}

}
