<?php declare(strict_types = 1);

/**
 * Install.php
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

use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette\Localization;
use Psr\Log;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;

/**
 * Module initialize command
 *
 * @package        FastyBird:AccountsModule!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Install extends Console\Command\Command
{

	public const NAME = 'fb:accounts-module:install';

	public function __construct(
		private readonly Localization\Translator $translator,
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
			->setDescription('Accounts module installer');
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
			$io->title($this->translator->translate('//accounts-module.cmd.install.title'));

			$io->note($this->translator->translate('//accounts-module.cmd.install.subtitle'));
		}

		if ($input->getOption('no-interaction') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//accounts-module.cmd.base.questions.continue'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				return Console\Command\Command::SUCCESS;
			}
		}

		try {
			$this->initializeDatabase($io, $input, $output);

			if ($input->getOption('quiet') === false) {
				$io->success($this->translator->translate('//accounts-module.cmd.install.messages.success'));
			}

			return Console\Command\Command::SUCCESS;
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('An unhandled error occurred', [
				'source' => MetadataTypes\ModuleSource::ACCOUNTS,
				'type' => 'initialize-cmd',
				'exception' => ApplicationHelpers\Logger::buildException($ex),
			]);

			if ($input->getOption('quiet') === false) {
				$io->error($this->translator->translate('//accounts-module.cmd.install.messages.error'));
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
			$io->section($this->translator->translate('//accounts-module.cmd.install.info.database'));
		}

		$databaseCmd = $symfonyApp->find('orm:schema-tool:update');

		$result = $databaseCmd->run(new Input\ArrayInput([
			'--force' => true,
		]), $output);

		if ($result !== Console\Command\Command::SUCCESS) {
			if ($input->getOption('quiet') === false) {
				$io->error(
					$this->translator->translate('//accounts-module.cmd.install.messages.initialisationFailed'),
				);
			}

			return;
		}

		$databaseProxiesCmd = $symfonyApp->find('orm:generate-proxies');

		$result = $databaseProxiesCmd->run(new Input\ArrayInput([
			'--quiet' => true,
		]), $output);

		if ($result !== 0) {
			if ($input->getOption('quiet') === false) {
				$io->error($this->translator->translate('//accounts-module.cmd.install.messages.databaseFailed'));
			}

			return;
		}

		if ($input->getOption('quiet') === false) {
			$io->success($this->translator->translate('//accounts-module.cmd.install.messages.databaseReady'));
		}
	}

}
