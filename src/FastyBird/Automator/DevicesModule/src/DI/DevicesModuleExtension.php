<?php declare(strict_types = 1);

/**
 * DevicesModuleExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           05.11.22
 */

namespace FastyBird\Automator\DevicesModule\DI;

use Doctrine\Persistence;
use FastyBird\Automator\DevicesModule\Hydrators;
use FastyBird\Automator\DevicesModule\Schemas;
use FastyBird\Library\Application\Boot as ApplicationBoot;
use Nette;
use Nette\DI;
use const DIRECTORY_SEPARATOR;

/**
 * Devices module automator
 *
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicesModuleExtension extends DI\CompilerExtension
{

	public const NAME = 'fbDevicesModuleAutomator';

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

		$builder->addDefinition($this->prefix('schemas.actions.deviceProperty'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Actions\DevicePropertyAction::class);

		$builder->addDefinition(
			$this->prefix('schemas.actions.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Actions\ChannelPropertyAction::class);

		$builder->addDefinition(
			$this->prefix('schemas.conditions.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Conditions\ChannelPropertyCondition::class);

		$builder->addDefinition(
			$this->prefix('schemas.conditions.deviceProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Conditions\DevicePropertyCondition::class);

		$builder->addDefinition(
			$this->prefix('hydrators.actions.deviceProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Actions\DevicePropertyAction::class);

		$builder->addDefinition(
			$this->prefix('hydrators.actions.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Actions\ChannelPropertyAction::class);

		$builder->addDefinition(
			$this->prefix('hydrators.conditions.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Conditions\ChannelPropertyCondition::class);

		$builder->addDefinition(
			$this->prefix('hydrators.conditions.deviceProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Conditions\DevicePropertyCondition::class);
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
				'FastyBird\Automator\DevicesModule\Entities',
			]);
		}
	}

}
