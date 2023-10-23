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
class VirtualExtension extends DI\CompilerExtension
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
		} elseif ($configuration->writer === Writers\Periodic::NAME) {
			$builder->addFactoryDefinition($this->prefix('writers.periodic'))
				->setImplement(Writers\PeriodicFactory::class)
				->getResultDefinition()
				->setType(Writers\Periodic::class);
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
			$this->prefix('queue.consumers.store.channelPropertyState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\StoreChannelPropertyState::class)
			->setArguments([
				'useExchange' => $configuration->writer === Writers\Exchange::NAME,
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
			$this->prefix('schemas.channel.virtual.presetAntiFreeze'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\PresetAntiFreeze::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.virtual.presetAway'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\PresetAway::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.virtual.presetComfort'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\PresetComfort::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.virtual.presetEco'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\PresetEco::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.virtual.presetHome'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\PresetHome::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.virtual.presetSleep'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\PresetSleep::class);

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
			$this->prefix('hydrators.channel.virtual.presetAntiFreeze'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\PresetAntiFreeze::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.virtual.presetAway'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\PresetAway::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.virtual.presetComfort'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\PresetComfort::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.virtual.presetEco'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\PresetEco::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.virtual.presetHome'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\PresetHome::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.virtual.presetSleep'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\PresetSleep::class);

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

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Initialize::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition($this->prefix('commands.device'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Devices::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition($this->prefix('commands.execute'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Execute::class);

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

}
