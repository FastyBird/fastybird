<?php declare(strict_types = 1);

/**
 * Initialize.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           10.07.23
 */

namespace FastyBird\Connector\NsPanel\Commands;

use Doctrine\DBAL;
use Doctrine\Persistence;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette\Localization;
use Nette\Utils;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function array_key_exists;
use function array_search;
use function array_values;
use function assert;
use function count;
use function sprintf;
use function strval;
use function usort;

/**
 * Connector initialize command
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Initialize extends Console\Command\Command
{

	public const NAME = 'fb:ns-panel-connector:initialize';

	public function __construct(
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Connectors\ConnectorsManager $connectorsManager,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly Persistence\ManagerRegistry $managerRegistry,
		private readonly Localization\Translator $translator,
		private readonly NsPanel\Logger $logger,
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
			->setDescription('NS Panel connector initialization');
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title($this->translator->translate('//ns-panel-connector.cmd.initialize.title'));

		$io->note($this->translator->translate('//ns-panel-connector.cmd.initialize.subtitle'));

		if ($input->getOption('no-interaction') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.base.questions.continue'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				return Console\Command\Command::SUCCESS;
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//ns-panel-connector.cmd.initialize.actions.create'),
				1 => $this->translator->translate('//ns-panel-connector.cmd.initialize.actions.update'),
				2 => $this->translator->translate('//ns-panel-connector.cmd.initialize.actions.remove'),
				3 => $this->translator->translate('//ns-panel-connector.cmd.initialize.actions.list'),
				4 => $this->translator->translate('//ns-panel-connector.cmd.initialize.actions.nothing'),
			],
		);

		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if ($whatToDo === $this->translator->translate('//ns-panel-connector.cmd.initialize.actions.create')) {
			$this->createConfiguration($io);

		} elseif ($whatToDo === $this->translator->translate('//ns-panel-connector.cmd.initialize.actions.update')) {
			$this->editConfiguration($io);

		} elseif ($whatToDo === $this->translator->translate('//ns-panel-connector.cmd.initialize.actions.remove')) {
			$this->deleteConfiguration($io);

		} elseif ($whatToDo === $this->translator->translate('//ns-panel-connector.cmd.initialize.actions.list')) {
			$this->listConfigurations($io);
		}

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function createConfiguration(Style\SymfonyStyle $io): void
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//ns-panel-connector.cmd.initialize.questions.provide.identifier'),
		);

		$question->setValidator(function ($answer) {
			if ($answer !== null) {
				$findConnectorQuery = new Queries\FindConnectors();
				$findConnectorQuery->byIdentifier($answer);

				if ($this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\NsPanelConnector::class,
				) !== null) {
					throw new Exceptions\Runtime(
						$this->translator->translate('//ns-panel-connector.cmd.initialize.messages.identifier.used'),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'ns-panel-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findConnectorQuery = new Queries\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				if ($this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\NsPanelConnector::class,
				) === null) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error($this->translator->translate('//ns-panel-connector.cmd.initialize.messages.identifier.missing'));

			return;
		}

		$name = $this->askName($io);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\NsPanelConnector::class,
				'identifier' => $identifier,
				'name' => $name === '' ? null : $name,
			]));

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//ns-panel-connector.cmd.initialize.messages.create.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'initialize-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//ns-panel-connector.cmd.initialize.messages.create.error'));
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function editConfiguration(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->warning($this->translator->translate('//ns-panel-connector.cmd.initialize.messages.noConnectors'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.initialize.questions.create'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createConfiguration($io);
			}

			return;
		}

		$name = $this->askName($io, $connector);

		$enabled = $connector->isEnabled();

		if ($connector->isEnabled()) {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.initialize.questions.disable'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = false;
			}
		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.initialize.questions.enable'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = true;
			}
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->update($connector, Utils\ArrayHash::from([
				'name' => $name === '' ? null : $name,
				'enabled' => $enabled,
			]));

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//ns-panel-connector.cmd.initialize.messages.update.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'initialize-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//ns-panel-connector.cmd.initialize.messages.update.error'));
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function deleteConfiguration(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info($this->translator->translate('//ns-panel-connector.cmd.initialize.messages.noConnectors'));

			return;
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->connectorsManager->delete($connector);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//ns-panel-connector.cmd.initialize.messages.remove.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'initialize-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//ns-panel-connector.cmd.initialize.messages.remove.error'));
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function listConfigurations(Style\SymfonyStyle $io): void
	{
		/** @var DevicesQueries\FindConnectors<Entities\NsPanelConnector> $findConnectorsQuery */
		$findConnectorsQuery = new Queries\FindConnectors();

		/** @var array<Entities\NsPanelConnector> $connectors */
		$connectors = $this->connectorsRepository->findAllBy($findConnectorsQuery, Entities\NsPanelConnector::class);
		usort(
			$connectors,
			static function (Entities\NsPanelConnector $a, Entities\NsPanelConnector $b): int {
				if ($a->getIdentifier() === $b->getIdentifier()) {
					return $a->getName() <=> $b->getName();
				}

				return $a->getIdentifier() <=> $b->getIdentifier();
			},
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//ns-panel-connector.cmd.initialize.data.name'),
			$this->translator->translate('//ns-panel-connector.cmd.initialize.data.panelsCnt'),
			$this->translator->translate('//ns-panel-connector.cmd.initialize.data.subDevicesCnt'),
			$this->translator->translate('//ns-panel-connector.cmd.initialize.data.devicesCnt'),
		]);

		foreach ($connectors as $index => $connector) {
			$findDevicesQuery = new Queries\FindGatewayDevices();
			$findDevicesQuery->forConnector($connector);

			$nsPanels = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class);

			$findDevicesQuery = new Queries\FindSubDevices();
			$findDevicesQuery->forConnector($connector);

			$subDevices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\SubDevice::class);

			$findDevicesQuery = new Queries\FindThirdPartyDevices();
			$findDevicesQuery->forConnector($connector);

			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Device::class);

			$table->addRow([
				$index + 1,
				$connector->getName() ?? $connector->getIdentifier(),
				count($nsPanels),
				count($subDevices),
				count($devices),
			]);
		}

		$table->render();

		$io->newLine();
	}

	private function askName(Style\SymfonyStyle $io, Entities\NsPanelConnector|null $connector = null): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//ns-panel-connector.cmd.initialize.questions.provide.name'),
			$connector?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\NsPanelConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new Queries\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\NsPanelConnector::class,
		);
		usort(
			$systemConnectors,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			static fn (Entities\NsPanelConnector $a, Entities\NsPanelConnector $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($systemConnectors as $connector) {
			$connectors[$connector->getIdentifier()] = $connector->getIdentifier()
				. ($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
		}

		if (count($connectors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.initialize.questions.select.connector'),
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer) use ($connectors): Entities\NsPanelConnector {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($connectors))) {
				$answer = array_values($connectors)[$answer];
			}

			$identifier = array_search($answer, $connectors, true);

			if ($identifier !== false) {
				$findConnectorQuery = new Queries\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\NsPanelConnector::class,
				);

				if ($connector !== null) {
					return $connector;
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$connector = $io->askQuestion($question);
		assert($connector instanceof Entities\NsPanelConnector);

		return $connector;
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	private function getOrmConnection(): DBAL\Connection
	{
		$connection = $this->managerRegistry->getConnection();

		if ($connection instanceof DBAL\Connection) {
			return $connection;
		}

		throw new Exceptions\Runtime('Transformer manager could not be loaded');
	}

}