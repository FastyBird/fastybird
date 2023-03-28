<?php declare(strict_types = 1);

/**
 * RedisExchangeDevicesModuleExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisExchangeDevicesModuleBridge!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           03.12.20
 */

namespace FastyBird\Bridge\RedisExchangeDevicesModule\DI;

use FastyBird\Bridge\RedisExchangeDevicesModule\Subscribers;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use Nette\DI;

/**
 * Redis DB devices module bridge extension
 *
 * @package        FastyBird:RedisExchangeDevicesModuleBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisExchangeDevicesModuleExtension extends DI\CompilerExtension
{

	public const NAME = 'fbRedisExchangeDevicesModuleBridge';

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
