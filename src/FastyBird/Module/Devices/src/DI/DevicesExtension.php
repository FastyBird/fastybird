<?php declare(strict_types = 1);

/**
 * DevicesModuleExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           25.11.20
 */

namespace FastyBird\Module\Devices\DI;

use Doctrine\Persistence;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Exchange\DI as ExchangeDI;
use FastyBird\Library\Exchange\Exchange as ExchangeExchange;
use FastyBird\Module\Devices\Commands;
use FastyBird\Module\Devices\Connectors;
use FastyBird\Module\Devices\Consumers;
use FastyBird\Module\Devices\Controllers;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Hydrators;
use FastyBird\Module\Devices\Middleware;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Router;
use FastyBird\Module\Devices\Schemas;
use FastyBird\Module\Devices\Subscribers;
use FastyBird\Module\Devices\Utilities;
use IPub\DoctrineCrud;
use IPub\SlimRouter\Routing as SlimRouterRouting;
use Nette;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use stdClass;
use function assert;
use function class_exists;
use function is_string;
use function ucfirst;
use const DIRECTORY_SEPARATOR;

/**
 * Devices module extension container
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicesExtension extends DI\CompilerExtension
{

	public const NAME = 'fbDevicesModule';

	public const CONNECTOR_TYPE_TAG = 'connector_type';

	public static function register(
		BootstrapBoot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			BootstrapBoot\Configurator $config,
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

		$builder->addDefinition($this->prefix('middlewares.access'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Access::class);

		$builder->addDefinition($this->prefix('router.api.routes'), new DI\Definitions\ServiceDefinition())
			->setType(Router\ApiRoutes::class)
			->setArguments(['usePrefix' => $configuration->apiPrefix]);

		if (class_exists('IPub\WebSockets\DI\WebSocketsExtension')) {
			$builder->addDefinition($this->prefix('router.sockets.routes'), new DI\Definitions\ServiceDefinition())
				->setType(Router\SocketRoutes::class)
				->addTag('ipub.websockets.routes');
		}

		$builder->addDefinition($this->prefix('router.validator'), new DI\Definitions\ServiceDefinition())
			->setType(Router\Validator::class);

		$builder->addDefinition($this->prefix('models.devicesRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Devices\DevicesRepository::class);

		$builder->addDefinition(
			$this->prefix('models.devicePropertiesRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Devices\Properties\PropertiesRepository::class);

		$builder->addDefinition(
			$this->prefix('models.deviceControlsRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Devices\Controls\ControlsRepository::class);

		$builder->addDefinition($this->prefix('models.channelsRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Channels\ChannelsRepository::class);

		$builder->addDefinition(
			$this->prefix('models.channelPropertiesRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Channels\Properties\PropertiesRepository::class);

		$builder->addDefinition(
			$this->prefix('models.channelControlsRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Channels\Controls\ControlsRepository::class);

		$builder->addDefinition($this->prefix('models.connectorsRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Connectors\ConnectorsRepository::class);

		$builder->addDefinition(
			$this->prefix('models.connectorPropertiesRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Connectors\Properties\PropertiesRepository::class);

		$builder->addDefinition(
			$this->prefix('models.connectorControlsRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Connectors\Controls\ControlsRepository::class);

		$builder->addDefinition($this->prefix('models.devicesManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Devices\DevicesManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition(
			$this->prefix('models.devicesPropertiesManager'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Devices\Properties\PropertiesManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('models.devicesControlsManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Devices\Controls\ControlsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('models.channelsManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Channels\ChannelsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition(
			$this->prefix('models.channelsPropertiesManager'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Channels\Properties\PropertiesManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('models.channelsControlsManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Channels\Controls\ControlsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('models.connectorsManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Entities\Connectors\ConnectorsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition(
			$this->prefix('models.connectorsPropertiesManager'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Connectors\Properties\PropertiesManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition(
			$this->prefix('models.connectorsControlsManager'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Connectors\Controls\ControlsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('models.configuration.builder'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Configuration\Builder::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.connectorsRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Connectors\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.connectorsControlsRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Connectors\Controls\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.devicesRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Devices\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.devicesControlsRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Devices\Controls\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.channelsRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Channels\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.channelsPropertiesRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Channels\Properties\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.channelsControlsRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Channels\Controls\Repository::class);

		$builder->addDefinition($this->prefix('subscribers.entities'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\ModuleEntities::class);

		$builder->addDefinition($this->prefix('subscribers.variableProperties'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\VariablePropertiesEntities::class);

		$builder->addDefinition($this->prefix('subscribers.states'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\StateEntities::class);

		$builder->addDefinition($this->prefix('controllers.devices'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\DevicesV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.deviceChildren'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\DeviceChildrenV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.deviceParents'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\DeviceParentsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.deviceProperties'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\DevicePropertiesV1::class)
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('controllers.devicePropertyChildren'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\DevicePropertyChildrenV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.deviceControls'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\DeviceControlsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.channels'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\ChannelsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.channelProperties'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\ChannelPropertiesV1::class)
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('controllers.channelPropertyChildren'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\ChannelPropertyChildrenV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.channelControls'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\ChannelControlsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.connectors'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\ConnectorsV1::class)
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('controllers.connectorProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\ConnectorPropertiesV1::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('controllers.connectorsControls'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\ConnectorControlsV1::class)
			->addTag('nette.inject');

		if (class_exists('IPub\WebSockets\DI\WebSocketsExtension')) {
			$builder->addDefinition($this->prefix('controllers.exchange'), new DI\Definitions\ServiceDefinition())
				->setType(Controllers\ExchangeV1::class)
				->addTag('nette.inject');
		}

		$builder->addDefinition($this->prefix('schemas.device.blank'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Devices\Blank::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.property.dynamic'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\Properties\Dynamic::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.property.variable'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\Properties\Variable::class);

		$builder->addDefinition($this->prefix('schemas.device.property.mapped'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Devices\Properties\Mapped::class);

		$builder->addDefinition($this->prefix('schemas.device.control'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Devices\Controls\Control::class);

		$builder->addDefinition($this->prefix('schemas.channel'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\Channel::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.property.dynamic'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Properties\Dynamic::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.property.variable'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Properties\Variable::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.property.mapped'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Properties\Mapped::class);

		$builder->addDefinition($this->prefix('schemas.control'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\Controls\Control::class);

		$builder->addDefinition($this->prefix('schemas.connector.blank'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Connectors\Blank::class);

		$builder->addDefinition(
			$this->prefix('schemas.connector.property.dynamic'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Connectors\Properties\Dynamic::class);

		$builder->addDefinition(
			$this->prefix('schemas.connector.property.variable'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Connectors\Properties\Variable::class);

		$builder->addDefinition($this->prefix('schemas.connector.controls'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Connectors\Controls\Control::class);

		$builder->addDefinition($this->prefix('hydrators.device.blank'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Devices\Blank::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.property.dynamic'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Properties\DeviceDynamic::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.property.variable'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Properties\DeviceVariable::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.property.mapped'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Properties\DeviceMapped::class);

		$builder->addDefinition($this->prefix('hydrators.channel'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Channels\Channel::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.property.dynamic'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Properties\ChannelDynamic::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.property.variable'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Properties\ChannelVariable::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.property.mapped'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Properties\ChannelMapped::class);

		$builder->addDefinition($this->prefix('hydrators.connectors.blank'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Connectors\Blank::class);

		$builder->addDefinition(
			$this->prefix('hydrators.connector.property.dynamic'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Properties\ConnectorDynamic::class);

		$builder->addDefinition(
			$this->prefix('hydrators.connector.property.variable'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Properties\ConnectorVariable::class);

		$builder->addDefinition(
			$this->prefix('states.repositories.connectors.properties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\ConnectorPropertiesRepository::class);

		$builder->addDefinition(
			$this->prefix('states.repositories.devices.properties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\DevicePropertiesRepository::class);

		$builder->addDefinition(
			$this->prefix('states.repositories.channels.properties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\ChannelPropertiesRepository::class);

		$builder->addDefinition(
			$this->prefix('states.managers.connectors.properties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\ConnectorPropertiesManager::class);

		$builder->addDefinition(
			$this->prefix('states.managers.devices.properties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\DevicePropertiesManager::class);

		$builder->addDefinition(
			$this->prefix('states.managers.channels.properties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\ChannelPropertiesManager::class);

		$builder->addDefinition(
			$this->prefix('utilities.database'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Utilities\Database::class);

		$builder->addDefinition(
			$this->prefix('utilities.channels.states'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Utilities\ChannelPropertiesStates::class);

		$builder->addDefinition(
			$this->prefix('utilities.connectors.states'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Utilities\ConnectorPropertiesStates::class);

		$builder->addDefinition(
			$this->prefix('utilities.devices.states'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Utilities\DevicePropertiesStates::class);

		$builder->addDefinition(
			$this->prefix('utilities.devices.connection'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Utilities\DeviceConnection::class);

		$builder->addDefinition(
			$this->prefix('utilities.connector.connection'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Utilities\ConnectorConnection::class);

		$builder->addDefinition($this->prefix('exchange.consumer.states'), new DI\Definitions\ServiceDefinition())
			->setType(Consumers\State::class)
			->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATE, false);

		if (
			$builder->findByType('IPub\WebSockets\Router\LinkGenerator') !== []
			&& $builder->findByType('IPub\WebSocketsWAMP\Topics\IStorage') !== []
		) {
			$builder->addDefinition($this->prefix('exchange.consumer.sockets'), new DI\Definitions\ServiceDefinition())
				->setType(Consumers\Sockets::class)
				->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATE, false);
		}

		$builder->addDefinition($this->prefix('commands.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Initialize::class);

		$builder->addDefinition($this->prefix('commands.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Connector::class)
			->setArguments([
				'exchangeFactories' => $builder->findByType(ExchangeExchange\Factory::class),
			]);

		$builder->addDefinition($this->prefix('commands.exchange'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Exchange::class)
			->setArguments([
				'exchangeFactories' => $builder->findByType(ExchangeExchange\Factory::class),
			]);
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
				'FastyBird\Module\Devices\Entities',
			]);
		}

		/**
		 * ApiRoutes
		 */

		$routerService = $builder->getDefinitionByType(SlimRouterRouting\Router::class);

		if ($routerService instanceof DI\Definitions\ServiceDefinition) {
			$routerService->addSetup('?->registerRoutes(?)', [
				$builder->getDefinitionByType(Router\ApiRoutes::class),
				$routerService,
			]);
		}

		/**
		 * Connectors
		 */

		$connectorCommand = $builder->getDefinitionByType(Commands\Connector::class);

		if ($connectorCommand instanceof DI\Definitions\ServiceDefinition) {
			$connectorsExecutorsFactoriesServices = $builder->findByType(Connectors\ConnectorFactory::class);

			foreach ($connectorsExecutorsFactoriesServices as $connectorExecutorFactoryService) {
				if (is_string($connectorExecutorFactoryService->getTag(self::CONNECTOR_TYPE_TAG))) {
					$connectorCommand->addSetup('?->attach(?, ?)', [
						$connectorCommand,
						$connectorExecutorFactoryService,
						$connectorExecutorFactoryService->getTag(self::CONNECTOR_TYPE_TAG),
					]);
				}
			}
		}

		/**
		 * WebSockets
		 */

		if (class_exists('IPub\WebSockets\DI\WebSocketsExtension')) {
			try {
				$wsControllerFactoryService = $builder->getDefinitionByType(
					'IPub\WebSockets\Application\Controller\IControllerFactory',
				);
				assert($wsControllerFactoryService instanceof DI\Definitions\ServiceDefinition);

				$wsControllerFactoryService->addSetup(
					'setMapping',
					[
						[
							'DevicesModule' => ['FastyBird\\Module\\Devices\\Controllers', '*', '*V1'],
						],
					],
				);

				$consumerService = $builder->getDefinitionByType(ExchangeConsumers\Container::class);
				assert($consumerService instanceof DI\Definitions\ServiceDefinition);

				$wsServerService = $builder->getDefinitionByType('IPub\WebSockets\Server\Server');
				assert($wsServerService instanceof DI\Definitions\ServiceDefinition);

				$wsServerService->addSetup(
					'?->onCreate[] = function() {?->enable(?);}',
					[
						'@self',
						$consumerService,
						Consumers\Sockets::class,
					],
				);

			} catch (DI\MissingServiceException) {
				// Extension is not registered
			}
		}
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function afterCompile(PhpGenerator\ClassType $class): void
	{
		$builder = $this->getContainerBuilder();

		$entityFactoryServiceName = $builder->getByType(DoctrineCrud\Crud\IEntityCrudFactory::class, true);

		$devicesManagerService = $class->getMethod('createService' . ucfirst($this->name) . '__models__devicesManager');
		$devicesManagerService->setBody(
			'return new ' . Models\Entities\Devices\DevicesManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Devices\Device::class . '\'));',
		);

		$devicesPropertiesManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__devicesPropertiesManager',
		);
		$devicesPropertiesManagerService->setBody(
			'return new ' . Models\Entities\Devices\Properties\PropertiesManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Devices\Properties\Property::class . '\'));',
		);

		$devicesControlsManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__devicesControlsManager',
		);
		$devicesControlsManagerService->setBody(
			'return new ' . Models\Entities\Devices\Controls\ControlsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Devices\Controls\Control::class . '\'));',
		);

		$channelsManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__channelsManager',
		);
		$channelsManagerService->setBody(
			'return new ' . Models\Entities\Channels\ChannelsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Channels\Channel::class . '\'));',
		);

		$channelsPropertiesManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__channelsPropertiesManager',
		);
		$channelsPropertiesManagerService->setBody(
			'return new ' . Models\Entities\Channels\Properties\PropertiesManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Channels\Properties\Property::class . '\'));',
		);

		$channelsControlsManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__channelsControlsManager',
		);
		$channelsControlsManagerService->setBody(
			'return new ' . Models\Entities\Channels\Controls\ControlsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Channels\Controls\Control::class . '\'));',
		);

		$connectorsManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__connectorsManager',
		);
		$connectorsManagerService->setBody(
			'return new ' . Models\Entities\Connectors\ConnectorsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Connectors\Connector::class . '\'));',
		);

		$connectorsPropertiesManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__connectorsPropertiesManager',
		);
		$connectorsPropertiesManagerService->setBody(
			'return new ' . Models\Entities\Connectors\Properties\PropertiesManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Connectors\Properties\Property::class . '\'));',
		);

		$connectorsControlsManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__connectorsControlsManager',
		);
		$connectorsControlsManagerService->setBody(
			'return new ' . Models\Entities\Connectors\Controls\ControlsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Connectors\Controls\Control::class . '\'));',
		);
	}

}
