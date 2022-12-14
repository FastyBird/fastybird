<?php declare(strict_types = 1);

/**
 * RedisDbTriggersModuleExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbTriggersModuleBridge!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           02.11.22
 */

namespace FastyBird\Bridge\RedisDbTriggersModule\DI;

use FastyBird\Bridge\RedisDbTriggersModule\Models;
use FastyBird\Bridge\RedisDbTriggersModule\Subscribers;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use Nette;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;

/**
 * Redis DB devices module bridge extension
 *
 * @package        FastyBird:RedisDbTriggersModuleBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisDbTriggersModuleExtension extends DI\CompilerExtension
{

	public const NAME = 'fbRedisDbTriggersModuleBridge';

	public static function register(
		Nette\Configurator|BootstrapBoot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator|BootstrapBoot\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new RedisDbTriggersModuleExtension());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'database' => Schema\Expect::int(1),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition(
			$this->prefix('models.actionsRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\ActionsRepository::class)
			->setArguments(['database' => $configuration->database]);

		$builder->addDefinition(
			$this->prefix('models.conditionsRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\ConditionsRepository::class)
			->setArguments(['database' => $configuration->database]);

		$builder->addDefinition(
			$this->prefix('models.actionsManager'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\ActionsManager::class)
			->setArguments(['database' => $configuration->database]);

		$builder->addDefinition(
			$this->prefix('models.conditionsManager'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\ConditionsManager::class)
			->setArguments(['database' => $configuration->database]);

		$builder->addDefinition(
			$this->prefix('subscribers.redisClient'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Subscribers\RedisClient::class);
	}

}
