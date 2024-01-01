<?php declare(strict_types = 1);

/**
 * Zigbee2MqttExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           23.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\DI;

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\Connector\Zigbee2Mqtt;
use FastyBird\Connector\Zigbee2Mqtt\API;
use FastyBird\Connector\Zigbee2Mqtt\Clients;
use FastyBird\Connector\Zigbee2Mqtt\Commands;
use FastyBird\Connector\Zigbee2Mqtt\Connector;
use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Helpers;
use FastyBird\Connector\Zigbee2Mqtt\Hydrators;
use FastyBird\Connector\Zigbee2Mqtt\Queue;
use FastyBird\Connector\Zigbee2Mqtt\Schemas;
use FastyBird\Connector\Zigbee2Mqtt\Subscribers;
use FastyBird\Connector\Zigbee2Mqtt\Writers;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Library\Exchange\DI as ExchangeDI;
use FastyBird\Module\Devices\DI as DevicesDI;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;
use const DIRECTORY_SEPARATOR;

/**
 * Zigbee2MQTT connector
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Zigbee2MqttExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbZigbee2MqttConnector';

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
			'writer' => Schema\Expect::anyOf(
				Writers\Event::NAME,
				Writers\Exchange::NAME,
			)->default(
				Writers\Exchange::NAME,
			),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$logger = $builder->addDefinition($this->prefix('logger'), new DI\Definitions\ServiceDefinition())
			->setType(Zigbee2Mqtt\Logger::class)
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
		}

		/**
		 * CLIENTS
		 */

		$builder->addFactoryDefinition($this->prefix('clients.mqtt'))
			->setImplement(Clients\MqttFactory::class)
			->getResultDefinition()
			->setType(Clients\Mqtt::class)
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

		$builder->addFactoryDefinition($this->prefix('clients.subscriber.bridge'))
			->setImplement(Clients\Subscribers\BridgeFactory::class)
			->getResultDefinition()
			->setType(Clients\Subscribers\Bridge::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.subscriber.device'))
			->setImplement(Clients\Subscribers\DeviceFactory::class)
			->getResultDefinition()
			->setType(Clients\Subscribers\Device::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * API
		 */

		$builder->addDefinition($this->prefix('api.connectionsManager'), new DI\Definitions\ServiceDefinition())
			->setType(API\ConnectionManager::class);

		$builder->addFactoryDefinition($this->prefix('api.client'))
			->setImplement(API\ClientFactory::class)
			->getResultDefinition()
			->setType(API\Client::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * MESSAGES QUEUE
		 */

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.bridgeConnectionState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreBridgeConnectionState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.bridgeDevices'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreBridgeDevices::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.bridgeEvent'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreBridgeEvent::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.bridgeGroups'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreBridgeGroups::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.bridgeInfo'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreBridgeInfo::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.bridgeLog'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreBridgeLog::class)
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
			$this->prefix('queue.consumers.write.subDeviceState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\WriteSubDeviceState::class)
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

		$builder->addDefinition($this->prefix('schemas.connector.zigbee2mqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Zigbee2MqttConnector::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.zigbee2mqtt.bridge'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\Bridge::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.zigbee2mqtt.subDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\SubDevice::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition(
			$this->prefix('hydrators.connector.zigbee2mqtt'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Zigbee2MqttConnector::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.zigbee2mqtt.bridge'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\Bridge::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.zigbee2mqtt.subDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\SubDevice::class);

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('helpers.entity'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Entity::class);

		$builder->addDefinition($this->prefix('helpers.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Connector::class);

		$builder->addDefinition($this->prefix('helpers.devices.bridge'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Devices\Bridge::class);

		$builder->addDefinition($this->prefix('helpers.devices.subDevice'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Devices\SubDevice::class);

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

		$builder->addFactoryDefinition($this->prefix('executor.factory'))
			->setImplement(Connector\ConnectorFactory::class)
			->addTag(
				DevicesDI\DevicesExtension::CONNECTOR_TYPE_TAG,
				Entities\Zigbee2MqttConnector::TYPE,
			)
			->getResultDefinition()
			->setType(Connector\Connector::class)
			->setArguments([
				'clientsFactories' => $builder->findByType(Clients\ClientFactory::class),
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
				'FastyBird\Connector\Zigbee2Mqtt\Entities',
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
