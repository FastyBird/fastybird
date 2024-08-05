<?php declare(strict_types = 1);

/**
 * DevicesModuleUiModuleExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           04.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\DI;

use FastyBird\Bridge\DevicesModuleUiModule\Hydrators;
use FastyBird\Bridge\DevicesModuleUiModule\Schemas;
use FastyBird\Library\Application\Boot as ApplicationBoot;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;

/**
 * Redis DB devices module bridge extension
 *
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicesModuleUiModuleExtension extends DI\CompilerExtension
{

	public const NAME = 'fbDevicesModuleUiModuleBridge';

	public static function register(
		ApplicationBoot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			ApplicationBoot\Configurator $config,
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

		/**
		 * JSON-API SCHEMAS
		 */

		$builder->addDefinition(
			$this->prefix('schemas.dataSources.connectorProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\DataSources\ConnectorProperty::class);

		$builder->addDefinition(
			$this->prefix('schemas.dataSources.deviceProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\DataSources\DeviceProperty::class);

		$builder->addDefinition(
			$this->prefix('schemas.dataSources.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Schemas\Widgets\DataSources\ChannelProperty::class);

		/**
		 * JSON-API HYDRATORS
		 */

		$builder->addDefinition(
			$this->prefix('hydrators.dataSources.connectorProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DataSources\ConnectorProperty::class);

		$builder->addDefinition(
			$this->prefix('hydrators.dataSources.deviceProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DataSources\DeviceProperty::class);

		$builder->addDefinition(
			$this->prefix('hydrators.dataSources.channelProperty'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Hydrators\Widgets\DataSources\ChannelProperty::class);
	}

}
