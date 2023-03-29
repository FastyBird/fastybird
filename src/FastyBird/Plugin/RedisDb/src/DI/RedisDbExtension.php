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

use FastyBird\Library\Bootstrap\Boot as BootstrapBoot;
use FastyBird\Library\Metadata;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Connections;
use FastyBird\Plugin\RedisDb\Handlers;
use FastyBird\Plugin\RedisDb\Models;
use FastyBird\Plugin\RedisDb\Publishers;
use FastyBird\Plugin\RedisDb\Subscribers;
use FastyBird\Plugin\RedisDb\Utilities;
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

		$publisher = $builder->addDefinition($this->prefix('publisher'), new DI\Definitions\ServiceDefinition())
			->setType(Publishers\Publisher::class)
			->setArguments([
				'channel' => $configuration->exchange->channel,
			]);

		$builder->addDefinition($this->prefix('redis.connection'), new DI\Definitions\ServiceDefinition())
			->setType(Connections\Connection::class)
			->setArguments([
				'host' => $configuration->client->host,
				'port' => $configuration->client->port,
				'username' => $configuration->client->username,
				'password' => $configuration->client->password,
			]);

		$builder->addDefinition($this->prefix('clients.sync'), new DI\Definitions\ServiceDefinition())
			->setType(Clients\Client::class);

		$builder->addDefinition($this->prefix('clients.async.factory'), new DI\Definitions\ServiceDefinition())
			->setType(Clients\Factory::class)
			->setArguments([
				'channel' => $configuration->exchange->channel,
			]);

		$builder->addDefinition($this->prefix('models.statesManagerFactory'), new DI\Definitions\ServiceDefinition())
			->setType(Models\StatesManagerFactory::class);

		$builder->addDefinition($this->prefix('models.statesRepositoryFactory'), new DI\Definitions\ServiceDefinition())
			->setType(Models\StatesRepositoryFactory::class);

		$builder->addDefinition($this->prefix('handlers.message'), new DI\Definitions\ServiceDefinition())
			->setType(Handlers\Message::class);

		$builder->addDefinition($this->prefix('utilities.identifier'), new DI\Definitions\ServiceDefinition())
			->setType(Utilities\IdentifierGenerator::class);

		$builder->addDefinition($this->prefix('subscribers.client'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Client::class)
			->setArguments([
				'publisher' => $publisher,
			]);
	}

}
