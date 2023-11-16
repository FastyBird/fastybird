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
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Exchange\Exchange as ExchangeExchange;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Connectors;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Utilities;
use Nette\Localization;
use Nette\Utils;
use Psr\EventDispatcher as PsrEventDispatcher;
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

	public const MODE_EXECUTE = 'execute';

	public const MODE_DISCOVER = 'discover';

	private const SHUTDOWN_WAITING_DELAY = 3;

	private const DATABASE_REFRESH_INTERVAL = 5;

	private const DISCOVERY_MAX_PROCESSING_INTERVAL = 60.0;

	private Entities\Connectors\Connector|null $connector = null;

	private Connectors\Connector|null $service = null;

	private Console\Helper\ProgressBar|null $progressBar = null;

	private string $mode = self::MODE_EXECUTE;

	/** @var SplObjectStorage<Connectors\ConnectorFactory, string> */
	private SplObjectStorage $factories;

	private EventLoop\TimerInterface|null $databaseRefreshTimer = null;

	private EventLoop\TimerInterface|null $progressBarTimer = null;

	/**
	 * @param array<ExchangeExchange\Factory> $exchangeFactories
	 */
	public function __construct(
		private readonly Models\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly Models\Entities\Connectors\Properties\PropertiesRepository $connectorsPropertiesRepository,
		private readonly Models\Entities\Connectors\Controls\ControlsRepository $connectorsControlsRepository,
		private readonly Models\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly Models\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly Models\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly Models\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly Utilities\ConnectorConnection $connectorConnectionManager,
		private readonly Utilities\DeviceConnection $deviceConnectionManager,
		private readonly Utilities\ConnectorPropertiesStates $connectorPropertiesStateManager,
		private readonly Utilities\DevicePropertiesStates $devicePropertiesStateManager,
		private readonly Utilities\ChannelPropertiesStates $channelPropertiesStateManager,
		private readonly Devices\Logger $logger,
		private readonly BootstrapHelpers\Database $database,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly Localization\Translator $translator,
		private readonly array $exchangeFactories = [],
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
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
						self::MODE_EXECUTE,
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
				$this->terminate();

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
			$io->title($this->translator->translate('//devices-module.cmd.connector.title'));

			$io->note($this->translator->translate('//devices-module.cmd.connector.subtitle'));
		}

		if ($input->getOption('no-interaction') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//devices-module.cmd.base.questions.continue'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				return Console\Command\Command::SUCCESS;
			}
		}

		if ($this->mode === self::MODE_DISCOVER) {
			$this->progressBar = new Console\Helper\ProgressBar(
				$output,
				intval(self::DISCOVERY_MAX_PROCESSING_INTERVAL * 60),
			);

			$this->progressBar->setFormat('[%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %');
		}

		try {
			$this->executeConnector($io, $input);

			$this->eventLoop->run();

			$this->progressBar?->finish();

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
				$io->error($this->translator->translate('//devices-module.cmd.connector.messages.error'));
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
	): void
	{
		if ($input->getOption('quiet') === false) {
			$io->section($this->translator->translate('//devices-module.cmd.connector.info.preparing'));
		}

		if (
			$input->hasOption('mode')
			&& is_string($input->getOption('mode'))
			&& Utils\Strings::lower($input->getOption('mode')) === self::MODE_DISCOVER
		) {
			$this->mode = self::MODE_DISCOVER;
		}

		if (
			$input->hasOption('connector')
			&& is_string($input->getOption('connector'))
			&& $input->getOption('connector') !== ''
		) {
			$connectorId = $input->getOption('connector');

			$findConnectorQuery = new Queries\Entities\FindConnectors();

			if (Uuid\Uuid::isValid($connectorId)) {
				$findConnectorQuery->byId(Uuid\Uuid::fromString($connectorId));
			} else {
				$findConnectorQuery->byIdentifier($connectorId);
			}

			$this->connector = $this->connectorsRepository->findOneBy($findConnectorQuery);

			if ($this->connector === null) {
				if ($input->getOption('quiet') === false) {
					$io->warning(
						$this->translator->translate('//devices-module.cmd.connector.messages.connectorNotFound'),
					);
				}

				return;
			}
		} else {
			$connectors = [];

			$findConnectorsQuery = new Queries\Entities\FindConnectors();

			foreach ($this->connectorsRepository->findAllBy($findConnectorsQuery) as $connector) {
				if ($this->mode === self::MODE_DISCOVER) {
					$findConnectorControlQuery = new Queries\Entities\FindConnectorControls();
					$findConnectorControlQuery->forConnector($connector);
					$findConnectorControlQuery->byName(MetadataTypes\ControlName::NAME_DISCOVER);

					$control = $this->connectorsControlsRepository->findOneBy($findConnectorControlQuery);

					if ($control === null) {
						continue;
					}
				}

				$connectors[$connector->getIdentifier()] = $connector->getIdentifier() .
					($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
			}

			if (count($connectors) === 0) {
				if ($input->getOption('quiet') === false) {
					if ($this->mode === self::MODE_DISCOVER) {
						$io->warning(
							$this->translator->translate(
								'//devices-module.cmd.connector.messages.noDiscoverableConnectors',
							),
						);
					} else {
						$io->warning(
							$this->translator->translate('//devices-module.cmd.connector.messages.noConnectors'),
						);
					}
				}

				return;
			}

			$question = new Console\Question\ChoiceQuestion(
				$this->translator->translate('//devices-module.cmd.connector.questions.selectConnector'),
				array_values($connectors),
			);

			$question->setErrorMessage(
				$this->translator->translate('//devices-module.cmd.base.messages.answerNotValid'),
			);

			$connectorIdentifierKey = array_search($io->askQuestion($question), $connectors, true);

			if ($connectorIdentifierKey === false) {
				if ($input->getOption('quiet') === false) {
					$io->error(
						$this->translator->translate('//devices-module.cmd.connector.messages.loadConnectorError'),
					);
				}

				$this->logger->error('Connector identifier was not able to get from answer', [
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'command',
				]);

				return;
			}

			$findConnectorQuery = new Queries\Entities\FindConnectors();
			$findConnectorQuery->byIdentifier($connectorIdentifierKey);

			$this->connector = $this->connectorsRepository->findOneBy($findConnectorQuery);

			if ($this->connector === null) {
				if ($input->getOption('quiet') === false) {
					$io->error(
						$this->translator->translate('//devices-module.cmd.connector.messages.loadConnectorError'),
					);
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
				$io->warning($this->translator->translate('//devices-module.cmd.connector.messages.connectorDisabled'));
			}

			return;
		}

		if ($this->mode === self::MODE_DISCOVER) {
			$findConnectorControlQuery = new Queries\Entities\FindConnectorControls();
			$findConnectorControlQuery->forConnector($this->connector);
			$findConnectorControlQuery->byName(MetadataTypes\ControlName::NAME_DISCOVER);

			$control = $this->connectorsControlsRepository->findOneBy($findConnectorControlQuery);

			if ($control === null) {
				if ($input->getOption('quiet') === false) {
					$io->warning(
						$this->translator->translate('//devices-module.cmd.connector.messages.notSupportedConnector'),
					);
				}

				return;
			}
		}

		if ($input->getOption('quiet') === false) {
			$io->section($this->translator->translate('//devices-module.cmd.connector.info.initialization'));
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

		if ($this->mode === self::MODE_DISCOVER) {
			$this->progressBarTimer = $this->eventLoop->addPeriodicTimer(
				0.1,
				async(function (): void {
					$this->progressBar?->advance();
				}),
			);
		}

		$this->eventLoop->futureTick(function (): void {
			assert($this->connector instanceof Entities\Connectors\Connector);

			if ($this->mode === self::MODE_DISCOVER) {
				$this->dispatcher?->dispatch(new Events\BeforeConnectorDiscoveryStart($this->connector));

				$this->logger->info('Starting connector...', [
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'type' => 'command',
				]);

				try {
					assert($this->service instanceof Connectors\Connector);

					$this->progressBar?->start();

					// Start connector service
					$this->service->discover();

					$this->eventLoop->addTimer(
						self::DISCOVERY_MAX_PROCESSING_INTERVAL,
						function (): void {
							$this->terminate();
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
			$this->terminate();
		});

		$this->eventLoop->addSignal(SIGINT, function (): void {
			$this->terminate();
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
	private function terminate(): void
	{
		if ($this->service === null || $this->connector === null) {
			$this->eventLoop->stop();

			return;
		}

		$service = $this->service;
		$connector = $this->connector;

		$this->logger->info('Stopping connector...', [
			'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
			'type' => 'command',
		]);

		try {
			$this->dispatcher?->dispatch(new Events\BeforeConnectorTerminate($this->service));

			$this->service->terminate();

			if ($this->mode === self::MODE_EXECUTE) {
				$this->resetConnector(
					$this->connector,
					MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_DISCONNECTED),
				);
			}

			// Wait until connector is fully terminated
			$this->eventLoop->addTimer(
				self::SHUTDOWN_WAITING_DELAY,
				function () use ($connector, $service): void {
					if ($this->mode === self::MODE_DISCOVER) {
						$this->dispatcher?->dispatch(new Events\AfterConnectorDiscoveryTerminate($service));
					} else {
						$this->dispatcher?->dispatch(new Events\AfterConnectorExecutionTerminate($service));
					}

					if ($this->mode === self::MODE_EXECUTE) {
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
				},
			);

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
		$findConnectorPropertiesQuery = new Queries\Entities\FindConnectorDynamicProperties();
		$findConnectorPropertiesQuery->forConnector($connector);

		foreach ($this->connectorsPropertiesRepository->findAllBy(
			$findConnectorPropertiesQuery,
			Entities\Connectors\Properties\Dynamic::class,
		) as $property) {
			$this->connectorPropertiesStateManager->setValidState($property, false);
		}

		$findDevicesQuery = new Queries\Entities\FindDevices();
		$findDevicesQuery->byConnectorId($connector->getId());

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery) as $device) {
			$this->resetDevice($device, $state);
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function resetDevice(
		Entities\Devices\Device $device,
		MetadataTypes\ConnectionState $state,
	): void
	{
		$this->deviceConnectionManager->setState($device, $state);

		$findDevicePropertiesQuery = new Queries\Entities\FindDeviceDynamicProperties();
		$findDevicePropertiesQuery->forDevice($device);

		foreach ($this->devicesPropertiesRepository->findAllBy(
			$findDevicePropertiesQuery,
			Entities\Devices\Properties\Dynamic::class,
		) as $property) {
			$this->devicePropertiesStateManager->setValidState($property, false);
		}

		$findChannelsQuery = new Queries\Entities\FindChannels();
		$findChannelsQuery->forDevice($device);

		foreach ($this->channelsRepository->findAllBy($findChannelsQuery) as $channel) {
			$findChannelPropertiesQuery = new Queries\Entities\FindChannelDynamicProperties();
			$findChannelPropertiesQuery->forChannel($channel);

			foreach ($this->channelsPropertiesRepository->findAllBy(
				$findChannelPropertiesQuery,
				Entities\Channels\Properties\Dynamic::class,
			) as $property) {
				$this->channelPropertiesStateManager->setValidState($property, false);
			}
		}

		$findChildrenQuery = new Queries\Entities\FindDevices();
		$findChildrenQuery->forParent($device);

		foreach ($this->devicesRepository->findAllBy($findChildrenQuery) as $child) {
			$this->resetDevice($child, $state);
		}
	}

}
