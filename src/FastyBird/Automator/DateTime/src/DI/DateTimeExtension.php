<?php declare(strict_types = 1);

/**
 * DateTimeExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DateTimeAutomator!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           05.11.22
 */

namespace FastyBird\Automator\DateTime\DI;

use Doctrine\Persistence;
use FastyBird\Automator\DateTime\Hydrators;
use FastyBird\Automator\DateTime\Schemas;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use Nette;
use Nette\DI;
use const DIRECTORY_SEPARATOR;

/**
 * Date&Time automator
 *
 * @package        FastyBird:DateTimeAutomator!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DateTimeExtension extends DI\CompilerExtension
{

	public const NAME = 'fbDateTimeAutomator';

	public static function register(
		Nette\Configurator|BootstrapBoot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator|BootstrapBoot\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new DateTimeExtension());
		};
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('schemas.conditions.date'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Conditions\DateCondition::class);

		$builder->addDefinition($this->prefix('schemas.conditions.time'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Conditions\TimeCondition::class);

		$builder->addDefinition($this->prefix('hydrators.conditions.date'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Conditions\DataCondition::class);

		$builder->addDefinition($this->prefix('hydrators.conditions.time'), new DI\Definitions\ServiceDefinition())
			->setType(Hydrators\Conditions\TimeCondition::class);
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
				'FastyBird\Automator\DateTime\Entities',
			]);
		}
	}

}
