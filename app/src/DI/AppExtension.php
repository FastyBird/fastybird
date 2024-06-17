<?php declare(strict_types = 1);

/**
 * AppExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Application!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           16.06.24
 */

namespace FastyBird\App\DI;

use FastyBird\App\Router;
use FastyBird\Library\Application\Boot as ApplicationBoot;
use Nette\Application;
use Nette\DI;

/**
 * FastyBird application
 *
 * @package        FastyBird:Application!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class AppExtension extends DI\CompilerExtension
{

	public const NAME = 'fbApplication';

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

		$builder->addDefinition($this->prefix('router.app.routes'), new DI\Definitions\ServiceDefinition())
			->setType(Router\AppRouter::class);
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		$presenterFactoryService = $builder->getDefinitionByType(Application\IPresenterFactory::class);

		if ($presenterFactoryService instanceof DI\Definitions\ServiceDefinition) {
			$presenterFactoryService->addSetup('setMapping', [[
				'App' => 'FastyBird\App\Presenters\*Presenter',
			]]);
		}
	}

}
