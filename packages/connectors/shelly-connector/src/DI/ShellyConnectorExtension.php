<?php declare(strict_types = 1);

/**
 * ShellyConnectorExtension.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           22.01.22
 */

namespace FastyBird\ShellyConnector\DI;

use Doctrine\Persistence;
use FastyBird\DevicesModule\DI as DevicesModuleDI;
use FastyBird\ShellyConnector\API;
use FastyBird\ShellyConnector\Clients;
use FastyBird\ShellyConnector\Commands;
use FastyBird\ShellyConnector\Connector;
use FastyBird\ShellyConnector\Consumers;
use FastyBird\ShellyConnector\Entities;
use FastyBird\ShellyConnector\Helpers;
use FastyBird\ShellyConnector\Hydrators;
use FastyBird\ShellyConnector\Mappers;
use FastyBird\ShellyConnector\Schemas;
use FastyBird\ShellyConnector\Subscribers;
use Nette;
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
class ShellyConnectorExtension extends DI\CompilerExtension
{

	public const NAME = 'fbShellyConnector';

	public static function register(
		Nette\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new ShellyConnectorExtension());
		};
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		// Clients
		$builder->addFactoryDefinition($this->prefix('client.gen1'))
			->setImplement(Clients\Gen1Factory::class)
			->getResultDefinition()
			->setType(Clients\Gen1::class);

		$builder->addFactoryDefinition($this->prefix('client.gen1.coap'))
			->setImplement(Clients\Gen1\CoapFactory::class)
			->getResultDefinition()
			->setType(Clients\Gen1\Coap::class);

		$builder->addFactoryDefinition($this->prefix('client.gen1.mdns'))
			->setImplement(Clients\Gen1\MdnsFactory::class)
			->getResultDefinition()
			->setType(Clients\Gen1\Mdns::class);

		$builder->addFactoryDefinition($this->prefix('client.gen1.http'))
			->setImplement(Clients\Gen1\HttpFactory::class)
			->getResultDefinition()
			->setType(Clients\Gen1\Http::class);

		$builder->addFactoryDefinition($this->prefix('client.gen1.mqtt'))
			->setImplement(Clients\Gen1\MqttFactory::class)
			->getResultDefinition()
			->setType(Clients\Gen1\Mqtt::class);

		// Messages API
		$builder->addDefinition($this->prefix('api.gen1parser'), new DI\Definitions\ServiceDefinition())
			->setType(API\Gen1Parser::class);

		$builder->addDefinition($this->prefix('api.gen1validator'), new DI\Definitions\ServiceDefinition())
			->setType(API\Gen1Validator::class);

		$builder->addDefinition($this->prefix('api.gen1transformer'), new DI\Definitions\ServiceDefinition())
			->setType(API\Gen1Transformer::class);

		// Consumers
		$builder->addDefinition(
			$this->prefix('consumer.messages.device.description'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\Description::class);

		$builder->addDefinition(
			$this->prefix('consumer.messages.device.status'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\Status::class);

		$builder->addDefinition($this->prefix('consumer.messages.device.info'), new DI\Definitions\ServiceDefinition())
			->setType(Consumers\Messages\Info::class);

		$builder->addDefinition(
			$this->prefix('consumer.messages.device.discovery'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\Discovery::class);

		$builder->addDefinition($this->prefix('consumer.messages'), new DI\Definitions\ServiceDefinition())
			->setType(Consumers\Messages::class)
			->setArguments([
				'consumers' => $builder->findByType(Consumers\Consumer::class),
			]);

		// Events subscribers
		$builder->addDefinition($this->prefix('subscribers.properties'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Properties::class);

		// API schemas
		$builder->addDefinition($this->prefix('schemas.connector.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\ShellyConnector::class);

		$builder->addDefinition($this->prefix('schemas.device.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\ShellyDevice::class);

		// API hydrators
		$builder->addDefinition($this->prefix('hydrators.connector.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\ShellyConnector::class);

		$builder->addDefinition($this->prefix('hydrators.device.shelly'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\ShellyDevice::class);

		// Helpers
		$builder->addDefinition($this->prefix('helpers.database'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Database::class);

		$builder->addDefinition($this->prefix('helpers.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Connector::class);

		$builder->addDefinition($this->prefix('helpers.device'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Device::class);

		$builder->addDefinition($this->prefix('helpers.property'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Property::class);

		// Mappers
		$builder->addDefinition($this->prefix('mappers.sensor'), new DI\Definitions\ServiceDefinition())
			->setType(Mappers\Sensor::class);

		// Service factory
		$builder->addFactoryDefinition($this->prefix('executor.factory'))
			->setImplement(Connector\ConnectorFactory::class)
			->addTag(
				DevicesModuleDI\DevicesModuleExtension::CONNECTOR_TYPE_TAG,
				Entities\ShellyConnector::CONNECTOR_TYPE,
			)
			->getResultDefinition()
			->setType(Connector\Connector::class)
			->setArguments([
				'clientsFactories' => $builder->findByType(Clients\ClientFactory::class),
			]);

		// Console commands
		$builder->addDefinition($this->prefix('commands.initialize'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Initialize::class);

		$builder->addDefinition($this->prefix('commands.discovery'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Discovery::class)
			->setArguments([
				'clientsFactories' => $builder->findByType(Clients\ClientFactory::class),
			]);

		$builder->addDefinition($this->prefix('commands.execute'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Execute::class);
	}

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
				'FastyBird\ShellyConnector\Entities',
			]);
		}
	}

}
