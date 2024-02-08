<?php declare(strict_types = 1);

/**
 * FbMqttExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           03.12.20
 */

namespace FastyBird\Connector\FbMqtt\DI;

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Clients;
use FastyBird\Connector\FbMqtt\Commands;
use FastyBird\Connector\FbMqtt\Connector;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Hydrators;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\Connector\FbMqtt\Schemas;
use FastyBird\Connector\FbMqtt\Subscribers;
use FastyBird\Connector\FbMqtt\Writers;
use FastyBird\Library\Application\Boot as ApplicationBoot;
use FastyBird\Library\Exchange\DI as ExchangeDI;
use FastyBird\Module\Devices\DI as DevicesDI;
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
class FbMqttExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbFbMqttConnector';

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
			->setType(FbMqtt\Logger::class)
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
		 * CLIENTS
		 */

		$builder->addFactoryDefinition($this->prefix('client.apiv1'))
			->setImplement(Clients\FbMqttV1Factory::class)
			->getResultDefinition()
			->setType(Clients\FbMqttV1::class)
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
			$this->prefix('queue.consumers.store.device'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\DeviceAttribute::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.deviceProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\DeviceProperty::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.extension'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\ExtensionAttribute::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.channel'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\ChannelAttribute::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\ChannelProperty::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.writeV1DevicePropertyState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\WriteV1DevicePropertyState::class)
			->setArguments([
				'logger' => $logger,
			]);

		$builder->addDefinition(
			$this->prefix('queue.consumers.store.writeV1ChannelPropertyState'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\Consumers\WriteV1ChannelPropertyState::class)
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

		$builder->addDefinition(
			$this->prefix('queue.messageBuilder'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Queue\MessageBuilder::class);

		/**
		 * SUBSCRIBERS
		 */

		$builder->addDefinition($this->prefix('subscribers.controls'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Controls::class);

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition($this->prefix('schemas.connector.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\FbMqttConnector::class);

		$builder->addDefinition($this->prefix('schemas.device.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\FbMqttDevice::class);

		$builder->addDefinition($this->prefix('schemas.channel.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\FbMqttChannel::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition($this->prefix('hydrators.connector.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\FbMqttConnector::class);

		$builder->addDefinition($this->prefix('hydrators.device.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\FbMqttDevice::class);

		$builder->addDefinition($this->prefix('hydrators.channel.fbMqtt'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\FbMqttChannel::class);

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('helpers.connector'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Connector::class);

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.execute'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Execute::class);

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
				Entities\FbMqttConnector::TYPE,
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
				'FastyBird\Connector\FbMqtt\Entities',
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
