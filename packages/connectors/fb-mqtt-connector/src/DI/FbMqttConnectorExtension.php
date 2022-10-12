<?php declare(strict_types = 1);

/**
 * FbMqttConnectorExtension.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           03.12.20
 */

namespace FastyBird\FbMqttConnector\DI;

use Doctrine\Persistence;
use FastyBird\DevicesModule\DI as DevicesModuleDI;
use FastyBird\FbMqttConnector\API;
use FastyBird\FbMqttConnector\Clients;
use FastyBird\FbMqttConnector\Connector;
use FastyBird\FbMqttConnector\Consumers;
use FastyBird\FbMqttConnector\Entities;
use FastyBird\FbMqttConnector\Helpers;
use FastyBird\FbMqttConnector\Hydrators;
use FastyBird\FbMqttConnector\Schemas;
use Nette;
use Nette\DI;
use const DIRECTORY_SEPARATOR;

/**
 * FastyBird MQTT connector
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FbMqttConnectorExtension extends DI\CompilerExtension
{

	public const NAME = 'fbFbMqttConnector';

	public static function register(
		Nette\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new FbMqttConnectorExtension());
		};
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		// MQTT v1 API client
		$builder->addFactoryDefinition($this->prefix('client.apiv1'))
			->setImplement(Clients\FbMqttV1Factory::class)
			->getResultDefinition()
			->setType(Clients\FbMqttV1::class);

		// MQTT API
		$builder->addDefinition($this->prefix('api.v1parser'), new DI\Definitions\ServiceDefinition())
			->setType(API\V1Parser::class);

		$builder->addDefinition($this->prefix('api.v1validator'), new DI\Definitions\ServiceDefinition())
			->setType(API\V1Validator::class);

		$builder->addDefinition($this->prefix('api.v1builder'), new DI\Definitions\ServiceDefinition())
			->setType(API\V1Builder::class);

		// Consumers
		$builder->addDefinition(
			$this->prefix('consumer.device.attribute.message'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\Device::class);

		$builder->addDefinition(
			$this->prefix('consumer.device.extension.message'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\ExtensionAttribute::class);

		$builder->addDefinition(
			$this->prefix('consumer.device.property.message'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\DeviceProperty::class);

		$builder->addDefinition(
			$this->prefix('consumer.channel.attribute.message'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\Channel::class);

		$builder->addDefinition(
			$this->prefix('consumer.channel.property.message'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\ChannelProperty::class);

		$builder->addDefinition($this->prefix('consumer.proxy'), new DI\Definitions\ServiceDefinition())
			->setType(Consumers\Messages::class)
			->setArguments([
				'consumers' => $builder->findByType(Consumers\Consumer::class),
			]);

		// API schemas
		$builder->addDefinition($this->prefix('schemas.connector.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\FbMqttConnector::class);

		$builder->addDefinition($this->prefix('schemas.device.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\FbMqttDevice::class);

		// API hydrators
		$builder->addDefinition($this->prefix('hydrators.connector.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\FbMqttConnector::class);

		$builder->addDefinition($this->prefix('hydrators.device.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\FbMqttDevice::class);

		// Helpers
		$builder->addDefinition($this->prefix('helpers.database'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Database::class);

		$builder->addDefinition($this->prefix('helpers.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Connector::class);

		$builder->addDefinition($this->prefix('helpers.property'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Property::class);

		// Service factory
		$builder->addFactoryDefinition($this->prefix('executor.factory'))
			->setImplement(Connector\ConnectorFactory::class)
			->addTag(
				DevicesModuleDI\DevicesModuleExtension::CONNECTOR_TYPE_TAG,
				Entities\FbMqttConnector::CONNECTOR_TYPE,
			)
			->getResultDefinition()
			->setType(Connector\Connector::class)
			->setArguments([
				'clientsFactories' => $builder->findByType(Clients\ClientFactory::class),
			]);
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
				'FastyBird\FbMqttConnector\Entities',
			]);
		}
	}

}
