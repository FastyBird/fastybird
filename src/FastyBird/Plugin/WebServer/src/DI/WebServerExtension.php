<?php declare(strict_types = 1);

/**
 * WebServerExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           21.03.20
 */

namespace FastyBird\Plugin\WebServer\DI;

use FastyBird\Library\Application\Boot as ApplicationBoot;
use FastyBird\Plugin\WebServer\Application;
use FastyBird\Plugin\WebServer\Commands;
use FastyBird\Plugin\WebServer\Http;
use FastyBird\Plugin\WebServer\Middleware;
use FastyBird\Plugin\WebServer\Router;
use FastyBird\Plugin\WebServer\Server;
use FastyBird\Plugin\WebServer\Subscribers;
use Fig\Http\Message\RequestMethodInterface;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;

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

	public static function register(
		ApplicationBoot\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			ApplicationBoot\Configurator $config,
			DI\Compiler $compiler,
		) use (
			$extensionName,
		): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'static' => Schema\Expect::structure([
				'publicRoot' => Schema\Expect::string()->nullable(),
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
			->setArgument('publicRoot', $configuration->static->publicRoot)
			->setArgument('enabled', $configuration->static->enabled);

		$builder->addDefinition($this->prefix('middlewares.router'), new DI\Definitions\ServiceDefinition())
			->setType(Middleware\Router::class);

		$builder->addDefinition($this->prefix('application.classic'), new DI\Definitions\ServiceDefinition())
			->setType(Application\Application::class);

		$builder->addDefinition($this->prefix('server.factory'), new DI\Definitions\ServiceDefinition())
			->setType(Server\Factory::class);

		$builder->addDefinition($this->prefix('subscribers.server'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Server::class);
	}

}
