<?php declare(strict_types = 1);

/**
 * ExchangeExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           19.12.20
 */

namespace FastyBird\Library\Exchange\DI;

use FastyBird\Library\Exchange\Consumers;
use FastyBird\Library\Exchange\Entities;
use FastyBird\Library\Exchange\Publisher;
use Nette;
use Nette\DI;
use function assert;
use function is_bool;

/**
 * Exchange plugin extension container
 *
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ExchangeExtension extends DI\CompilerExtension
{

	public const CONSUMER_STATUS = 'consumer_status';

	public static function register(
		Nette\Configurator $config,
		string $extensionName = 'fbExchangeLibrary',
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new ExchangeExtension());
		};
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('consumer'), new DI\Definitions\ServiceDefinition())
			->setType(Consumers\Container::class);

		$builder->addDefinition($this->prefix('publisher'), new DI\Definitions\ServiceDefinition())
			->setType(Publisher\Container::class);

		$builder->addDefinition($this->prefix('entityFactory'), new DI\Definitions\ServiceDefinition())
			->setType(Entities\EntityFactory::class);
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * CONSUMERS PROXY
		 */

		$consumerProxyServiceName = $builder->getByType(Consumers\Container::class);

		if ($consumerProxyServiceName !== null) {
			$consumerProxyService = $builder->getDefinition($consumerProxyServiceName);
			assert($consumerProxyService instanceof DI\Definitions\ServiceDefinition);

			$consumerServices = $builder->findByType(Consumers\Consumer::class);

			foreach ($consumerServices as $consumerService) {
				if (
					$consumerService->getType() !== Consumers\Container::class
					&& (
						$consumerService->getAutowired() !== false
						|| !is_bool($consumerService->getAutowired())
					)
				) {
					// Container is not allowed to be autowired
					$consumerService->setAutowired(false);

					$consumerStatus = $consumerService->getTag(self::CONSUMER_STATUS);

					$consumerProxyService->addSetup('?->register(?, ?)', [
						'@self',
						$consumerService,
						$consumerStatus ?? true,
					]);
				}
			}
		}

		/**
		 * PUBLISHERS PROXY
		 */

		$publisherProxyServiceName = $builder->getByType(Publisher\Container::class);

		if ($publisherProxyServiceName !== null) {
			$publisherProxyService = $builder->getDefinition($publisherProxyServiceName);
			assert($publisherProxyService instanceof DI\Definitions\ServiceDefinition);

			$publisherServices = $builder->findByType(Publisher\Publisher::class);

			foreach ($publisherServices as $publisherService) {
				if (
					$publisherService->getType() !== Publisher\Container::class
					&& (
						$publisherService->getAutowired() !== false
						|| !is_bool($publisherService->getAutowired())
					)
				) {
					// Container is not allowed to be autowired
					$publisherService->setAutowired(false);

					$publisherProxyService->addSetup('?->register(?)', [
						'@self',
						$publisherService,
					]);
				}
			}
		}
	}

}
