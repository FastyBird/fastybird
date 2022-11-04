<?php declare(strict_types = 1);

/**
 * WsExchangeDevicesModuleExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsExchangeDevicesModuleBridge!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           03.12.20
 */

namespace FastyBird\Bridge\WsExchangeDevicesModule\DI;

use FastyBird\Bridge\WsExchangeDevicesModule\Subscribers;
use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use Nette;
use Nette\DI;

/**
 * WS exchange & Devices module bridge extension
 *
 * @package        FastyBird:WsExchangeDevicesModuleBridge!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WsExchangeDevicesModuleExtension extends DI\CompilerExtension
{

	public const NAME = 'fbWsExchangeDevicesModuleBridge';

	public static function register(
		Nette\Configurator|BootstrapBoot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator|BootstrapBoot\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new WsExchangeDevicesModuleExtension());
		};
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition(
			$this->prefix('subscribers.ws.client'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Subscribers\WsClient::class);
	}

}
