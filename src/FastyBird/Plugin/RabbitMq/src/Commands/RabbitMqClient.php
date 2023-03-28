<?php declare(strict_types = 1);

/**
 * RabbitMqClient.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           03.03.20
 */

namespace FastyBird\Plugin\RabbitMq\Commands;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RabbitMq\Channels;
use FastyBird\Plugin\RabbitMq\Events;
use FastyBird\Plugin\RabbitMq\Exceptions;
use Nette;
use Psr\EventDispatcher;
use Psr\Log;
use React\EventLoop;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Throwable;

/**
 * Exchange messages consumer console command
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class RabbitMqClient extends Console\Command\Command
{

	use Nette\SmartObject;

	public const NAME = 'fb:rabbitmq-client:start';

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Channels\Factory $channelFactory,
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
			->setDescription('Start RabbitMQ client');
	}

	protected function execute(
		Input\InputInterface $input,
		Output\OutputInterface $output,
	): int
	{
		$this->logger->info(
			'Launching RabbitMQ client',
			[
				'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_RABBITMQ,
				'type' => 'client-command',
				'group' => 'cmd',
			],
		);

		try {
			$this->dispatcher?->dispatch(new Events\Startup());

			$this->channelFactory->create();

			$this->eventLoop->run();

		} catch (Exceptions\Terminate $ex) {
			// Log error action reason
			$this->logger->error(
				'RabbitMQ client was forced to close',
				[
					'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_RABBITMQ,
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
				'An unhandled error occurred. Stopping RabbitMQ client',
				[
					'source' => MetadataTypes\PluginSource::SOURCE_PLUGIN_RABBITMQ,
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
