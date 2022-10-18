<?php declare(strict_types = 1);

/**
 * Initialize.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Commands
 * @since          0.1.0
 *
 * @date           08.08.20
 */

namespace FastyBird\Module\Devices\Commands;

use FastyBird\Library\Metadata;
use FastyBird\Module\Devices\DataStorage;
use Psr\Log;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;

/**
 * Module initialize command
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Initialize extends Console\Command\Command
{

	public const NAME = 'fb:devices-module:initialize';

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DataStorage\Writer $writer,
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
			->setDescription('Devices module initialization')
			->setDefinition(
				new Input\InputDefinition([
					new Input\InputOption('database', 'd', Input\InputOption::VALUE_NONE, 'Initialize module database'),
					new Input\InputOption(
						'data-storage',
						's',
						Input\InputOption::VALUE_NONE,
						'Initialize module data storage',
					),
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
		$symfonyApp = $this->getApplication();

		if ($symfonyApp === null) {
			return Console\Command\Command::FAILURE;
		}

		$io = new Style\SymfonyStyle($input, $output);

		if ($input->getOption('quiet') === false) {
			$io->title('FB devices module - initialization');

			$io->note('This action will create|update module database structure and build module configuration.');
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
			if ($input->getOption('database') === true) {
				$this->initializeDatabase($io, $input, $output);
			}

			if ($input->getOption('data-storage') === true) {
				$this->initializeDataStorage($io, $input);
			}

			if ($input->getOption('quiet') === false) {
				$io->success('Devices module has been successfully initialized and can be now used.');
			}

			return Console\Command\Command::SUCCESS;
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('An unhandled error occurred', [
				'source' => Metadata\Constants::MODULE_DEVICES_SOURCE,
				'type' => 'initialize-cmd',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			if ($input->getOption('quiet') === false) {
				$io->error('Something went wrong, initialization could not be finished. Error was logged.');
			}

			return Console\Command\Command::FAILURE;
		}
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws Console\Exception\ExceptionInterface
	 */
	private function initializeDatabase(
		Style\SymfonyStyle $io,
		Input\InputInterface $input,
		Output\OutputInterface $output,
	): void
	{
		$symfonyApp = $this->getApplication();

		if ($symfonyApp === null) {
			return;
		}

		if ($input->getOption('quiet') === false) {
			$io->section('Preparing module database');
		}

		$databaseCmd = $symfonyApp->find('orm:schema-tool:update');

		$result = $databaseCmd->run(new Input\ArrayInput([
			'--force' => true,
		]), $output);

		if ($result !== Console\Command\Command::SUCCESS) {
			if ($input->getOption('quiet') === false) {
				$io->error('Something went wrong, initialization could not be finished.');
			}

			return;
		}

		$databaseProxiesCmd = $symfonyApp->find('orm:generate-proxies');

		$result = $databaseProxiesCmd->run(new Input\ArrayInput([
			'--quiet' => true,
		]), $output);

		if ($result !== 0) {
			if ($input->getOption('quiet') === false) {
				$io->error('Something went wrong, database initialization could not be finished.');
			}

			return;
		}

		if ($input->getOption('quiet') === false) {
			$io->success('Devices module database has been successfully initialized.');
		}
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	private function initializeDataStorage(Style\SymfonyStyle $io, Input\InputInterface $input): void
	{
		if ($input->getOption('quiet') === false) {
			$io->section('Preparing module data storage');
		}

		try {
			$this->writer->write();

			if ($input->getOption('quiet') === false) {
				$io->success('Devices module data storage has been successfully initialized.');
			}

			return;
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('An unhandled error occurred', [
				'source' => Metadata\Constants::MODULE_DEVICES_SOURCE,
				'type' => 'initialize-cmd',
				'exception' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
			]);

			if ($input->getOption('quiet') === false) {
				$io->error(
					'Something went wrong, data storage initialization could not be finished. Error was logged.',
				);
			}

			return;
		}
	}

}
