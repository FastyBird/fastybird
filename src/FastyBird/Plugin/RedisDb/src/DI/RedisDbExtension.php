<?php declare(strict_types = 1);

/**
 * RedisDbExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           25.02.21
 */

namespace FastyBird\Plugin\RedisDb\DI;

use FastyBird\Library\Metadata;
use FastyBird\Plugin\RedisDb\Client;
use FastyBird\Plugin\RedisDb\Commands;
use FastyBird\Plugin\RedisDb\Connections;
use FastyBird\Plugin\RedisDb\Handlers;
use FastyBird\Plugin\RedisDb\Models;
use FastyBird\Plugin\RedisDb\Publishers;
use FastyBird\Plugin\RedisDb\Subscribers;
use FastyBird\Plugin\RedisDb\Utils;
use Nette;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;

/**
 * Message exchange extension container
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisDbExtension extends DI\CompilerExtension
{

	public const NAME = 'fbRedisDbPlugin';

	public static function register(
		Nette\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Nette\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new RedisDbExtension());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'client' => Schema\Expect::structure([
				'host' => Schema\Expect::string()->default('127.0.0.1'),
				'port' => Schema\Expect::int(6_379),
				'username' => Schema\Expect::string()->nullable(),
				'password' => Schema\Expect::string()->nullable(),
			]),
			'exchange' => Schema\Expect::structure([
				'channel' => Schema\Expect::string()->default(Metadata\Constants::EXCHANGE_CHANNEL_NAME),
			]),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition($this->prefix('publisher.exchange'), new DI\Definitions\ServiceDefinition())
			->setType(Publishers\Publisher::class)
			->setArguments([
				'channel' => $configuration->exchange->channel,
			]);

		$builder->addDefinition(
			$this->prefix('redis.connection'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Connections\Connection::class)
			->setArguments([
				'host' => $configuration->client->host,
				'port' => $configuration->client->port,
				'username' => $configuration->client->username,
				'password' => $configuration->client->password,
			]);

		$builder->addDefinition(
			$this->prefix('client.sync'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Client\Client::class);

		$builder->addDefinition(
			$this->prefix('client.async.factory'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Client\Factory::class)
			->setArguments([
				'channel' => $configuration->exchange->channel,
			]);

		// Models

		$builder->addDefinition(
			$this->prefix('model.statesManagerFactory'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\StatesManagerFactory::class);

		$builder->addDefinition(
			$this->prefix('model.statesRepositoryFactory'),
			new DI\Definitions\ServiceDefinition(),
		)
			->setType(Models\StatesRepositoryFactory::class);

		// Handlers

		$builder->addDefinition($this->prefix('handler.message'), new DI\Definitions\ServiceDefinition())
			->setType(Handlers\Message::class);

		// Commands

		$builder->addDefinition($this->prefix('command.client'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\RedisClient::class);

		// Utilities

		$builder->addDefinition($this->prefix('utils.identifier'), new DI\Definitions\ServiceDefinition())
			->setType(Utils\IdentifierGenerator::class);

		// Subscribers

		$builder->addDefinition($this->prefix('subscriber.client'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Client::class);
	}

}
