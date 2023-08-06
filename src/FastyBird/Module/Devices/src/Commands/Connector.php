<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           31.05.22
 */

namespace FastyBird\Module\Devices\Commands;

use BadMethodCallException;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Exchange\Exchange as ExchangeExchange;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Connectors;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Utilities;
use Nette\Utils;
use Psr\EventDispatcher as PsrEventDispatcher;
use Psr\Log;
use Ramsey\Uuid;
use React\EventLoop;
use SplObjectStorage;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Symfony\Component\EventDispatcher;
use Throwable;
use function array_search;
use function array_values;
use function assert;
use function count;
use function intval;
use function is_string;
use function React\Async\async;
use const SIGINT;
use const SIGTERM;

/**
 * Module connector command
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Connector extends Console\Command\Command implements EventDispatcher\EventSubscriberInterface
{

	public const NAME = 'fb:devices-module:connector';

	private const SHUTDOWN_WAITING_DELAY = 3;

	private const DATABASE_REFRESH_INTERVAL = 5;

	private const DISCOVERY_MAX_PROCESSING_INTERVAL = 60.0;

	private Entities\Connectors\Connector|null $connector = null;

	private Connectors\Connector|null $service = null;

	/** @var SplObjectStorage<Connectors\ConnectorFactory, string> */
	private SplObjectStorage $factories;

	private EventLoop\TimerInterface|null $databaseRefreshTimer = null;

	private EventLoop\TimerInterface|null $progressBarTimer = null;

	/**
	 * @param array<ExchangeExchange\Factory> $exchangeFactories
	 */
	public function __construct(
		private readonly Models\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly Models\Connectors\Properties\PropertiesRepository $connectorPropertiesRepository,
		private readonly Models\Connectors\Controls\ControlsRepository $connectorControlsRepository,
		private readonly Models\Devices\DevicesRepository $devicesRepository,
		private readonly Utilities\ConnectorConnection $connectorConnectionManager,
		private readonly Utilities\DeviceConnection $deviceConnectionManager,
		private readonly Utilities\ConnectorPropertiesStates $connectorPropertiesStateManager,
		private readonly Utilities\DevicePropertiesStates $devicePropertiesStateManager,
		private readonly Utilities\ChannelPropertiesStates $channelPropertiesStateManager,
		private readonly BootstrapHelpers\Database $database,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly array $exchangeFactories = [],
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
		string|null $name = null,
	)
	{
		$this->factories = new SplObjectStorage();

		parent::__construct($name);
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\TerminateConnector::class => 'terminateConnector',
		];
	}

	public function attach(Connectors\ConnectorFactory $factory, string $type): void
	{
		$this->factories->attach($factory, $type);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		$this
			->setName(self::NAME)
			->setDescription('Devices module connector')
			->setDefinition(
				new Input\InputDefinition([
					new Input\InputOption(
						'connector',
						'c',
						Input\InputOption::VALUE_REQUIRED,
						'Connector ID or identifier',
					),
					new Input\InputOption(
						'mode',
						'm',
						Input\InputOption::VALUE_OPTIONAL,
						'Connector mode',
						true,
					),
				]),
			);
	}

	public function terminateConnector(Events\TerminateConnector $event): void
	{
		if ($event->getException() !== null) {
			$this->logger->warning('Triggering connector termination due to some error', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
				'type' => 'command',
				'reason' => [
					'source' => $event->getSource()->getValue(),
					'message' => $event->getReason(),
				],
				'exception' => BootstrapHelpers\Logger::buildException($event->getException()),
			]);
		} else {
			$this->logger->info('Triggering connector termination', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
				'type' => 'command',
				'reason' => [
					'source' => $event->getSource()->getValue(),
					'message' => $event->getReason(),
				],
			]);
		}

		if ($this->service !== null && $this->connector !== null) {
			try {
				$this->terminate($this->service, $this->connector);

				return;
			} catch (Exceptions\Terminate $ex) {
				$this->logger->error('Connector could not be safely terminated', [
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'command',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				]);
			}
		}

		$this->eventLoop->stop();
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		if ($input->getOption('quiet') === false) {
			$io->title('Devices module - connector');

			$io->note('This action will run module connector service');
		}

		if ($input->getOption('no-interaction') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				'Would you like to continue?',
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				return Console\Command\Command::SUCCESS;
			}
		}

		$progressBar = new Console\Helper\ProgressBar(
			$output,
			intval(self::DISCOVERY_MAX_PROCESSING_INTERVAL * 60),
		);

		$progressBar->setFormat('[%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %');

		try {
			$this->executeConnector($io, $input, $progressBar);

			$this->eventLoop->run();

			$progressBar->finish();

			return Console\Command\Command::SUCCESS;
		} catch (Exceptions\Terminate $ex) {
			$this->logger->debug('Stopping connector', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
				'type' => 'command',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
			]);

			$this->eventLoop->stop();

			return Console\Command\Command::SUCCESS;
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('An unhandled error occurred', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
				'type' => 'command',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
			]);

			if ($input->getOption('quiet') === false) {
				$io->error('Something went wrong, service could not be finished. Error was logged.');
			}

			return Console\Command\Command::FAILURE;
		}
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws BadMethodCallException
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Terminate
	 */
	private function executeConnector(
		Style\SymfonyStyle $io,
		Input\InputInterface $input,
		Console\Helper\ProgressBar $progressBar,
	): void
	{
		if ($input->getOption('quiet') === false) {
			$io->section('Preparing connector');
		}

		$discover = false;

		if (
			$input->hasOption('mode')
			&& is_string($input->getOption('mode'))
			&& Utils\Strings::lower($input->getOption('mode')) === 'discover'
		) {
			$discover = true;
		}

		if (
			$input->hasOption('connector')
			&& is_string($input->getOption('connector'))
			&& $input->getOption('connector') !== ''
		) {
			$connectorId = $input->getOption('connector');

			$findConnectorQuery = new Queries\FindConnectors();

			if (Uuid\Uuid::isValid($connectorId)) {
				$findConnectorQuery->byId(Uuid\Uuid::fromString($connectorId));
			} else {
				$findConnectorQuery->byIdentifier($connectorId);
			}

			$this->connector = $this->connectorsRepository->findOneBy($findConnectorQuery);

			if ($this->connector === null) {
				if ($input->getOption('quiet') === false) {
					$io->warning('Connector was not found in system');
				}

				return;
			}
		} else {
			$connectors = [];

			$findConnectorsQuery = new Queries\FindConnectors();

			foreach ($this->connectorsRepository->findAllBy($findConnectorsQuery) as $connector) {
				if ($discover) {
					$findConnectorControlQuery = new Queries\FindConnectorControls();
					$findConnectorControlQuery->forConnector($connector);
					$findConnectorControlQuery->byName(MetadataTypes\ControlName::NAME_DISCOVER);

					$control = $this->connectorControlsRepository->findOneBy($findConnectorControlQuery);

					if ($control === null) {
						continue;
					}
				}

				$connectors[$connector->getIdentifier()] = $connector->getIdentifier() .
					($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
			}

			if (count($connectors) === 0) {
				if ($input->getOption('quiet') === false) {
					if ($discover) {
						$io->warning('No connectors with discovery support registered in system');
					} else {
						$io->warning('No connectors registered in system');
					}
				}

				return;
			}

			$question = new Console\Question\ChoiceQuestion(
				'Please select connector to execute',
				array_values($connectors),
			);

			$question->setErrorMessage('Selected connector: %s is not valid.');

			$connectorIdentifierKey = array_search($io->askQuestion($question), $connectors, true);

			if ($connectorIdentifierKey === false) {
				if ($input->getOption('quiet') === false) {
					$io->error('Something went wrong, connector could not be loaded');
				}

				$this->logger->error('Connector identifier was not able to get from answer', [
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'command',
				]);

				return;
			}

			$findConnectorQuery = new Queries\FindConnectors();
			$findConnectorQuery->byIdentifier($connectorIdentifierKey);

			$this->connector = $this->connectorsRepository->findOneBy($findConnectorQuery);

			if ($this->connector === null) {
				if ($input->getOption('quiet') === false) {
					$io->error('Something went wrong, connector could not be loaded');
				}

				$this->logger->error('Connector was not found', [
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'command',
				]);

				return;
			}
		}

		if (!$this->connector->isEnabled()) {
			if ($input->getOption('quiet') === false) {
				$io->warning('Connector is disabled. Disabled connector could not be executed');
			}

			return;
		}

		if ($discover) {
			$findConnectorControlQuery = new Queries\FindConnectorControls();
			$findConnectorControlQuery->forConnector($this->connector);
			$findConnectorControlQuery->byName(MetadataTypes\ControlName::NAME_DISCOVER);

			$control = $this->connectorControlsRepository->findOneBy($findConnectorControlQuery);

			if ($control === null) {
				if ($input->getOption('quiet') === false) {
					$io->warning('Connector does not support devices discovery');
				}

				return;
			}
		}

		if ($input->getOption('quiet') === false) {
			$io->section('Initializing connector');
		}

		$this->dispatcher?->dispatch(new Events\ConnectorStartup($this->connector));

		foreach ($this->factories as $factory) {
			if ($this->connector->getType() === $this->factories[$factory]) {
				$this->service = $factory->create($this->connector);
			}
		}

		if ($this->service === null) {
			throw new Exceptions\Terminate('Connector service could not created');
		}

		if ($discover) {
			$this->progressBarTimer = $this->eventLoop->addPeriodicTimer(
				0.1,
				async(static function () use ($progressBar): void {
					$progressBar->advance();
				}),
			);
		}

		$this->eventLoop->futureTick(function () use ($discover, $progressBar): void {
			assert($this->connector instanceof Entities\Connectors\Connector);

			if ($discover) {
				$this->dispatcher?->dispatch(new Events\BeforeConnectorDiscoveryStart($this->connector));

				$this->logger->info('Starting connector...', [
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'command',
				]);

				try {
					assert($this->service instanceof Connectors\Connector);

					$progressBar->start();

					// Start connector service
					$this->service->discover();

					$this->eventLoop->addTimer(
						self::DISCOVERY_MAX_PROCESSING_INTERVAL,
						function (): void {
							assert($this->service instanceof Connectors\Connector);
							assert($this->connector instanceof Entities\Connectors\Connector);

							$this->terminate($this->service, $this->connector, true);
						},
					);

				} catch (Throwable $ex) {
					throw new Exceptions\Terminate('Connector discovery can\'t be started', $ex->getCode(), $ex);
				}

				$this->dispatcher?->dispatch(new Events\AfterConnectorDiscoveryStart($this->connector));

			} else {
				$this->dispatcher?->dispatch(new Events\BeforeConnectorExecutionStart($this->connector));

				$this->logger->info('Starting connector...', [
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'command',
				]);

				try {
					$this->resetConnector(
						$this->connector,
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_UNKNOWN),
					);

					assert($this->service instanceof Connectors\Connector);

					// Start connector service
					$this->service->execute();

					assert($this->connector instanceof Entities\Connectors\Connector);

					$this->connectorConnectionManager->setState(
						$this->connector,
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_RUNNING),
					);
				} catch (Throwable $ex) {
					throw new Exceptions\Terminate('Connector can\'t be started', $ex->getCode(), $ex);
				}

				$this->dispatcher?->dispatch(new Events\AfterConnectorExecutionStart($this->connector));
			}

			foreach ($this->exchangeFactories as $exchangeFactory) {
				$exchangeFactory->create();
			}
		});

		$this->eventLoop->addSignal(SIGTERM, function (): void {
			assert($this->connector instanceof Entities\Connectors\Connector);
			assert($this->service instanceof Connectors\Connector);

			$this->terminate($this->service, $this->connector);
		});

		$this->eventLoop->addSignal(SIGINT, function (): void {
			assert($this->connector instanceof Entities\Connectors\Connector);
			assert($this->service instanceof Connectors\Connector);

			$this->terminate($this->service, $this->connector);
		});

		$this->databaseRefreshTimer = $this->eventLoop->addPeriodicTimer(
			self::DATABASE_REFRESH_INTERVAL,
			function (): void {
				$this->eventLoop->futureTick(function (): void {
					// Check if ping to DB is possible...
					if (!$this->database->ping()) {
						// ...if not, try to reconnect
						$this->database->reconnect();

						// ...and ping again
						if (!$this->database->ping()) {
							throw new Exceptions\Terminate('Connection to database could not be re-established');
						}
					}
				});
			},
		);
	}

	/**
	 * @throws Exceptions\Terminate
	 */
	private function terminate(
		Connectors\Connector $service,
		Entities\Connectors\Connector $connector,
		bool $isDiscovery = false,
	): void
	{
		$this->logger->info('Stopping connector...', [
			'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
			'type' => 'command',
		]);

		try {
			$this->dispatcher?->dispatch(new Events\BeforeConnectorTerminate($service));

			$service->terminate();

			if (!$isDiscovery) {
				$this->resetConnector(
					$connector,
					MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_DISCONNECTED),
				);
			}

			$now = $this->dateTimeFactory->getNow();

			$waitingForClosing = true;

			// Wait until connector is fully terminated
			while (
				$waitingForClosing
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $now->getTimestamp()
				) < self::SHUTDOWN_WAITING_DELAY
			) {
				if (!$service->hasUnfinishedTasks()) {
					$waitingForClosing = false;
				}
			}

			if ($isDiscovery) {
				$this->dispatcher?->dispatch(new Events\AfterConnectorDiscoveryTerminate($service));
			} else {
				$this->dispatcher?->dispatch(new Events\AfterConnectorExecutionTerminate($service));
			}

			if (!$isDiscovery) {
				$this->connectorConnectionManager->setState(
					$connector,
					MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_STOPPED),
				);
			}

			if ($this->databaseRefreshTimer !== null) {
				$this->eventLoop->cancelTimer($this->databaseRefreshTimer);
			}

			if ($this->progressBarTimer !== null) {
				$this->eventLoop->cancelTimer($this->progressBarTimer);
			}

			$this->eventLoop->stop();
		} catch (Throwable $ex) {
			$this->logger->error('Connector could not be stopped. An unexpected error occurred', [
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
				'type' => 'command',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
			]);

			throw new Exceptions\Terminate(
				'Error during connector termination process',
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function resetConnector(
		Entities\Connectors\Connector $connector,
		MetadataTypes\ConnectionState $state,
	): void
	{
		$findConnectorPropertiesQuery = new Queries\FindConnectorProperties();
		$findConnectorPropertiesQuery->forConnector($connector);

		foreach ($this->connectorPropertiesRepository->findAllBy($findConnectorPropertiesQuery) as $property) {
			if ($property instanceof Entities\Connectors\Properties\Dynamic) {
				$this->connectorPropertiesStateManager->setValidState($property, false);
			}
		}

		$findDevicesQuery = new Queries\FindDevices();
		$findDevicesQuery->byConnectorId($connector->getId());

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery) as $device) {
			$this->deviceConnectionManager->setState($device, $state);

			foreach ($device->getProperties() as $property) {
				if ($property instanceof Entities\Devices\Properties\Dynamic) {
					$this->devicePropertiesStateManager->setValidState($property, false);
				}
			}

			foreach ($device->getChannels() as $channel) {
				foreach ($channel->getProperties() as $property) {
					if ($property instanceof Entities\Channels\Properties\Dynamic) {
						$this->channelPropertiesStateManager->setValidState($property, false);
					}
				}
			}
		}
	}

}
