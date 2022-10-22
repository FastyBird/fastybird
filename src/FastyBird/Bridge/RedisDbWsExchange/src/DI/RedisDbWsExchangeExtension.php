<?php declare(strict_types = 1);

/**
 * RedisDbWsExchangeExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbWsExchangeBridge!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           03.12.20
 */

namespace FastyBird\Bridge\RedisDbWsExchange\DI;

use FastyBird\Bridge\RedisDbWsExchange\Subscribers;
use Nette;
use Nette\DI;

/**
 * Redis DB and WS exchange bridge extension
 *
 * @package        FastyBird:RedisDbWsExchangeBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisDbWsExchangeExtension extends DI\CompilerExtension
{

	public const NAME = 'fbRedisDbWsExchangeBridge';

	public static function register(
		Nette\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new RedisDbWsExchangeExtension());
		};
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition(
			$this->prefix('subscribers.redis.exchange'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Subscribers\RedisMessageReceived::class);

		$builder->addDefinition(
			$this->prefix('subscribers.ws.clientRpc'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Subscribers\WsClientRpc::class);

		$builder->addDefinition(
			$this->prefix('subscribers.ws.startup'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Subscribers\WsStartup::class);
	}

}
