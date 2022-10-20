<?php declare(strict_types = 1);

/**
 * WsExchangeExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsExchange!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           22.02.21
 */

namespace FastyBird\Plugin\WsExchange\DI;

use FastyBird\Plugin\WsExchange\Commands;
use FastyBird\Plugin\WsExchange\Consumers;
use FastyBird\Plugin\WsExchange\Controllers;
use FastyBird\Plugin\WsExchange\Events;
use FastyBird\Plugin\WsExchange\Exceptions;
use FastyBird\Plugin\WsExchange\Publishers;
use FastyBird\Plugin\WsExchange\Server;
use FastyBird\Plugin\WsExchange\Subscribers;
use IPub\WebSockets;
use Nette;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use Psr\EventDispatcher;
use stdClass;
use function assert;
use function is_string;
use function sprintf;

/**
 * WS server plugin
 *
 * @package        FastyBird:WsExchange!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WsExchangeExtension extends DI\CompilerExtension
{

	public const NAME = 'fbWsExchangePlugin';

	public static function register(
		Nette\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new WsExchangeExtension());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'access' => Schema\Expect::structure([
				'keys' => Schema\Expect::string()->default(null),
				'origins' => Schema\Expect::string()->default(null),
			]),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition($this->prefix('subscribers.client'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Client::class)
			->setArgument('wsKeys', $configuration->access->keys)
			->setArgument('allowedOrigins', $configuration->access->origins);

		// Controllers
		$builder->addDefinition($this->prefix('controllers.exchange'), new DI\Definitions\ServiceDefinition())
			->setType(Controllers\Exchange::class)
			->addTag('nette.inject');

		// Publisher
		$builder->addDefinition($this->prefix('exchange.publisher'), new DI\Definitions\ServiceDefinition())
			->setType(Publishers\Publisher::class);

		// Consumers
		$builder->addDefinition($this->prefix('consumers.exchange'), new DI\Definitions\ServiceDefinition())
			->setType(Consumers\Consumer::class)
			->setAutowired(false);

		// Commands
		$builder->addDefinition($this->prefix('command.server'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\WsServer::class);

		// Server
		$builder->addDefinition($this->prefix('server.factory'), new DI\Definitions\ServiceDefinition())
			->setType(Server\Factory::class)
			->setArguments([
				'exchangeConsumer' => '@' . $this->prefix('consumers.exchange'),
			]);
	}

	/**
	 * @throws Exceptions\Logic
	 * @throws Nette\DI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * Events bridges
		 */

		if ($builder->getByType(EventDispatcher\EventDispatcherInterface::class) === null) {
			throw new Exceptions\Logic(
				sprintf(
					'Service of type "%s" is needed. Please register it.',
					EventDispatcher\EventDispatcherInterface::class,
				),
			);
		}

		$dispatcher = $builder->getDefinition($builder->getByType(EventDispatcher\EventDispatcherInterface::class));

		$socketWrapperServiceName = $builder->getByType(WebSockets\Server\Wrapper::class);
		assert(is_string($socketWrapperServiceName));

		$socketWrapperService = $builder->getDefinition($socketWrapperServiceName);
		assert($socketWrapperService instanceof DI\Definitions\ServiceDefinition);

		$socketWrapperService->addSetup(
			'?->onClientConnected[] = function() {?->dispatch(new ?(...func_get_args()));}',
			[
				'@self',
				$dispatcher,
				new PhpGenerator\Literal(Events\ClientConnected::class),
			],
		);

		$socketWrapperService->addSetup(
			'?->onIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}',
			[
				'@self',
				$dispatcher,
				new PhpGenerator\Literal(Events\IncomingMessage::class),
			],
		);
	}

}
