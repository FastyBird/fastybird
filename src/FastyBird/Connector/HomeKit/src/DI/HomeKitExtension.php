<?php declare(strict_types = 1);

/**
 * HomeKitExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           29.03.22
 */

namespace FastyBird\Connector\HomeKit\DI;

use Doctrine\Persistence;
use FastyBird\Connector\HomeKit\Clients;
use FastyBird\Connector\HomeKit\Commands;
use FastyBird\Connector\HomeKit\Connector;
use FastyBird\Connector\HomeKit\Consumers;
use FastyBird\Connector\HomeKit\Controllers;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Helpers;
use FastyBird\Connector\HomeKit\Hydrators;
use FastyBird\Connector\HomeKit\Middleware;
use FastyBird\Connector\HomeKit\Models;
use FastyBird\Connector\HomeKit\Protocol;
use FastyBird\Connector\HomeKit\Router;
use FastyBird\Connector\HomeKit\Schemas;
use FastyBird\Connector\HomeKit\Servers;
use FastyBird\Connector\HomeKit\Subscribers;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Library\Exchange\DI as ExchangeDI;
use FastyBird\Module\Devices\DI as DevicesDI;
use IPub\DoctrineCrud;
use Nette;
use Nette\DI;
use Nette\PhpGenerator;
use function ucfirst;
use const DIRECTORY_SEPARATOR;

/**
 * HomeKit connector
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class HomeKitExtension extends DI\CompilerExtension
{

	public const NAME = 'fbHomeKitConnector';

	public static function register(
		Nette\Configurator|BootstrapBoot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator|BootstrapBoot\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new HomeKitExtension());
		};
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addFactoryDefinition($this->prefix('server.mdns'))
			->setImplement(Servers\MdnsFactory::class)
			->getResultDefinition()
			->setType(Servers\Mdns::class);

		$builder->addFactoryDefinition($this->prefix('server.http'))
			->setImplement(Servers\HttpFactory::class)
			->getResultDefinition()
			->setType(Servers\Http::class);

		$builder->addFactoryDefinition($this->prefix('server.http.secure.server'))
			->setImplement(Servers\SecureServerFactory::class)
			->getResultDefinition()
			->setType(Servers\SecureServer::class);

		$builder->addFactoryDefinition($this->prefix('server.http.secure.connection'))
			->setImplement(Servers\SecureConnectionFactory::class)
			->getResultDefinition()
			->setType(Servers\SecureConnection::class);

		$builder->addDefinition($this->prefix('schemas.connector.homekit'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\HomeKitConnector::class);

		$builder->addDefinition($this->prefix('schemas.device.homekit'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\HomeKitDevice::class);

		$builder->addDefinition($this->prefix('hydrators.connector.homekit'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\HomeKitConnector::class);

		$builder->addDefinition($this->prefix('hydrators.device.homekit'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\HomeKitDevice::class);

		$builder->addDefinition($this->prefix('helpers.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Connector::class);

		$builder->addDefinition($this->prefix('helpers.loader'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Loader::class);

		$router = $builder->addDefinition($this->prefix('http.router'), new DI\Definitions\ServiceDefinition())
			->setType(Router\Router::class)
			->setAutowired(false);

		$builder->addDefinition($this->prefix('http.middlewares.router'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Router::class)
			->setArguments(['router' => $router]);

		$builder->addDefinition($this->prefix('http.controllers.accessories'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\AccessoriesController::class)
			->addTag('nette.inject');

		$builder->addDefinition(
			$this->prefix('http.controllers.characteristics'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\CharacteristicsController::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('http.controllers.pairing'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\PairingController::class)
			->addTag('nette.inject');

		$builder->addDefinition($this->prefix('entities.accessory.factory'))
			->setType(Entities\Protocol\AccessoryFactory::class);

		$builder->addDefinition($this->prefix('entities.service.factory'))
			->setType(Entities\Protocol\ServiceFactory::class);

		$builder->addDefinition($this->prefix('entities.characteristic.factory'))
			->setType(Entities\Protocol\CharacteristicsFactory::class);

		$builder->addDefinition($this->prefix('protocol.tlv'), new DI\Definitions\ServiceDefinition())
			->setType(Protocol\Tlv::class);

		$builder->addDefinition($this->prefix('protocol.accessoryDriver'))
			->setType(Protocol\Driver::class);

		$builder->addDefinition($this->prefix('clients.subscriber'))
			->setType(Clients\Subscriber::class);

		$builder->addDefinition($this->prefix('models.clientsRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Clients\ClientsRepository::class);

		$builder->addDefinition($this->prefix('models.clientsManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\Clients\ClientsManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addFactoryDefinition($this->prefix('executor.factory'))
			->setImplement(Connector\ConnectorFactory::class)
			->addTag(
				DevicesDI\DevicesExtension::CONNECTOR_TYPE_TAG,
				Entities\HomeKitConnector::CONNECTOR_TYPE,
			)
			->getResultDefinition()
			->setType(Connector\Connector::class)
			->setArguments([
				'serversFactories' => $builder->findByType(Servers\ServerFactory::class),
			]);

		$builder->addDefinition($this->prefix('consumers.exchange'), new DI\Definitions\ServiceDefinition())
			->setType(Consumers\Consumer::class)
			->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATUS, false);

		$builder->addDefinition($this->prefix('subscribers.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Connector::class);

		$builder->addDefinition($this->prefix('commands.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Initialize::class);

		$builder->addDefinition($this->prefix('commands.execute'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Execute::class);
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
				'FastyBird\Connector\HomeKit\Entities',
			]);
		}
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function afterCompile(PhpGenerator\ClassType $class): void
	{
		$builder = $this->getContainerBuilder();

		$entityFactoryServiceName = $builder->getByType(DoctrineCrud\Crud\IEntityCrudFactory::class, true);

		$devicesManagerService = $class->getMethod('createService' . ucfirst($this->name) . '__models__clientsManager');
		$devicesManagerService->setBody(
			'return new ' . Models\Clients\ClientsManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Client::class . '\'));',
		);
	}

}
