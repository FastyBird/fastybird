<?php declare(strict_types = 1);

/**
 * ShellyExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           22.01.22
 */

namespace FastyBird\Connector\Shelly\DI;

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Clients;
use FastyBird\Connector\Shelly\Commands;
use FastyBird\Connector\Shelly\Connector;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Hydrators;
use FastyBird\Connector\Shelly\Queue;
use FastyBird\Connector\Shelly\Schemas;
use FastyBird\Connector\Shelly\Services;
use FastyBird\Connector\Shelly\Subscribers;
use FastyBird\Connector\Shelly\Writers;
use FastyBird\Library\Application\Boot as ApplicationBoot;
use FastyBird\Library\Exchange\DI as ExchangeDI;
use FastyBird\Module\Devices\DI as DevicesDI;
use Nette\DI;
use const DIRECTORY_SEPARATOR;

/**
 * Shelly connector
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ShellyExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbShellyConnector';

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

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$logger = $builder->addDefinition($this->prefix('logger'), new DI\Definitions\ServiceDefinition())
			->setType(Shelly\Logger::class)
			->setAutowired(false);

		/**
		 * WRITERS
		 */

		$builder->addFactoryDefinition($this->prefix('writers.event'))
			->setImplement(Writers\EventFactory::class)
			->getResultDefinition()
			->setType(Writers\Event::class);

		$builder->addFactoryDefinition($this->prefix('writers.exchange'))
			->setImplement(Writers\ExchangeFactory::class)
			->getResultDefinition()
			->setType(Writers\Exchange::class)
			->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATE, false);

		/**
		 * SERVICES & FACTORIES
		 */

		$builder->addDefinition(
			$this->prefix('services.multicastFactory'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Services\MulticastFactory::class);

		$builder->addDefinition($this->prefix('services.httpClientFactory'), new DI\Definitions\ServiceDefinition())
			->setType(Services\HttpClientFactory::class);

		/**
		 * CLIENTS
		 */

		$builder->addFactoryDefinition($this->prefix('clients.local'))
			->setImplement(Clients\LocalFactory::class)
			->getResultDefinition()
			->setType(Clients\Local::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.cloud'))
			->setImplement(Clients\CloudFactory::class)
			->getResultDefinition()
			->setType(Clients\Cloud::class);

		$builder->addFactoryDefinition($this->prefix('clients.mqtt'))
			->setImplement(Clients\MqttFactory::class)
			->getResultDefinition()
			->setType(Clients\Mqtt::class);

		$builder->addFactoryDefinition($this->prefix('clients.discover'))
			->setImplement(Clients\DiscoveryFactory::class)
			->getResultDefinition()
			->setType(Clients\Discovery::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * API
		 */

		$builder->addDefinition($this->prefix('api.connectionsManager'), new DI\Definitions\ServiceDefinition())
			->setType(API\ConnectionManager::class);

		$builder->addFactoryDefinition($this->prefix('api.gen1HttpApi'))
			->setImplement(API\Gen1HttpApiFactory::class)
			->getResultDefinition()
			->setType(API\Gen1HttpApi::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('api.gen1CoapApi'))
			->setImplement(API\Gen1CoapFactory::class)
			->getResultDefinition()
			->setType(API\Gen1Coap::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('api.gen2HttpApi'))
			->setImplement(API\Gen2HttpApiFactory::class)
			->getResultDefinition()
			->setType(API\Gen2HttpApi::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('api.ws'))
			->setImplement(API\Gen2WsApiFactory::class)
			->getResultDefinition()
			->setType(API\Gen2WsApi::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * MESSAGES QUEUE
		 */

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.localDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreLocalDevice::class)
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
			$this->prefix('queue.consumers.store.deviceState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreDeviceState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.write.channelPropertyState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\WriteChannelPropertyState::class)
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

		$builder->addDefinition($this->prefix('schemas.connector.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\ShellyConnector::class);

		$builder->addDefinition($this->prefix('schemas.device.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\ShellyDevice::class);

		$builder->addDefinition($this->prefix('schemas.channel.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\ShellyChannel::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition($this->prefix('hydrators.connector.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\ShellyConnector::class);

		$builder->addDefinition($this->prefix('hydrators.device.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\ShellyDevice::class);

		$builder->addDefinition($this->prefix('hydrators.channel.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\ShellyChannel::class);

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('helpers.entity'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Entity::class);

		$builder->addDefinition($this->prefix('helpers.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Connector::class);

		$builder->addDefinition($this->prefix('helpers.device'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Device::class);

		$builder->addDefinition($this->prefix('helpers.loader'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Loader::class);

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
				Entities\ShellyConnector::TYPE,
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
		 * Doctrine entities
		 */

		$ormAttributeDriverService = $builder->getDefinition('nettrineOrmAttributes.attributeDriver');

		if ($ormAttributeDriverService instanceof DI\Definitions\ServiceDefinition) {
			$ormAttributeDriverService->addSetup(
				'addPaths',
				[[__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities']],
			);
		}

		$ormAttributeDriverChainService = $builder->getDefinitionByType(
			Persistence\Mapping\Driver\MappingDriverChain::class,
		);

		if ($ormAttributeDriverChainService instanceof DI\Definitions\ServiceDefinition) {
			$ormAttributeDriverChainService->addSetup('addDriver', [
				$ormAttributeDriverService,
				'FastyBird\Connector\Shelly\Entities',
			]);
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
