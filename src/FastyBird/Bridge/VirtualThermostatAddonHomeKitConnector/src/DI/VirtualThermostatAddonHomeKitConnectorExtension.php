<?php declare(strict_types = 1);

/**
 * VirtualThermostatAddonHomeKitConnectorExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           04.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\DI;

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Builders;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Commands;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Controllers;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Hydrators;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Router;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Schemas;
use FastyBird\Library\Application\Boot as ApplicationBoot;
use IPub\SlimRouter\Routing as SlimRouterRouting;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;
use const DIRECTORY_SEPARATOR;

/**
 * Virtual thermostat HomeKit connector bridge extension
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class VirtualThermostatAddonHomeKitConnectorExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbVirtualThermostatAddonHomeKitConnectorBridge';

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

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'apiPrefix' => Schema\Expect::bool(true),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$logger = $builder->addDefinition($this->prefix('logger'), new DI\Definitions\ServiceDefinition())
			->setType(VirtualThermostatAddonHomeKitConnector\Logger::class)
			->setAutowired(false);

		/**
		 * BUILDERS
		 */

		$builder->addDefinition($this->prefix('builder'), new DI\Definitions\ServiceDefinition())
			->setType(Builders\Builder::class)
			->setArguments([
				'logger' => $logger,
			]);

		/**
		 * ROUTE MIDDLEWARES & ROUTING
		 */

		$builder->addDefinition($this->prefix('router.api.routes'), new DI\Definitions\ServiceDefinition())
			->setType(Router\ApiRoutes::class)
			->setArguments(['usePrefix' => $configuration->apiPrefix]);

		/**
		 * API CONTROLLERS
		 */

		$builder->addDefinition(
			$this->prefix('controllers.bridges'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Controllers\BridgesV1::class)
			->addSetup('setLogger', [$logger])
			->addTag('nette.inject');

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition($this->prefix('schemas.device.thermostat'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Devices\Thermostat::class);

		$builder->addDefinition($this->prefix('schemas.channel.thermostat'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\Thermostat::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition($this->prefix('hydrators.device.thermostat'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Devices\Thermostat::class);

		$builder->addDefinition($this->prefix('hydrators.channel.thermostat'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Channels\Thermostat::class);

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.build'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Build::class)
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
		 * DOCTRINE ENTITIES
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
				'FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities',
			]);
		}

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
				'FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities',
			]);
		}

		/**
		 * API ROUTER
		 */

		$routerService = $builder->getDefinitionByType(SlimRouterRouting\Router::class);

		if ($routerService instanceof DI\Definitions\ServiceDefinition) {
			$routerService->addSetup('?->registerRoutes(?)', [
				$builder->getDefinitionByType(Router\ApiRoutes::class),
				$routerService,
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
