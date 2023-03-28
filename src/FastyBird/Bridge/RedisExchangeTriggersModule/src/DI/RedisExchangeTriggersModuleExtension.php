<?php declare(strict_types = 1);

/**
 * RedisExchangeTriggersModuleExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisExchangeTriggersModuleBridge!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           02.11.22
 */

namespace FastyBird\Bridge\RedisExchangeTriggersModule\DI;

use FastyBird\Bridge\RedisExchangeTriggersModule\Subscribers;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use Nette\DI;

/**
 * Redis DB devices module bridge extension
 *
 * @package        FastyBird:RedisExchangeTriggersModuleBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisExchangeTriggersModuleExtension extends DI\CompilerExtension
{

	public const NAME = 'fbRedisExchangeTriggersModuleBridge';

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

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition(
			$this->prefix('subscribers.redisClient'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Subscribers\RedisClient::class);
	}

}
