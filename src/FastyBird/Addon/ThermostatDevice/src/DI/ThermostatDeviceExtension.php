<?php declare(strict_types = 1);

/**
 * ThermostatDeviceExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Addon\ThermostatDevice\DI;

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\Addon\ThermostatDevice;
use FastyBird\Addon\ThermostatDevice\Commands;
use FastyBird\Addon\ThermostatDevice\Drivers;
use FastyBird\Addon\ThermostatDevice\Helpers;
use FastyBird\Addon\ThermostatDevice\Hydrators;
use FastyBird\Addon\ThermostatDevice\Schemas;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use Nette\DI;
use const DIRECTORY_SEPARATOR;

/**
 * Virtual connector
 *
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ThermostatDeviceExtension extends DI\CompilerExtension implements Translation\DI\TranslationProviderInterface
{

	public const NAME = 'fbThermostatDeviceAddon';

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

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$logger = $builder->addDefinition($this->prefix('logger'), new DI\Definitions\ServiceDefinition())
			->setType(ThermostatDevice\Logger::class)
			->setAutowired(false);

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

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition(
			$this->prefix('schemas.device.thermostat'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\ThermostatDevice::class);

		$builder->addDefinition($this->prefix('schemas.channel.actors'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Channels\Actors::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.preset'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Preset::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.sensors'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Sensors::class);

		$builder->addDefinition(
			$this->prefix('schemas.channel.thermostat'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Channels\Configuration::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition(
			$this->prefix('hydrators.device.thermostat'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\ThermostatDevice::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.actors'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Actors::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.preset'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Preset::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.sensors'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Sensors::class);

		$builder->addDefinition(
			$this->prefix('hydrators.channel.thermostat'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Channels\Configuration::class);

		/**
		 * HELPERS
		 */

		$builder->addDefinition($this->prefix('helpers.device'), new DI\Definitions\ServiceDefinition())
			->setType(Helpers\Device::class);

		/**
		 * COMMANDS
		 */

		$builder->addDefinition($this->prefix('commands.install'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Install::class)
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
				'FastyBird\Addon\ThermostatDevice\Entities',
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
