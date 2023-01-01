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

use Doctrine\Persistence;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Clients;
use FastyBird\Connector\Shelly\Commands;
use FastyBird\Connector\Shelly\Connector;
use FastyBird\Connector\Shelly\Consumers;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Hydrators;
use FastyBird\Connector\Shelly\Schemas;
use FastyBird\Connector\Shelly\Subscribers;
use FastyBird\Connector\Shelly\Writers;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Module\Devices\DI as DevicesDI;
use Nette;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;
use const DIRECTORY_SEPARATOR;

/**
 * Shelly connector
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ShellyExtension extends DI\CompilerExtension
{

	public const NAME = 'fbShellyConnector';

	public static function register(
		Nette\Configurator|BootstrapBoot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator|BootstrapBoot\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new ShellyExtension());
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

		if ($configuration->writer === Writers\Event::NAME) {
			$builder->addDefinition($this->prefix('writers.event'), new DI\Definitions\ServiceDefinition())
				->setType(Writers\Event::class);
		} elseif ($configuration->writer === Writers\Exchange::NAME) {
			$builder->addDefinition($this->prefix('writers.exchange'), new DI\Definitions\ServiceDefinition())
				->setType(Writers\Exchange::class);
		} elseif ($configuration->writer === Writers\Periodic::NAME) {
			$builder->addDefinition($this->prefix('writers.periodic'), new DI\Definitions\ServiceDefinition())
				->setType(Writers\Periodic::class);
		}

		$builder->addFactoryDefinition($this->prefix('clients.local'))
			->setImplement(Clients\LocalFactory::class)
			->getResultDefinition()
			->setType(Clients\Local::class);

		$builder->addFactoryDefinition($this->prefix('clients.local.coap'))
			->setImplement(Clients\Local\CoapFactory::class)
			->getResultDefinition()
			->setType(Clients\Local\Coap::class);

		$builder->addFactoryDefinition($this->prefix('clients.local.http'))
			->setImplement(Clients\Local\HttpFactory::class)
			->getResultDefinition()
			->setType(Clients\Local\Http::class);

		$builder->addFactoryDefinition($this->prefix('clients.local.mqtt'))
			->setImplement(Clients\Local\MqttFactory::class)
			->getResultDefinition()
			->setType(Clients\Local\Mqtt::class);

		$builder->addFactoryDefinition($this->prefix('clients.discover'))
			->setImplement(Clients\DiscoveryFactory::class)
			->getResultDefinition()
			->setType(Clients\Discovery::class);

		$builder->addDefinition($this->prefix('api.entityFactory'), new DI\Definitions\ServiceDefinition())
			->setType(API\EntityFactory::class);

		$builder->addDefinition($this->prefix('api.gen1HttpApi'), new DI\Definitions\ServiceDefinition())
			->setType(API\Gen1HttpApiFactory::class);

		$builder->addDefinition($this->prefix('api.gen2HttpApi'), new DI\Definitions\ServiceDefinition())
			->setType(API\Gen2HttpApiFactory::class);

		$builder->addDefinition($this->prefix('api.gen1transformer'), new DI\Definitions\ServiceDefinition())
			->setType(API\Gen1Transformer::class);

		$builder->addDefinition(
			$this->prefix('consumers.messages.device.status'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\Status::class);

		$builder->addDefinition(
			$this->prefix('consumers.messages.device.discovery'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\LocalDiscovery::class);

		$builder->addDefinition($this->prefix('consumers.messages'), new DI\Definitions\ServiceDefinition())
			->setType(Consumers\Messages::class)
			->setArguments([
				'consumers' => $builder->findByType(Consumers\Consumer::class),
			]);

		$builder->addDefinition($this->prefix('subscribers.properties'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Properties::class);

		$builder->addDefinition($this->prefix('schemas.connector.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\ShellyConnector::class);

		$builder->addDefinition($this->prefix('schemas.device.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\ShellyDevice::class);

		$builder->addDefinition($this->prefix('hydrators.connector.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\ShellyConnector::class);

		$builder->addDefinition($this->prefix('hydrators.device.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\ShellyDevice::class);

		$builder->addDefinition($this->prefix('helpers.device'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Device::class);

		$builder->addDefinition($this->prefix('helpers.property'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Property::class);

		$builder->addFactoryDefinition($this->prefix('executor.factory'))
			->setImplement(Connector\ConnectorFactory::class)
			->addTag(
				DevicesDI\DevicesExtension::CONNECTOR_TYPE_TAG,
				Entities\ShellyConnector::CONNECTOR_TYPE,
			)
			->getResultDefinition()
			->setType(Connector\Connector::class)
			->setArguments([
				'clientsFactories' => $builder->findByType(Clients\ClientFactory::class),
			]);

		$builder->addDefinition($this->prefix('commands.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Initialize::class);

		$builder->addDefinition($this->prefix('commands.discovery'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Discovery::class);

		$builder->addDefinition($this->prefix('commands.execute'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Execute::class);
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
				'FastyBird\Connector\Shelly\Entities',
			]);
		}
	}

}
