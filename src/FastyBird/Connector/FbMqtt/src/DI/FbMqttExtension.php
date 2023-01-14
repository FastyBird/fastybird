<?php declare(strict_types = 1);

/**
 * FbMqttExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           03.12.20
 */

namespace FastyBird\Connector\FbMqtt\DI;

use Doctrine\Persistence;
use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Clients;
use FastyBird\Connector\FbMqtt\Connector;
use FastyBird\Connector\FbMqtt\Consumers;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Hydrators;
use FastyBird\Connector\FbMqtt\Schemas;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Module\Devices\DI as DevicesDI;
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
class FbMqttExtension extends DI\CompilerExtension
{

	public const NAME = 'fbFbMqttConnector';

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
			$compiler->addExtension($extensionName, new FbMqttExtension());
		};
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addFactoryDefinition($this->prefix('client.apiv1'))
			->setImplement(Clients\FbMqttV1Factory::class)
			->getResultDefinition()
			->setType(Clients\FbMqttV1::class);

		$builder->addDefinition($this->prefix('api.v1parser'), new DI\Definitions\ServiceDefinition())
			->setType(API\V1Parser::class);

		$builder->addDefinition($this->prefix('api.v1validator'), new DI\Definitions\ServiceDefinition())
			->setType(API\V1Validator::class);

		$builder->addDefinition($this->prefix('api.v1builder'), new DI\Definitions\ServiceDefinition())
			->setType(API\V1Builder::class);

		$builder->addDefinition(
			$this->prefix('consumers.device.attribute.message'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\Device::class);

		$builder->addDefinition(
			$this->prefix('consumers.device.extension.message'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\ExtensionAttribute::class);

		$builder->addDefinition(
			$this->prefix('consumers.device.property.message'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\DeviceProperty::class);

		$builder->addDefinition(
			$this->prefix('consumers.channel.attribute.message'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\Channel::class);

		$builder->addDefinition(
			$this->prefix('consumers.channel.property.message'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\ChannelProperty::class);

		$builder->addDefinition($this->prefix('consumers.proxy'), new DI\Definitions\ServiceDefinition())
			->setType(Consumers\Messages::class)
			->setArguments([
				'consumers' => $builder->findByType(Consumers\Consumer::class),
			]);

		$builder->addDefinition($this->prefix('schemas.connector.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\FbMqttConnector::class);

		$builder->addDefinition($this->prefix('schemas.device.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\FbMqttDevice::class);

		$builder->addDefinition($this->prefix('hydrators.connector.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\FbMqttConnector::class);

		$builder->addDefinition($this->prefix('hydrators.device.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\FbMqttDevice::class);

		$builder->addDefinition($this->prefix('helpers.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Connector::class);

		$builder->addDefinition($this->prefix('helpers.property'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Property::class);

		$builder->addFactoryDefinition($this->prefix('executor.factory'))
			->setImplement(Connector\ConnectorFactory::class)
			->addTag(
				DevicesDI\DevicesExtension::CONNECTOR_TYPE_TAG,
				Entities\FbMqttConnector::CONNECTOR_TYPE,
			)
			->getResultDefinition()
			->setType(Connector\Connector::class)
			->setArguments([
				'clientsFactories' => $builder->findByType(Clients\ClientFactory::class),
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
				'FastyBird\Connector\FbMqtt\Entities',
			]);
		}
	}

}
