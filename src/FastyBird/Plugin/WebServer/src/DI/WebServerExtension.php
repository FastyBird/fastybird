<?php declare(strict_types = 1);

/**
 * WebServerExtension.php
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

namespace FastyBird\Plugin\WebServer\DI;

use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Plugin\WebServer\Application;
use FastyBird\Plugin\WebServer\Commands;
use FastyBird\Plugin\WebServer\Exceptions;
use FastyBird\Plugin\WebServer\Http;
use FastyBird\Plugin\WebServer\Middleware;
use FastyBird\Plugin\WebServer\Router;
use FastyBird\Plugin\WebServer\Server;
use FastyBird\Plugin\WebServer\Subscribers;
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
class WebServerExtension extends DI\CompilerExtension
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
		Nette\Configurator|BootstrapBoot\Configurator $config,
		bool $cliMode = false,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator|BootstrapBoot\Configurator $config,
			DI\Compiler $compiler,
		) use (
			$extensionName,
			$cliMode,
		): void {
			$compiler->addExtension($extensionName, new WebServerExtension($cliMode));
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
			->setType(Router\Router::class);

		$builder->addDefinition($this->prefix('commands.server'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\HttpServer::class)
			->setArguments([
				'serverAddress' => $configuration->server->address,
				'serverPort' => $configuration->server->port,
				'serverCertificate' => $configuration->server->certificate,
			]);

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

		if ($this->cliMode === true) {
			$builder->addDefinition($this->prefix('application.console'), new DI\Definitions\ServiceDefinition())
				->setType(Application\Console::class);
		}

		$builder->addDefinition($this->prefix('application.classic'), new DI\Definitions\ServiceDefinition())
			->setType(Application\Application::class);

		$builder->addDefinition($this->prefix('server.factory'), new DI\Definitions\ServiceDefinition())
			->setType(Server\Factory::class);

		$builder->addDefinition($this->prefix('subscribers.server'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Server::class);
	}

}
