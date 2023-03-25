<?php declare(strict_types = 1);

/**
 * RedisClient.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Commands
 * @since          0.61.0
 *
 * @date           09.10.22
 */

namespace FastyBird\Plugin\RedisDb\Commands;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RedisDb\Client;
use FastyBird\Plugin\RedisDb\Events;
use FastyBird\Plugin\RedisDb\Exceptions;
use Nette;
use Psr\EventDispatcher;
use Psr\Log;
use React\EventLoop;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Throwable;

/**
 * Redis client command
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Commands
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class RedisClient extends Console\Command\Command
{

	use Nette\SmartObject;

	public const NAME = 'fb:redis-client:start';

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Client\Factory $clientFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		Log\LoggerInterface|null $logger = null,
		string|null $name = null,
	)
	{
		parent::__construct($name);

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		parent::configure();

		$this
			->setName(self::NAME)
			->setDescription('Start redis client');
	}

	protected function execute(
		Input\InputInterface $input,
		Output\OutputInterface $output,
	): int
	{
		$this->logger->info(
			'Launching Redis client',
			[
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
				'type' => 'client-command',
				'group' => 'cmd',
			],
		);

		try {
			$this->dispatcher?->dispatch(new Events\Startup());

			$this->clientFactory->create($this->eventLoop);

			$this->eventLoop->run();

		} catch (Exceptions\Terminate $ex) {
			// Log error action reason
			$this->logger->error(
				'Redis client was forced to close',
				[
					'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
					'type' => 'client-command',
					'group' => 'cmd',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'cmd' => $this->getName(),
				],
			);

			$this->eventLoop->stop();

		} catch (Throwable $ex) {
			// Log error action reason
			$this->logger->error(
				'An unhandled error occurred. Stopping Redis client',
				[
					'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_REDISDB,
					'type' => 'client-command',
					'group' => 'cmd',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
					'cmd' => $this->getName(),
				],
			);

			$this->eventLoop->stop();

			return self::FAILURE;
		}

		return self::SUCCESS;
	}

}
