<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Commands
 * @since          0.60.0
 *
 * @date           28.10.22
 */

namespace FastyBird\Module\Devices\Commands;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use Psr\EventDispatcher as PsrEventDispatcher;
use Psr\Log;
use React\EventLoop;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;

/**
 * Module exchange command
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Exchange extends Console\Command\Command
{

	public const NAME = 'fb:devices-module:exchange';

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher,
		Log\LoggerInterface|null $logger = null,
		string|null $name = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();

		parent::__construct($name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		$this
			->setName(self::NAME)
			->setDescription('Devices module exchange')
			->setDefinition(
				new Input\InputDefinition([
					new Input\InputOption(
						'no-confirm',
						null,
						Input\InputOption::VALUE_NONE,
						'Do not ask for any confirmation',
					),
					new Input\InputOption('quiet', 'q', Input\InputOption::VALUE_NONE, 'Do not output any message'),
				]),
			);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		if ($input->getOption('quiet') === false) {
			$io->title('Devices module - exchange');

			$io->note('This action will run module exchange service.');
		}

		if ($input->getOption('no-confirm') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				'Would you like to continue?',
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				return Console\Command\Command::SUCCESS;
			}
		}

		try {
			$this->dispatcher?->dispatch(new Events\ExchangeStartup());

			$this->eventLoop->run();

			return Console\Command\Command::SUCCESS;
		} catch (Exceptions\Terminate $ex) {
			$this->logger->debug('Stopping exchange', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
				'type' => 'command',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			$this->eventLoop->stop();

			return Console\Command\Command::SUCCESS;
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('An unhandled error occurred', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
				'type' => 'command',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			if ($input->getOption('quiet') === false) {
				$io->error('Something went wrong, service could not be finished. Error was logged.');
			}

			return Console\Command\Command::FAILURE;
		}
	}

}
