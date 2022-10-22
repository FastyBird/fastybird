<?php declare(strict_types = 1);

/**
 * RedisDbDevicesStatesExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbDevicesStatesBridge!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           03.12.20
 */

namespace FastyBird\Bridge\RedisDbDevicesStates\DI;

use FastyBird\Bridge\RedisDbDevicesStates\Models;
use Nette;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;

/**
 * Redis DB devices states storage extension
 *
 * @package        FastyBird:RedisDbDevicesStatesBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisDbDevicesStatesExtension extends DI\CompilerExtension
{

	public const NAME = 'fbRedisDbDevicesStatesBridge';

	public static function register(
		Nette\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new RedisDbDevicesStatesExtension());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'database' => Schema\Expect::int(0),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition(
			$this->prefix('models.connectorPropertyRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\ConnectorPropertiesRepository::class)
			->setArguments(['database' => $configuration->database]);

		$builder->addDefinition(
			$this->prefix('models.devicePropertyRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\DevicePropertiesRepository::class)
			->setArguments(['database' => $configuration->database]);

		$builder->addDefinition(
			$this->prefix('models.channelPropertyRepository'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\ChannelPropertiesRepository::class)
			->setArguments(['database' => $configuration->database]);

		$builder->addDefinition(
			$this->prefix('models.connectorPropertiesManager'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\ConnectorPropertiesManager::class)
			->setArguments(['database' => $configuration->database]);

		$builder->addDefinition(
			$this->prefix('models.devicePropertiesManager'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\DevicePropertiesManager::class)
			->setArguments(['database' => $configuration->database]);

		$builder->addDefinition(
			$this->prefix('models.channelPropertiesManager'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\ChannelPropertiesManager::class)
			->setArguments(['database' => $configuration->database]);
	}

}
