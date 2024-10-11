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

use Contributte\Translation;
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
use FastyBird\Connector\NsPanel\Mapping;
use FastyBird\Connector\NsPanel\Middleware;
use FastyBird\Connector\NsPanel\Protocol;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Connector\NsPanel\Router;
use FastyBird\Connector\NsPanel\Schemas;
use FastyBird\Connector\NsPanel\Servers;
use FastyBird\Connector\NsPanel\Services;
use FastyBird\Connector\NsPanel\Subscribers;
use FastyBird\Connector\NsPanel\Writers;
use FastyBird\Library\Application\Boot as ApplicationBoot;
use FastyBird\Library\Exchange\DI as ExchangeDI;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices\DI as DevicesDI;
use Nette\Bootstrap;
use Nette\DI;
use Nettrine\ORM as NettrineORM;
use function array_keys;
use function array_pop;
use const DIRECTORY_SEPARATOR;

/**
 * NS Panel connector
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class NsPanelExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbNsPanelConnector';

	public static function register(
		ApplicationBoot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Bootstrap\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$logger = $builder->addDefinition($this->prefix('logger'), new DI\Definitions\ServiceDefinition())
			->setType(NsPanel\Logger::class)
			->setAutowired(false);

		/**
		 * WRITERS
		 */

		$builder->addFactoryDefinition($this->prefix('writers.event'))
			->setImplement(Writers\EventFactory::class)
			->getResultDefinition()
			->setType(Writers\Event::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('writers.exchange'))
			->setImplement(Writers\ExchangeFactory::class)
			->getResultDefinition()
			->setType(Writers\Exchange::class)
			->setArguments([
				'logger' => $logger,
			])
			->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATE, false);

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
		 * SERVICES & FACTORIES
		 */

		$builder->addDefinition($this->prefix('services.httpClient'), new DI\Definitions\ServiceDefinition())
			->setType(Services\HttpClientFactory::class);

		/**
		 * API
		 */

		$builder->addFactoryDefinition($this->prefix('api.lanApi'))
			->setImplement(API\LanApiFactory::class)
			->getResultDefinition()
			->setType(API\LanApi::class)
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
			$this->prefix('schemas.connector'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Connectors\Connector::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.gateway'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\Gateway::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.subDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\SubDevice::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.thirdPartyDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\ThirdPartyDevice::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.battery'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Battery::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.brightness'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Brightness::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.cameraStream'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\CameraStream::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.colorRgb'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\ColorRgb::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.colorTemperature'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\ColorTemperature::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.detect'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Detect::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.fault'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Fault::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.humidity'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Humidity::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.illuminationLevel'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\IlluminationLevel::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.motorCalibration'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\MotorCalibration::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.motorControl'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\MotorControl::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.motorReverse'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\MotorReverse::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.percentage'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Percentage::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.power'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Power::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.press'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Press::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.rssi'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Rssi::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.startup'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Startup::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.temperature'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Temperature::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.toggle'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Toggle::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition(
			$this->prefix('hydrators.connector'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Connectors\Connector::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.gateway'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\Gateway::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.subDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\SubDevice::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.thirdPartyDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\ThirdPartyDevice::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.battery'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Battery::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.brightness'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Brightness::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.cameraStream'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\CameraStream::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.colorRgb'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\ColorRgb::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.colorTemperature'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\ColorTemperature::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.detect'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Detect::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.fault'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Fault::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.humidity'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Humidity::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.illuminationLevel'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\IlluminationLevel::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.motorCalibration'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\MotorCalibration::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.motorControl'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\MotorControl::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.motorReverse'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\MotorReverse::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.percentage'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Percentage::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.power'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Power::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.press'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Press::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.rssi'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Rssi::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.startup'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Startup::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.temperature'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Temperature::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.toggle'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Toggle::class);

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('helpers.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Connectors\Connector::class);

		$builder->addDefinition($this->prefix('helpers.gatewayDevice'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Devices\Gateway::class);

		$builder->addDefinition($this->prefix('helpers.thirdPartyDevice'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Devices\ThirdPartyDevice::class);

		$builder->addDefinition($this->prefix('helpers.subDevice'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Devices\SubDevice::class);

		$builder->addDefinition($this->prefix('helpers.channel'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Channels\Channel::class);

		$builder->addDefinition($this->prefix('helpers.messageBuilder'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\MessageBuilder::class);

		/**
		 * SERVERS
		 */

		$builder->addFactoryDefinition($this->prefix('server.http'))
			->setImplement(Servers\HttpFactory::class)
			->getResultDefinition()
			->setType(Servers\Http::class)
			->setArguments([
				'logger' => $logger,
			]);

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
		 * METADATA MAPPING
		 */

		$builder->addDefinition($this->prefix('mapping.builder'), new DI\Definitions\ServiceDefinition())
			->setType(Mapping\Builder::class);

		/**
		 * COMMUNICATION PROTOCOL
		 */

		$builder->addDefinition($this->prefix('protocol.driver'), new DI\Definitions\ServiceDefinition())
			->setType(Protocol\Driver::class);

		$builder->addDefinition($this->prefix('protocol.loader'), new DI\Definitions\ServiceDefinition())
			->setType(Protocol\Loader::class)
			->setArguments([
				'logger' => $logger,
				'deviceFactories' => [],
				'capabilityFactories' => [],
				'attributeFactories' => [],
				'configurationsFactories' => [],
			]);

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.execute'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Execute::class);

		$builder->addDefinition($this->prefix('commands.discover'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Discover::class);

		$builder->addDefinition($this->prefix('commands.install'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Install::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * CONNECTOR
		 */

		$builder->addFactoryDefinition($this->prefix('connector'))
			->setImplement(Connector\ConnectorFactory::class)
			->addTag(
				DevicesDI\DevicesExtension::CONNECTOR_TYPE_TAG,
				Entities\Connectors\Connector::TYPE,
			)
			->getResultDefinition()
			->setType(Connector\Connector::class)
			->setArguments([
				'clientsFactories' => $builder->findByType(Clients\ClientFactory::class),
				'writersFactories' => $builder->findByType(Writers\WriterFactory::class),
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
						'FastyBird\Connector\NsPanel\Entities',
					]);
				}
			}
		}

		/**
		 * APPLICATION DOCUMENTS
		 */

		$services = $builder->findByTag(Metadata\DI\MetadataExtension::DRIVER_TAG);

		if ($services !== []) {
			$services = array_keys($services);
			$documentAttributeDriverServiceName = array_pop($services);

			$documentAttributeDriverService = $builder->getDefinition($documentAttributeDriverServiceName);

			if ($documentAttributeDriverService instanceof DI\Definitions\ServiceDefinition) {
				$documentAttributeDriverService->addSetup(
					'addPaths',
					[[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Documents']],
				);

				$documentAttributeDriverChainService = $builder->getDefinitionByType(
					MetadataDocuments\Mapping\Driver\MappingDriverChain::class,
				);

				if ($documentAttributeDriverChainService instanceof DI\Definitions\ServiceDefinition) {
					$documentAttributeDriverChainService->addSetup('addDriver', [
						$documentAttributeDriverService,
						'FastyBird\Connector\NsPanel\Documents',
					]);
				}
			}
		}
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
