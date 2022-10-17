<?php declare(strict_types = 1);

/**
 * WebServerPluginExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           21.03.20
 */

namespace FastyBird\WebServerPlugin\DI;

use FastyBird\WebServerPlugin\Application;
use FastyBird\WebServerPlugin\Commands;
use FastyBird\WebServerPlugin\Exceptions;
use FastyBird\WebServerPlugin\Http;
use FastyBird\WebServerPlugin\Middleware;
use FastyBird\WebServerPlugin\Server;
use Fig\Http\Message\RequestMethodInterface;
use IPub\SlimRouter;
use Nette;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;
use function func_num_args;
use function sprintf;

/**
 * Simple web server extension container
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WebServerPluginExtension extends DI\CompilerExtension
{

	public const NAME = 'fbWebServerPlugin';

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function __construct(private readonly bool $cliMode = false)
	{
		if (func_num_args() <= 0) {
			throw new Exceptions\InvalidArgument(sprintf('Provide CLI mode, e.q. %s(%%consoleMode%%).', self::class));
		}
	}

	public static function register(
		Nette\Configurator $config,
		bool $cliMode = false,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator $config,
			DI\Compiler $compiler,
		) use (
			$extensionName,
			$cliMode,
		): void {
			$compiler->addExtension($extensionName, new WebServerPluginExtension($cliMode));
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'static' => Schema\Expect::structure([
				'webroot' => Schema\Expect::string(null)->nullable(),
				'enabled' => Schema\Expect::bool(false),
			]),
			'server' => Schema\Expect::structure([
				'address' => Schema\Expect::string('127.0.0.1'),
				'port' => Schema\Expect::int(8_000),
				'certificate' => Schema\Expect::string()
					->nullable(),
			]),
			'cors' => Schema\Expect::structure([
				'enabled' => Schema\Expect::bool(false),
				'allow' => Schema\Expect::structure([
					'origin' => Schema\Expect::string('*'),
					'methods' => Schema\Expect::arrayOf('string')
						->default([
							RequestMethodInterface::METHOD_GET,
							RequestMethodInterface::METHOD_POST,
							RequestMethodInterface::METHOD_PATCH,
							RequestMethodInterface::METHOD_DELETE,
							RequestMethodInterface::METHOD_OPTIONS,
						]),
					'credentials' => Schema\Expect::bool(true),
					'headers' => Schema\Expect::arrayOf('string')
						->default([
							'Content-Type',
							'Authorization',
							'X-Requested-With',
						]),
				]),
			]),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition($this->prefix('routing.responseFactory'), new DI\Definitions\ServiceDefinition())
			->setType(Http\ResponseFactory::class);

		$builder->addDefinition($this->prefix('routing.router'), new DI\Definitions\ServiceDefinition())
			->setType(SlimRouter\Routing\Router::class);

		$builder->addDefinition($this->prefix('command.server'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\HttpServer::class)
			->setArguments([
				'serverAddress' => $configuration->server->address,
				'serverPort' => $configuration->server->port,
				'serverCertificate' => $configuration->server->certificate,
			]);

		// Web server middlewares
		$builder->addDefinition($this->prefix('middlewares.cors'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Cors::class)
			->setArguments([
				'enabled' => $configuration->cors->enabled,
				'allowOrigin' => $configuration->cors->allow->origin,
				'allowMethods' => $configuration->cors->allow->methods,
				'allowCredentials' => $configuration->cors->allow->credentials,
				'allowHeaders' => $configuration->cors->allow->headers,
			]);

		$builder->addDefinition($this->prefix('middlewares.staticFiles'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\StaticFiles::class)
			->setArgument('publicRoot', $configuration->static->webroot)
			->setArgument('enabled', $configuration->static->enabled);

		$builder->addDefinition($this->prefix('middlewares.router'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Router::class);

		// Applications

		if ($this->cliMode === true) {
			$builder->addDefinition($this->prefix('application.console'), new DI\Definitions\ServiceDefinition())
				->setType(Application\Console::class);
		}

		$builder->addDefinition($this->prefix('application.classic'), new DI\Definitions\ServiceDefinition())
			->setType(Application\Application::class);

		// Web server factory

		$builder->addDefinition($this->prefix('server.factory'), new DI\Definitions\ServiceDefinition())
			->setType(Server\Factory::class);
	}

}
