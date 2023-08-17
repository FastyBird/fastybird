<?php declare(strict_types = 1);

/**
 * NsPanelExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           11.07.23
 */

namespace FastyBird\Connector\NsPanel\DI;

use Doctrine\Persistence;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Clients;
use FastyBird\Connector\NsPanel\Commands;
use FastyBird\Connector\NsPanel\Connector;
use FastyBird\Connector\NsPanel\Controllers;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Hydrators;
use FastyBird\Connector\NsPanel\Middleware;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Router;
use FastyBird\Connector\NsPanel\Schemas;
use FastyBird\Connector\NsPanel\Servers;
use FastyBird\Connector\NsPanel\Subscribers;
use FastyBird\Connector\NsPanel\Writers;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Library\Exchange\DI as ExchangeDI;
use FastyBird\Module\Devices\DI as DevicesDI;
use Nette;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;
use const DIRECTORY_SEPARATOR;

/**
 * NS Panel connector
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class NsPanelExtension extends DI\CompilerExtension
{

	public const NAME = 'fbNsPanelConnector';

	public static function register(
		BootstrapBoot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		// @phpstan-ignore-next-line
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
			'writer' => Schema\Expect::anyOf(
				Writers\Event::NAME,
				Writers\Exchange::NAME,
				Writers\Periodic::NAME,
			)->default(
				Writers\Periodic::NAME,
			),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$logger = $builder->addDefinition($this->prefix('logger'), new DI\Definitions\ServiceDefinition())
			->setType(NsPanel\Logger::class)
			->setAutowired(false);

		/**
		 * WRITERS
		 */

		if ($configuration->writer === Writers\Event::NAME) {
			$builder->addFactoryDefinition($this->prefix('writers.event'))
				->setImplement(Writers\EventFactory::class)
				->getResultDefinition()
				->setType(Writers\Event::class);
		} elseif ($configuration->writer === Writers\Exchange::NAME) {
			$builder->addFactoryDefinition($this->prefix('writers.exchange'))
				->setImplement(Writers\ExchangeFactory::class)
				->getResultDefinition()
				->setType(Writers\Exchange::class)
				->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATE, false);
		} elseif ($configuration->writer === Writers\Periodic::NAME) {
			$builder->addFactoryDefinition($this->prefix('writers.periodic'))
				->setImplement(Writers\PeriodicFactory::class)
				->getResultDefinition()
				->setType(Writers\Periodic::class);
		}

		/**
		 * CLIENTS
		 */

		$builder->addFactoryDefinition($this->prefix('clients.gateway'))
			->setImplement(Clients\GatewayFactory::class)
			->getResultDefinition()
			->setType(Clients\Gateway::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.device'))
			->setImplement(Clients\DeviceFactory::class)
			->getResultDefinition()
			->setType(Clients\Device::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.discovery'))
			->setImplement(Clients\DiscoveryFactory::class)
			->getResultDefinition()
			->setType(Clients\Discovery::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * API
		 */

		$builder->addDefinition($this->prefix('api.httpClient'), new DI\Definitions\ServiceDefinition())
			->setType(API\HttpClientFactory::class);

		$builder->addFactoryDefinition($this->prefix('api.lanApi'))
			->setImplement(API\LanApiFactory::class)
			->getResultDefinition()
			->setType(API\LanApi::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('server.http'))
			->setImplement(Servers\HttpFactory::class)
			->getResultDefinition()
			->setType(Servers\Http::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * MESSAGES QUEUE
		 */

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.deviceState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreDeviceState::class)
			->setArguments([
				'useExchange' => $configuration->writer === Writers\Exchange::NAME,
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.deviceConnectionState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreDeviceConnectionState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.thirdPartyDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreThirdPartyDevice::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.subDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreSubDevice::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.write.subDeviceState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\WriteSubDeviceState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.write.thirdPartyDeviceState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\WriteThirdPartyDeviceState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers::class)
			->setArguments([
				'consumers' => $builder->findByType(Queue\Consumer::class),
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.queue'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Queue::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * SUBSCRIBERS
		 */

		$builder->addDefinition($this->prefix('subscribers.properties'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Properties::class);

		$builder->addDefinition($this->prefix('subscribers.controls'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Controls::class);

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition(
			$this->prefix('schemas.connector.nsPanel'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\NsPanelConnector::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.nsPanel.gateway'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\Gateway::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.nsPanel.subDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\SubDevice::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.nsPanel.thirdPartyDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\ThirdPartyDevice::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.nsPanel'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\NsPanelChannel::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition(
			$this->prefix('hydrators.connector.nsPanel'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\NsPanelConnector::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.nsPanel.gateway'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\Gateway::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.nsPanel.subDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\SubDevice::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.nsPanel.thirdPartyDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\ThirdPartyDevice::class);

		$builder->addDefinition($this->prefix('hydrators.channel.nsPanel'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\NsPanelChannel::class);

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('helpers.loader'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Loader::class);

		$builder->addDefinition($this->prefix('helpers.entity'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Entity::class);

		/**
		 * SERVERS
		 */

		$router = $builder->addDefinition($this->prefix('http.router'), new DI\Definitions\ServiceDefinition())
			->setType(Router\Router::class)
			->setAutowired(false);

		$builder->addDefinition($this->prefix('http.middlewares.router'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Router::class)
			->setArguments([
				'router' => $router,
				'logger' => $logger,
			]);

		$builder->addDefinition($this->prefix('http.controllers.directive'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\DirectiveController::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Initialize::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition($this->prefix('commands.devices'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Devices::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition($this->prefix('commands.execute'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Execute::class);

		$builder->addDefinition($this->prefix('commands.discovery'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Discovery::class);

		/**
		 * CONNECTOR
		 */

		$builder->addFactoryDefinition($this->prefix('connector'))
			->setImplement(Connector\ConnectorFactory::class)
			->addTag(
				DevicesDI\DevicesExtension::CONNECTOR_TYPE_TAG,
				Entities\NsPanelConnector::TYPE,
			)
			->getResultDefinition()
			->setType(Connector\Connector::class)
			->setArguments([
				'clientsFactories' => $builder->findByType(Clients\ClientFactory::class),
				'logger' => $logger,
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
				'FastyBird\Connector\NsPanel\Entities',
			]);
		}
	}

}
