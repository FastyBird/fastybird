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
use FastyBird\Library\Application\Router as ApplicationRouter;
use FastyBird\Library\Application\UI as ApplicationUI;
use Nette\Application;
use Nette\Bootstrap;
use Nette\DI;
use function assert;
use function is_string;
use const DIRECTORY_SEPARATOR;

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
			Bootstrap\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * ROUTES
		 */

		$appRouterServiceName = $builder->getByType(ApplicationRouter\AppRouter::class);
		assert(is_string($appRouterServiceName));
		$appRouterService = $builder->getDefinition($appRouterServiceName);
		assert($appRouterService instanceof DI\Definitions\ServiceDefinition);

		$appRouterService->addSetup([Router\AppRouter::class, 'createRouter'], [$appRouterService]);

		/**
		 * UI
		 */

		$presenterFactoryService = $builder->getDefinitionByType(Application\IPresenterFactory::class);

		if ($presenterFactoryService instanceof DI\Definitions\ServiceDefinition) {
			$presenterFactoryService->addSetup('setMapping', [[
				'App' => 'FastyBird\App\Presenters\*Presenter',
			]]);
		}

		$templateFactoryService = $builder->getDefinitionByType(ApplicationUI\TemplateFactory::class);
		assert($templateFactoryService instanceof DI\Definitions\ServiceDefinition);

		$templateFactoryService->addSetup(
			'registerLayout',
			[
				__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
				. 'templates' . DIRECTORY_SEPARATOR . '@layout.latte',
			],
		);
	}

}
