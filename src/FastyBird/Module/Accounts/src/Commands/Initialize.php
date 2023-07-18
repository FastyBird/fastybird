<?php declare(strict_types = 1);

/**
 * Initialize.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:AccountsModule!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           08.08.20
 */

namespace FastyBird\Module\Accounts\Commands;

use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Psr\Log;
use RuntimeException;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function assert;
use function in_array;
use function is_bool;

/**
 * Module initialize command
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Initialize extends Console\Command\Command
{

	public const NAME = 'fb:accounts-module:initialize';

	public function __construct(
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
		string|null $name = null,
	)
	{
		parent::__construct($name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		$this
			->setName(self::NAME)
			->addOption('noconfirm', null, Input\InputOption::VALUE_NONE, 'do not ask for any confirmation')
			->setDescription('Initialize module.');
	}

	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$symfonyApp = $this->getApplication();

		if ($symfonyApp === null) {
			return 1;
		}

		$io = new Style\SymfonyStyle($input, $output);

		$io->title('FB accounts module - initialization');

		$io->note('This action will create|update module database structure.');

		$continue = $io->ask('Would you like to continue?', 'n', static function ($answer): bool {
			if (!in_array($answer, ['y', 'Y', 'n', 'N'], true)) {
				throw new RuntimeException('You must type Y or N');
			}

			return in_array($answer, ['y', 'Y'], true);
		});
		assert(is_bool($continue));

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

			$io->success('Accounts module has been successfully initialized and can be now started.');

			return 0;
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('An unhandled error occurred', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_ACCOUNTS,
				'type' => 'command',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
			]);

			$io->error('Something went wrong, initialization could not be finished. Error was logged.');

			return 1;
		}
	}

}
