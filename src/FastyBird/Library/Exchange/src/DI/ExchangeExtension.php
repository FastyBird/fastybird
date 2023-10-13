<?php declare(strict_types = 1);

/**
 * ExchangeExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           19.12.20
 */

namespace FastyBird\Library\Exchange\DI;

use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Library\Exchange\Consumers;
use FastyBird\Library\Exchange\Entities;
use FastyBird\Library\Exchange\Publisher;
use Nette;
use Nette\DI;
use function assert;
use function is_bool;
use function is_string;

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

	public const CONSUMER_STATE = 'consumer_state';

	public const CONSUMER_ROUTING_KEY = 'consumer_routing_key';

	public static function register(
		BootstrapBoot\Configurator $config,
		string $extensionName = 'fbExchangeLibrary',
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
						$consumerService->getAutowired() === true
						|| !is_bool($consumerService->getAutowired())
					)
				) {
					// Container is not allowed to be autowired
					$consumerService->setAutowired(false);

					$consumerStatus = $consumerService->getTag(self::CONSUMER_STATE);
					assert(is_bool($consumerStatus) || $consumerStatus === null);
					$consumerRoutingKey = $consumerService->getTag(self::CONSUMER_ROUTING_KEY);
					assert(is_string($consumerRoutingKey) || $consumerRoutingKey === null);

					$consumerProxyService->addSetup('?->register(?, ?, ?)', [
						'@self',
						$consumerService,
						$consumerRoutingKey ?? null,
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
						$publisherService->getAutowired() === true
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
