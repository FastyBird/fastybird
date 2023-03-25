<?php declare(strict_types = 1);

/**
 * RedisExchangeWsExchangeExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisExchangeWsExchangeBridge!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           03.12.20
 */

namespace FastyBird\Bridge\RedisExchangeWsExchange\DI;

use FastyBird\Bridge\RedisExchangeWsExchange\Subscribers;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use Nette\DI;

/**
 * Redis DB and WS exchange bridge extension
 *
 * @package        FastyBird:RedisExchangeWsExchangeBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisExchangeWsExchangeExtension extends DI\CompilerExtension
{

	public const NAME = 'fbRedisExchangeWsExchangeBridge';

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
			$compiler->addExtension($extensionName, new RedisExchangeWsExchangeExtension());
		};
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition(
			$this->prefix('subscribers.ws.startup'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Subscribers\WsServer::class);
	}

}
