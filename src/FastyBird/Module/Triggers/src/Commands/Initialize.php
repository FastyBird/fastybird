<?php declare(strict_types = 1);

/**
 * InitializeCommand.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Commands
 * @since          0.1.0
 *
 * @date           08.08.20
 */

namespace FastyBird\Module\Triggers\Commands;

use Psr\Log;
use RuntimeException;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;

/**
 * Module initialize command
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class InitializeCommand extends Console\Command\Command
{

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		?Log\LoggerInterface $logger = null,
		?string $name = null
	) {
		parent::__construct($name);

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function configure(): void
	{
		$this
			->setName('fb:triggers-module:initialize')
			->addOption('noconfirm', null, Input\InputOption::VALUE_NONE, 'do not ask for any confirmation')
			->setDescription('Initialize module.');
	}

	/**
	 * {@inheritDoc}
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$symfonyApp = $this->getApplication();

		if ($symfonyApp === null) {
			return 1;
		}

		$io = new Style\SymfonyStyle($input, $output);

		$io->title('FB triggers module - initialization');

		$io->note('This action will create|update module database structure.');

		/** @var bool $continue */
		$continue = $io->ask('Would you like to continue?', 'n', function ($answer): bool {
			if (!in_array($answer, ['y', 'Y', 'n', 'N'], true)) {
				throw new RuntimeException('You must type Y or N');
			}

			return in_array($answer, ['y', 'Y'], true);
		});

		if (!$continue) {
			return 0;
		}

		try {
			$io->section('Preparing module database');

			$databaseCmd = $symfonyApp->find('orm:schema-tool:update');

			$result = $databaseCmd->run(new Input\ArrayInput([
				'--force' => true,
			]), $output);

			if ($result !== 0) {
				$io->error('Something went wrong, initialization could not be finished.');

				return 1;
			}

			$databaseProxiesCmd = $symfonyApp->find('orm:generate-proxies');

			$result = $databaseProxiesCmd->run(new Input\ArrayInput([
				'--quiet' => true,
			]), $output);

			if ($result !== 0) {
				$io->error('Something went wrong, initialization could not be finished.');

				return 1;
			}

			$io->newLine(3);

			$io->success('Triggers module has been successfully initialized and can be now started.');

			return 0;

		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('An unhandled error occurred', [
				'source'    => 'triggers-module-initialize',
				'type'      => 'run',
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			$io->error('Something went wrong, initialization could not be finished. Error was logged.');

			return 1;
		}
	}

}
