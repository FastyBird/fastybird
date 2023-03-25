<?php declare(strict_types = 1);

/**
 * RabbitMqExtension.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           19.06.20
 */

namespace FastyBird\Plugin\RabbitMq\DI;

use FastyBird\ApplicationExchange\Consumer as ApplicationExchangeConsumer;
use FastyBird\Plugin\RabbitMq;
use FastyBird\Plugin\RabbitMq\Commands;
use FastyBird\Plugin\RabbitMq\Connections;
use FastyBird\Plugin\RabbitMq\Consumer;
use FastyBird\Plugin\RabbitMq\Publisher;
use FastyBird\Plugin\RabbitMq\Subscribers;
use Nette;
use Nette\DI;
use Nette\Schema;
use stdClass;

/**
 * Message exchange extension container
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RabbitMqExtension extends DI\CompilerExtension
{

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 *
	 * @return void
	 */
	public static function register(
		Nette\Configurator $config,
		string $extensionName = 'fbRabbitMqPlugin'
	): void {
		$config->onCompile[] = function (
			Nette\Configurator $config,
			DI\Compiler $compiler
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new RabbitMqExtension());
		};
	}

	/**
	 * {@inheritDoc}
	 */
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'origins'  => Schema\Expect::array([])->items(Schema\Expect::string())->default([]),
			'rabbitMQ' => Schema\Expect::structure([
				'connection' => Schema\Expect::structure([
					'host'     => Schema\Expect::string()->default('127.0.0.1'),
					'port'     => Schema\Expect::int(5672),
					'vhost'    => Schema\Expect::string('/'),
					'username' => Schema\Expect::string('guest'),
					'password' => Schema\Expect::string('guest'),
				]),
				'queue'      => Schema\Expect::structure([
					'name' => Schema\Expect::string()->default(null),
				]),
				'routing'    => Schema\Expect::structure([
					'keys' => Schema\Expect::array([])->items(Schema\Expect::string())->default(null),
				]),
			]),
		]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		/** @var stdClass $configuration */
		$configuration = $this->getConfig();

		$builder->addDefinition($this->prefix('connection'))
			->setType(Connections\RabbitMqConnection::class)
			->setArguments([
				'host'     => $configuration->rabbitMQ->connection->host,
				'port'     => $configuration->rabbitMQ->connection->port,
				'vhost'    => $configuration->rabbitMQ->connection->vhost,
				'username' => $configuration->rabbitMQ->connection->username,
				'password' => $configuration->rabbitMQ->connection->password,
			]);

		$exchange = $builder->addDefinition($this->prefix('consumer'))
			->setType(Consumer\ConsumerProxy::class);

		if ($configuration->rabbitMQ->queue->name !== null) {
			$exchange->addSetup('?->setQueueName(?)', [
				'@self',
				$configuration->rabbitMQ->queue->name,
			]);
		}

		$builder->addDefinition($this->prefix('publisher'))
			->setType(Publisher\Publisher::class)
			->setAutowired(false);

		$builder->addDefinition($this->prefix('exchange'))
			->setType(RabbitMqPlugin\Exchange::class)
			->setArguments([
				'origins'     => $configuration->origins,
				'routingKeys' => $configuration->rabbitMQ->routing->keys,
			]);

		$builder->addDefinition($this->prefix('commands.consume'))
			->setType(Commands\ConsumerCommand::class);

		$builder->addDefinition($this->prefix('subscribers.initialize'))
			->setType(Subscribers\InitializeSubscriber::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/** @var string $consumerProxyServiceName */
		$consumerProxyServiceName = $builder->getByType(Consumer\ConsumerProxy::class, true);

		/** @var DI\Definitions\ServiceDefinition $consumerProxyService */
		$consumerProxyService = $builder->getDefinition($consumerProxyServiceName);

		$consumerServices = $builder->findByType(ApplicationExchangeConsumer\IConsumer::class);

		foreach ($consumerServices as $consumerService) {
			$consumerProxyService->addSetup('?->registerConsumer(?)', [
				'@self',
				$consumerService,
			]);
		}
	}

}
