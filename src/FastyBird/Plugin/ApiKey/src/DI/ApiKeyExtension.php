<?php declare(strict_types = 1);

/**
 * ApiKeyExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\ApiKey\DI;

use Doctrine\Persistence;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Plugin\ApiKey\Commands;
use FastyBird\Plugin\ApiKey\Entities;
use FastyBird\Plugin\ApiKey\Middleware;
use FastyBird\Plugin\ApiKey\Models;
use IPub\DoctrineCrud;
use Nette;
use Nette\DI;
use Nette\PhpGenerator;
use function ucfirst;
use const DIRECTORY_SEPARATOR;

/**
 * API key plugin
 *
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ApiKeyExtension extends DI\CompilerExtension
{

	public const NAME = 'fbApiKeyPlugin';

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

		$builder->addDefinition($this->prefix('models.keysRepository'), new DI\Definitions\ServiceDefinition())
			->setType(Models\KeyRepository::class);

		$builder->addDefinition($this->prefix('models.keysManager'), new DI\Definitions\ServiceDefinition())
			->setType(Models\KeysManager::class)
			->setArgument('entityCrud', '__placeholder__');

		$builder->addDefinition($this->prefix('commands.create'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\Create::class);

		$builder->addDefinition($this->prefix('middlewares.validator'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Validator::class);
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
				'FastyBird\Plugin\ApiKey\Entities',
			]);
		}
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function afterCompile(PhpGenerator\ClassType $class): void
	{
		$builder = $this->getContainerBuilder();

		$entityFactoryServiceName = $builder->getByType(DoctrineCrud\Crud\IEntityCrudFactory::class, true);

		$devicesManagerService = $class->getMethod('createService' . ucfirst($this->name) . '__models__keysManager');
		$devicesManagerService->setBody(
			'return new ' . Models\KeysManager::class
			. '($this->getService(\'' . $entityFactoryServiceName . '\')->create(\'' . Entities\Key::class . '\'));',
		);
	}

}
