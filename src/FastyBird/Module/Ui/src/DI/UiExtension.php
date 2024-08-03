<?php declare(strict_types = 1);

/**
 * UiExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           02.12.20
 */

namespace FastyBird\Module\Ui\DI;

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\Library\Application\Boot as ApplicationBoot;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Commands;
use FastyBird\Module\Ui\Controllers;
use FastyBird\Module\Ui\Hydrators;
use FastyBird\Module\Ui\Middleware;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Router;
use FastyBird\Module\Ui\Schemas;
use FastyBird\Module\Ui\Subscribers;
use IPub\SlimRouter\Routing as SlimRouterRouting;
use Nette\DI;
use Nette\Schema;
use Nettrine\ORM as NettrineORM;
use stdClass;
use function array_keys;
use function array_pop;
use function assert;
use const DIRECTORY_SEPARATOR;

/**
 * UI module
 *
 * @package        FastyBird:UIModule!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class UiExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbUiModule';

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

		$logger = $builder->addDefinition($this->prefix('logger'), new DI\Definitions\ServiceDefinition())
			->setType(Ui\Logger::class)
			->setAutowired(false);

		/**
		 * ROUTE MIDDLEWARES & ROUTING
		 */

		$builder->addDefinition($this->prefix('middleware.access'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Access::class);

		$builder->addDefinition($this->prefix('router.api.routes'), new DI\Definitions\ServiceDefinition())
			->setType(Router\ApiRoutes::class)
			->setArguments(['usePrefix' => $configuration->apiPrefix]);

		$builder->addDefinition($this->prefix('router.validator'), new DI\Definitions\ServiceDefinition())
			->setType(Router\Validator::class);

		/**
		 * MODELS - DOCTRINE
		 */

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.dashboards'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Dashboards\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.dashboards'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Dashboards\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.tabs'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Dashboards\Tabs\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.tabs'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Dashboards\Tabs\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.groups'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Groups\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.groups'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Groups\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.widgets'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.widgets'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.dataSources'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\DataSources\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.dataSources'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\DataSources\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.displays'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Widgets\Displays\Manager::class);

		/**
		 * SUBSCRIBERS
		 */

		$builder->addDefinition($this->prefix('subscribers.dashboardEntity'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\DashboardEntity::class);

		/**
		 * API CONTROLLERS
		 */

		$builder->addDefinition($this->prefix('controllers.dashboards'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\DashboardsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.tabs'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\TabsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.groups'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\GroupsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.widgets'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\WidgetsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.dataSources'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\DataSourcesV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.display'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\DisplayV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition($this->prefix('schemas.dashboard'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Dashboards\Dashboard::class);

		$builder->addDefinition($this->prefix('schemas.tabs'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Dashboards\Tabs\Tab::class);

		$builder->addDefinition($this->prefix('schemas.group'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Groups\Group::class);

		$builder->addDefinition($this->prefix('schemas.widgets.analogActuator'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\AnalogActuator::class);

		$builder->addDefinition($this->prefix('schemas.widgets.analogSensor'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\AnalogSensor::class);

		$builder->addDefinition(
			$this->prefix('schemas.widgets.digitalActuator'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\DigitalActuator::class);

		$builder->addDefinition($this->prefix('schemas.widgets.digitalSensor'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\DigitalSensor::class);

		$builder->addDefinition(
			$this->prefix('schemas.dataSources.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\DataSources\ChannelProperty::class);

		$builder->addDefinition($this->prefix('schemas.display.analogValue'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\AnalogValue::class);

		$builder->addDefinition($this->prefix('schemas.display.button'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\Button::class);

		$builder->addDefinition($this->prefix('schemas.display.chartGraph'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\ChartGraph::class);

		$builder->addDefinition($this->prefix('schemas.display.digitalValue'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\DigitalValue::class);

		$builder->addDefinition($this->prefix('schemas.display.gauge'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\Gauge::class);

		$builder->addDefinition($this->prefix('schemas.display.groupedButton'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\GroupedButton::class);

		$builder->addDefinition($this->prefix('schemas.display.slider'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Widgets\Display\Slider::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition($this->prefix('hydrators.dashboard'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Dashboards\Dashboard::class);

		$builder->addDefinition($this->prefix('hydrators.tabs'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Dashboards\Tabs\Tab::class);

		$builder->addDefinition($this->prefix('hydrators.group'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Groups\Group::class);

		$builder->addDefinition(
			$this->prefix('hydrators.widgets.analogActuator'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\AnalogActuator::class);

		$builder->addDefinition($this->prefix('hydrators.widgets.analogSensor'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\AnalogSensor::class);

		$builder->addDefinition(
			$this->prefix('hydrators.widgets.digitalActuator'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DigitalActuator::class);

		$builder->addDefinition(
			$this->prefix('hydrators.widgets.digitalSensor'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DigitalSensor::class);

		$builder->addDefinition(
			$this->prefix('hydrators.dataSources.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DataSources\ChannelProperty::class);

		$builder->addDefinition($this->prefix('hydrators.widgets.analogValue'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\Displays\AnalogValue::class);

		$builder->addDefinition($this->prefix('hydrators.widgets.button'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\Displays\Button::class);

		$builder->addDefinition($this->prefix('hydrators.widgets.chartGraph'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\Displays\ChartGraph::class);

		$builder->addDefinition($this->prefix('hydrators.widgets.digitalValue'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\Displays\DigitalValue::class);

		$builder->addDefinition($this->prefix('hydrators.widgets.gauge'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\Displays\Gauge::class);

		$builder->addDefinition(
			$this->prefix('hydrators.widgets.groupedButton'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\Displays\GroupedButton::class);

		$builder->addDefinition($this->prefix('hydrators.widgets.slider'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Widgets\Displays\Slider::class);

		/**
		 * COMMANDS
		 */

		// Console commands
		$builder->addDefinition($this->prefix('commands.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Install::class)
			->setArguments([
				'logger' => $logger,
			]);
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * DOCTRINE ENTITIES
		 */

		$services = $builder->findByTag(NettrineORM\DI\OrmAttributesExtension::DRIVER_TAG);

		if ($services !== []) {
			$services = array_keys($services);
			$ormAttributeDriverServiceName = array_pop($services);

			$ormAttributeDriverService = $builder->getDefinition($ormAttributeDriverServiceName);

			if ($ormAttributeDriverService instanceof DI\Definitions\ServiceDefinition) {
				$ormAttributeDriverService->addSetup(
					'addPaths',
					[[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities']],
				);

				$ormAttributeDriverChainService = $builder->getDefinitionByType(
					Persistence\Mapping\Driver\MappingDriverChain::class,
				);

				if ($ormAttributeDriverChainService instanceof DI\Definitions\ServiceDefinition) {
					$ormAttributeDriverChainService->addSetup('addDriver', [
						$ormAttributeDriverService,
						'FastyBird\Module\Ui\Entities',
					]);
				}
			}
		}

		/**
		 * ROUTES
		 */

		$routerService = $builder->getDefinitionByType(SlimRouterRouting\Router::class);

		if ($routerService instanceof DI\Definitions\ServiceDefinition) {
			$routerService->addSetup('?->registerRoutes(?)', [
				$builder->getDefinitionByType(Router\ApiRoutes::class),
				$routerService,
			]);
		}
	}

	/**
	 * @return array<string>
	 */
	public function getTranslationResources(): array
	{
		return [
			__DIR__ . '/../Translations',
		];
	}

}
