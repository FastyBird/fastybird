<?php declare(strict_types = 1);

/**
 * RedisDbDevicesModuleExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           03.12.20
 */

namespace FastyBird\Bridge\RedisDbDevicesModule\DI;

use FastyBird\Bridge\RedisDbDevicesModule\Models;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;

/**
 * Redis DB devices module bridge extension
 *
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisDbDevicesModuleExtension extends DI\CompilerExtension
{

	public const NAME = 'fbRedisDbDevicesModuleBridge';

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
