<?php declare(strict_types = 1);

/**
 * DevicesExtension.php
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

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\Library\Application\Boot as ApplicationBoot;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Exchange\DI as ExchangeDI;
use FastyBird\Library\Exchange\Exchange as ExchangeExchange;
use FastyBird\Module\Devices;
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
use Orisai\DataSources;
use stdClass;
use function assert;
use function class_exists;
use function is_string;
use function ucfirst;
use const DIRECTORY_SEPARATOR;

/**
 * Devices module
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicesExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbDevicesModule';

	public const CONNECTOR_TYPE_TAG = 'connector_type';

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
			->setType(Devices\Logger::class)
			->setAutowired(false);

		/**
		 * ROUTE MIDDLEWARES & ROUTING
		 */

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

		/**
		 * MODELS - DOCTRINE
		 */

		// CONNECTORS
		$builder->addDefinition(
			$this->prefix('models.entities.repositories.connectors'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Connectors\ConnectorsRepository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.connectors'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Connectors\ConnectorsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.connectorsProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Connectors\Properties\PropertiesRepository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.connectorsProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Connectors\Properties\PropertiesManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.connectorsControls'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Connectors\Controls\ControlsRepository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.connectorsControls'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Connectors\Controls\ControlsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		// DEVICES
		$builder->addDefinition(
			$this->prefix('models.entities.repositories.devices'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Devices\DevicesRepository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.devices'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Devices\DevicesManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.devicesProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Devices\Properties\PropertiesRepository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.devicesProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Devices\Properties\PropertiesManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.devicesControls'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Devices\Controls\ControlsRepository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.devicesControls'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Devices\Controls\ControlsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		// CHANNELS
		$builder->addDefinition(
			$this->prefix('models.entities.repositories.channels'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Channels\ChannelsRepository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.channels'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Channels\ChannelsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.channelsProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Channels\Properties\PropertiesRepository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.channelsProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Channels\Properties\PropertiesManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition(
			$this->prefix('models.entities.repositories.channelsControls'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Channels\Controls\ControlsRepository::class);

		$builder->addDefinition(
			$this->prefix('models.entities.managers.channelsControls'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Entities\Channels\Controls\ControlsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		/**
		 * MODELS - CONFIGURATION
		 */

		$manager = new DataSources\DefaultFormatEncoderManager();
		$manager->addEncoder(new DataSources\JsonFormatEncoder());

		$dataSource = $builder->addDefinition(
			$this->prefix('models.configuration.builder.datasource'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(DataSources\DefaultDataSource::class)
			->setArguments([
				'encoderManager' => $manager,
			])
			->setAutowired(false);

		$builder->addDefinition(
			$this->prefix('models.configuration.builder'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Builder::class)
			->setArguments([
				'dataSource' => $dataSource,
			]);

		// CONNECTORS
		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.connectors'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Connectors\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.connectorsProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Connectors\Properties\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.connectorsControls'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Connectors\Controls\Repository::class);

		// DEVICES
		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.devices'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Devices\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.devicesProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Devices\Properties\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.devicesControls'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Devices\Controls\Repository::class);

		// CHANNELS
		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.channels'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Channels\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.channelsProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Channels\Properties\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.configuration.repositories.channelsControls'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\Configuration\Channels\Controls\Repository::class);

		/**
		 * MODELS - STATES
		 */

		// CONNECTORS
		$builder->addDefinition(
			$this->prefix('models.states.repositories.connectorsProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\Connectors\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.states.managers.connectorsProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\Connectors\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.states.repositories.connectorsProperties.async'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\Connectors\Async\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.states.managers.connectorsProperties.async'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\Connectors\Async\Manager::class);

		// DEVICES
		$builder->addDefinition(
			$this->prefix('models.states.repositories.devicesProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\Devices\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.states.managers.devicesProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\Devices\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.states.repositories.devicesProperties.async'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\Devices\Async\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.states.managers.devicesProperties.async'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\Devices\Async\Manager::class);

		// CHANNELS
		$builder->addDefinition(
			$this->prefix('models.states.repositories.channelsProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\Channels\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.states.managers.channelsProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\Channels\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.states.repositories.channelsProperties.async'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\Channels\Async\Repository::class);

		$builder->addDefinition(
			$this->prefix('models.states.managers.channelsProperties.async'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\Channels\Async\Manager::class);

		$builder->addDefinition(
			$this->prefix('models.states.connectors.states'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\ConnectorPropertiesManager::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('models.states.devices.states'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\DevicePropertiesManager::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('models.states.channels.states'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\States\ChannelPropertiesManager::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * SUBSCRIBERS
		 */

		$builder->addDefinition($this->prefix('subscribers.entities'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\ModuleEntities::class);

		$builder->addDefinition($this->prefix('subscribers.states'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\StateEntities::class);

		$builder->addDefinition($this->prefix('subscribers.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Connector::class);

		/**
		 * API CONTROLLERS
		 */

		// CONNECTORS
		$builder->addDefinition(
			$this->prefix('controllers.connectors'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\ConnectorsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('controllers.connectorProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\ConnectorPropertiesV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('controllers.connectorsControls'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\ConnectorControlsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		// DEVICES
		$builder->addDefinition(
			$this->prefix('controllers.devices'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\DevicesV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('controllers.deviceChildren'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\DeviceChildrenV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('controllers.deviceParents'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\DeviceParentsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('controllers.deviceProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\DevicePropertiesV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('controllers.devicePropertyChildren'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\DevicePropertyChildrenV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('controllers.deviceControls'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\DeviceControlsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		// CHANNELS
		$builder->addDefinition(
			$this->prefix('controllers.channels'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\ChannelsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('controllers.channelProperties'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\ChannelPropertiesV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('controllers.channelPropertyChildren'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\ChannelPropertyChildrenV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('controllers.channelControls'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\ChannelControlsV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		/**
		 * WEBSOCKETS CONTROLLERS
		 */

		if (class_exists('IPub\WebSockets\DI\WebSocketsExtension')) {
			$builder->addDefinition($this->prefix('controllers.exchange'), new DI\Definitions\ServiceDefinition())
				->setType(Controllers\ExchangeV1::class)
				->setArguments([
					'logger' => $logger,
				])
				->addTag('nette.inject');
		}

		/**
		 * JSON-API SCHEMAS
		 */

		// CONNECTORS
		$builder->addDefinition(
			$this->prefix('schemas.connector'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Connectors\Connector::class);

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

		$builder->addDefinition(
			$this->prefix('schemas.connector.controls'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Connectors\Controls\Control::class);

		// DEVICES
		$builder->addDefinition(
			$this->prefix('schemas.device'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\Device::class);

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

		$builder->addDefinition(
			$this->prefix('schemas.device.property.mapped'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\Properties\Mapped::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.control'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\Controls\Control::class);

		// CHANNELS
		$builder->addDefinition(
			$this->prefix('schemas.channel'),
			new DI\Definitions\ServiceDefinition(),
		)
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

		$builder->addDefinition(
			$this->prefix('schemas.control'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Controls\Control::class);

		/**
		 * JSON-API HYDRATORS
		 */

		// CONNECTORS
		$builder->addDefinition(
			$this->prefix('hydrators.connectors'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Connectors\Connector::class);

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

		// DEVICES
		$builder->addDefinition(
			$this->prefix('hydrators.device'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\Device::class);

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

		// CHANNELS
		$builder->addDefinition(
			$this->prefix('hydrators.channel'),
			new DI\Definitions\ServiceDefinition(),
		)
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

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('utilities.devices.connection'), new DI\Definitions\ServiceDefinition())
			->setType(Utilities\DeviceConnection::class);

		$builder->addDefinition($this->prefix('utilities.connector.connection'), new DI\Definitions\ServiceDefinition())
			->setType(Utilities\ConnectorConnection::class);

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Install::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition($this->prefix('commands.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Connector::class)
			->setArguments([
				'logger' => $logger,
				'exchangeFactories' => $builder->findByType(ExchangeExchange\Factory::class),
			]);

		$builder->addDefinition($this->prefix('commands.exchange'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Exchange::class)
			->setArguments([
				'logger' => $logger,
				'exchangeFactories' => $builder->findByType(ExchangeExchange\Factory::class),
			]);

		/**
		 * COMMUNICATION EXCHANGE
		 */

		$builder->addDefinition(
			$this->prefix('exchange.consumer.states'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\State::class)
			->setArguments([
				'logger' => $logger,
			])
			->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATE, false);

		$builder->addDefinition(
			$this->prefix('exchange.consumer.configuration'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Configuration::class)
			->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATE, false);

		if (
			$builder->findByType('IPub\WebSockets\Router\LinkGenerator') !== []
			&& $builder->findByType('IPub\WebSocketsWAMP\Topics\IStorage') !== []
		) {
			$builder->addDefinition(
				$this->prefix('exchange.consumer.sockets'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Consumers\Sockets::class)
				->setArguments([
					'logger' => $logger,
				])
				->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATE, false);
		}

		/**
		 * CONNECTOR
		 */

		$builder->addFactoryDefinition($this->prefix('connector'))
			->setImplement(Connectors\ContainerFactory::class)
			->getResultDefinition()
			->setType(Connectors\Container::class);
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

		$connectorProxyServiceFactoryName = $builder->getByType(Connectors\ContainerFactory::class);

		if ($connectorProxyServiceFactoryName !== null) {
			$connectorProxyServiceFactory = $builder->getDefinition($connectorProxyServiceFactoryName);
			assert($connectorProxyServiceFactory instanceof DI\Definitions\FactoryDefinition);

			$connectorsServicesFactories = $builder->findByType(Connectors\ConnectorFactory::class);

			$factories = [];

			foreach ($connectorsServicesFactories as $connectorServiceFactory) {
				if (
					$connectorServiceFactory->getType() !== Connectors\ConnectorFactory::class
					&& is_string($connectorServiceFactory->getTag(self::CONNECTOR_TYPE_TAG))
				) {
					$factories[$connectorServiceFactory->getTag(self::CONNECTOR_TYPE_TAG)] = $connectorServiceFactory;
				}
			}

			$connectorProxyServiceFactory->getResultDefinition()->setArgument('factories', $factories);
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

		$connectorsManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__entities__managers__connectors',
		);
		$connectorsManagerService->setBody(
			'return new ' . Models\Entities\Connectors\ConnectorsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Connectors\Connector::class . '\'));',
		);

		$connectorsPropertiesManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__entities__managers__connectorsProperties',
		);
		$connectorsPropertiesManagerService->setBody(
			'return new ' . Models\Entities\Connectors\Properties\PropertiesManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Connectors\Properties\Property::class . '\'));',
		);

		$connectorsControlsManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__entities__managers__connectorsControls',
		);
		$connectorsControlsManagerService->setBody(
			'return new ' . Models\Entities\Connectors\Controls\ControlsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Connectors\Controls\Control::class . '\'));',
		);

		$devicesManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__entities__managers__devices',
		);
		$devicesManagerService->setBody(
			'return new ' . Models\Entities\Devices\DevicesManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Devices\Device::class . '\'));',
		);

		$devicesPropertiesManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__entities__managers__devicesProperties',
		);
		$devicesPropertiesManagerService->setBody(
			'return new ' . Models\Entities\Devices\Properties\PropertiesManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Devices\Properties\Property::class . '\'));',
		);

		$devicesControlsManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__entities__managers__devicesControls',
		);
		$devicesControlsManagerService->setBody(
			'return new ' . Models\Entities\Devices\Controls\ControlsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Devices\Controls\Control::class . '\'));',
		);

		$channelsManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__entities__managers__channels',
		);
		$channelsManagerService->setBody(
			'return new ' . Models\Entities\Channels\ChannelsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Channels\Channel::class . '\'));',
		);

		$channelsPropertiesManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__entities__managers__channelsProperties',
		);
		$channelsPropertiesManagerService->setBody(
			'return new ' . Models\Entities\Channels\Properties\PropertiesManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Channels\Properties\Property::class . '\'));',
		);

		$channelsControlsManagerService = $class->getMethod(
			'createService' . ucfirst($this->name) . '__models__entities__managers__channelsControls',
		);
		$channelsControlsManagerService->setBody(
			'return new ' . Models\Entities\Channels\Controls\ControlsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Channels\Controls\Control::class . '\'));',
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
