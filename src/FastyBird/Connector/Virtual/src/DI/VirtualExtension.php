<?php declare(strict_types = 1);

/**
 * VirtualExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Connector\Virtual\DI;

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Commands;
use FastyBird\Connector\Virtual\Connector;
use FastyBird\Connector\Virtual\Devices;
use FastyBird\Connector\Virtual\Drivers;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Helpers;
use FastyBird\Connector\Virtual\Hydrators;
use FastyBird\Connector\Virtual\Queue;
use FastyBird\Connector\Virtual\Schemas;
use FastyBird\Connector\Virtual\Subscribers;
use FastyBird\Connector\Virtual\Writers;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Library\Exchange\DI as ExchangeDI;
use FastyBird\Module\Devices\DI as DevicesDI;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;
use const DIRECTORY_SEPARATOR;

/**
 * Virtual connector
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class VirtualExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbVirtualConnector';

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
			->setType(Virtual\Logger::class)
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
		 * DRIVERS
		 */

		$builder->addFactoryDefinition($this->prefix('drivers.thermostat'))
			->setImplement(Drivers\ThermostatFactory::class)
			->getResultDefinition()
			->setType(Drivers\Thermostat::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition($this->prefix('drivers.manager'), new DI\Definitions\ServiceDefinition())
			->setType(Drivers\DriversManager::class)
			->setArguments([
				'driversFactories' => $builder->findByType(Drivers\DriverFactory::class),
			]);

		/**
		 * DEVICES
		 */

		$builder->addFactoryDefinition($this->prefix('devices.service'))
			->setImplement(Devices\DevicesFactory::class)
			->getResultDefinition()
			->setType(Devices\Devices::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * MESSAGES QUEUE
		 */

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.deviceConnectionState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreDeviceConnectionState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.devicePropertyState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreDevicePropertyState::class)
			->setArguments([
				'useExchange' => $configuration->writer === Writers\Exchange::NAME,
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.channelPropertyState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreChannelPropertyState::class)
			->setArguments([
				'useExchange' => $configuration->writer === Writers\Exchange::NAME,
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.write.devicePropertyState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\WriteDevicePropertyState::class)
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

		$builder->addDefinition($this->prefix('schemas.connector.virtual'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\VirtualConnector::class);

		$builder->addDefinition(
			$this->prefix('schemas.device.virtual.thermostat'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Devices\Thermostat::class);

		$builder->addDefinition($this->prefix('schemas.channel.virtual.actors'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\Actors::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.virtual.preset'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Preset::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.virtual.sensors'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Sensors::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.virtual.thermostat'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Thermostat::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition($this->prefix('hydrators.connector.virtual'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\VirtualConnector::class);

		$builder->addDefinition(
			$this->prefix('hydrators.device.virtual.thermostat'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Devices\Thermostat::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.virtual.actors'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Actors::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.virtual.preset'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Preset::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.virtual.sensors'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Sensors::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.virtual.thermostat'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Thermostat::class);

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('helpers.entity'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Entity::class);

		$builder->addDefinition($this->prefix('helpers.device'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Device::class);

		$builder->addDefinition($this->prefix('helpers.thermostat'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Devices\Thermostat::class);

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.execute'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Execute::class);

		$thermostatCmd = $builder->addDefinition(
			$this->prefix('commands.device.thermostat'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Commands\Devices\Thermostat::class)
			->setArguments([
				'logger' => $logger,
			])
			->setAutowired(false);

		$builder->addDefinition($this->prefix('commands.install'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Install::class)
			->setArguments([
				'logger' => $logger,
				'commands' => [
					Entities\Devices\Thermostat::TYPE => $thermostatCmd,
				],
			]);

		/**
		 * CONNECTOR
		 */

		$builder->addFactoryDefinition($this->prefix('connector'))
			->setImplement(Connector\ConnectorFactory::class)
			->addTag(
				DevicesDI\DevicesExtension::CONNECTOR_TYPE_TAG,
				Entities\VirtualConnector::TYPE,
			)
			->getResultDefinition()
			->setType(Connector\Connector::class)
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
				'FastyBird\Connector\Virtual\Entities',
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
