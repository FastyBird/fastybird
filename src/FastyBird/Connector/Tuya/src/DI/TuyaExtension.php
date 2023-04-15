<?php declare(strict_types = 1);

/**
 * TuyaExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Connector\Tuya\DI;

use Doctrine\Persistence;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Clients;
use FastyBird\Connector\Tuya\Commands;
use FastyBird\Connector\Tuya\Connector;
use FastyBird\Connector\Tuya\Consumers;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Hydrators;
use FastyBird\Connector\Tuya\Mappers;
use FastyBird\Connector\Tuya\Schemas;
use FastyBird\Connector\Tuya\Subscribers;
use FastyBird\Connector\Tuya\Writers;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Library\Exchange\DI as ExchangeDI;
use FastyBird\Module\Devices\DI as DevicesDI;
use Nette;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;
use const DIRECTORY_SEPARATOR;

/**
 * Tuya connector
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class TuyaExtension extends DI\CompilerExtension
{

	public const NAME = 'fbTuyaConnector';

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

		$builder->addDefinition(
			$this->prefix('consumers.discovery.cloudDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\CloudDiscovery::class);

		$builder->addDefinition(
			$this->prefix('consumers.discovery.localDevice'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Consumers\Messages\LocalDiscovery::class);

		$builder->addDefinition($this->prefix('consumers.discovery.status'), new DI\Definitions\ServiceDefinition())
			->setType(Consumers\Messages\Status::class);

		$builder->addDefinition($this->prefix('consumers.discovery.state'), new DI\Definitions\ServiceDefinition())
			->setType(Consumers\Messages\State::class);

		$builder->addDefinition($this->prefix('consumers.proxy'), new DI\Definitions\ServiceDefinition())
			->setType(Consumers\Messages::class)
			->setArguments([
				'consumers' => $builder->findByType(Consumers\Consumer::class),
			]);

		$writer = null;

		if ($configuration->writer === Writers\Event::NAME) {
			$writer = $builder->addDefinition($this->prefix('writers.event'), new DI\Definitions\ServiceDefinition())
				->setType(Writers\Event::class)
				->setAutowired(false);
		} elseif ($configuration->writer === Writers\Exchange::NAME) {
			$writer = $builder->addDefinition($this->prefix('writers.exchange'), new DI\Definitions\ServiceDefinition())
				->setType(Writers\Exchange::class)
				->setAutowired(false)
				->addTag(ExchangeDI\ExchangeExtension::CONSUMER_STATUS, false);
		} elseif ($configuration->writer === Writers\Periodic::NAME) {
			$writer = $builder->addDefinition($this->prefix('writers.periodic'), new DI\Definitions\ServiceDefinition())
				->setType(Writers\Periodic::class)
				->setAutowired(false);
		}

		$builder->addFactoryDefinition($this->prefix('api.openApi'))
			->setImplement(API\OpenApiFactory::class)
			->getResultDefinition()
			->setType(API\OpenApi::class);

		$builder->addFactoryDefinition($this->prefix('api.openPulsar'))
			->setImplement(API\OpenPulsarFactory::class)
			->getResultDefinition()
			->setType(API\OpenPulsar::class);

		$builder->addFactoryDefinition($this->prefix('api.local'))
			->setImplement(API\LocalApiFactory::class)
			->getResultDefinition()
			->setType(API\LocalApi::class);

		$builder->addFactoryDefinition($this->prefix('clients.local'))
			->setImplement(Clients\LocalFactory::class)
			->getResultDefinition()
			->setType(Clients\Local::class)
			->setArguments([
				'writer' => $writer,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.cloud'))
			->setImplement(Clients\CloudFactory::class)
			->getResultDefinition()
			->setType(Clients\Cloud::class)
			->setArguments([
				'writer' => $writer,
			]);

		$builder->addFactoryDefinition($this->prefix('clients.discover'))
			->setImplement(Clients\DiscoveryFactory::class)
			->getResultDefinition()
			->setType(Clients\Discovery::class);

		$builder->addDefinition($this->prefix('subscribers.properties'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Properties::class);

		$builder->addDefinition($this->prefix('subscribers.controls'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Controls::class);

		$builder->addDefinition($this->prefix('schemas.connector.tuya'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\TuyaConnector::class);

		$builder->addDefinition($this->prefix('schemas.device.tuya'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\TuyaDevice::class);

		$builder->addDefinition($this->prefix('hydrators.connector.tuya'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\TuyaConnector::class);

		$builder->addDefinition($this->prefix('hydrators.device.tuya'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\TuyaDevice::class);

		$builder->addDefinition($this->prefix('helpers.property'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Property::class);

		$builder->addDefinition($this->prefix('mappers.dataPoint'), new DI\Definitions\ServiceDefinition())
			->setType(Mappers\DataPoint::class);

		$builder->addFactoryDefinition($this->prefix('executor.factory'))
			->setImplement(Connector\ConnectorFactory::class)
			->addTag(
				DevicesDI\DevicesExtension::CONNECTOR_TYPE_TAG,
				Entities\TuyaConnector::CONNECTOR_TYPE,
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
				'FastyBird\Connector\Tuya\Entities',
			]);
		}
	}

}
