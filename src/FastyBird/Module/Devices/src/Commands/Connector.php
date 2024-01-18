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
use DateTimeInterface;
use Doctrine\DBAL;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Exchange\Exchange as ExchangeExchange;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices;
use FastyBird\Module\Devices\Connectors;
use FastyBird\Module\Devices\Consumers;
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

	private const DISCOVERY_MAX_PROCESSING_INTERVAL = 120.0;

	private bool $isTerminating = false;

	private MetadataDocuments\DevicesModule\Connector|null $connector = null;

	private Connectors\Connector|null $service = null;

	private Console\Helper\ProgressBar|null $progressBar = null;

	private string $mode = self::MODE_EXECUTE;

	/** @var SplObjectStorage<Connectors\ConnectorFactory, string> */
	private SplObjectStorage $factories;

	private EventLoop\TimerInterface|null $databaseRefreshTimer = null;

	private EventLoop\TimerInterface|null $progressBarTimer = null;

	private EventLoop\TimerInterface|null $discoveryTimer = null;

	private DateTimeInterface|null $executedAt = null;

	/**
	 * @param array<ExchangeExchange\Factory> $exchangeFactories
	 */
	public function __construct(
		private readonly Models\Configuration\Connectors\Repository $connectorsConfigurationRepository,
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorsPropertiesConfigurationRepository,
		private readonly Models\Configuration\Connectors\Controls\Repository $connectorsControlsConfigurationRepository,
		private readonly Models\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly Models\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly Models\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly Models\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Utilities\ConnectorConnection $connectorConnectionManager,
		private readonly Utilities\DeviceConnection $deviceConnectionManager,
		private readonly Devices\Logger $logger,
		private readonly BootstrapHelpers\Database $database,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly ExchangeConsumers\Container $consumer,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
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
				'source' => MetadataTypes\ModuleSource::DEVICES,
				'type' => 'command',
				'reason' => [
					'source' => $event->getSource()->getValue(),
					'message' => $event->getReason(),
				],
				'exception' => BootstrapHelpers\Logger::buildException($event->getException()),
			]);
		} else {
			$this->logger->info('Triggering connector termination', [
				'source' => MetadataTypes\ModuleSource::DEVICES,
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
					'source' => MetadataTypes\ModuleSource::DEVICES,
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

		if (
			$input->hasOption('mode')
			&& is_string($input->getOption('mode'))
			&& Utils\Strings::lower($input->getOption('mode')) === self::MODE_DISCOVER
		) {
			$this->mode = self::MODE_DISCOVER;
		}

		if ($this->mode === self::MODE_DISCOVER) {
			$this->progressBar = new Console\Helper\ProgressBar(
				$output,
				intval(self::DISCOVERY_MAX_PROCESSING_INTERVAL),
			);

			$this->progressBar->setFormat('[%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
		}

		try {
			$this->isTerminating = false;

			$this->executeConnector($io, $input);

			$this->executedAt = $this->dateTimeFactory->getNow();

			$this->eventLoop->run();

			$this->progressBar?->finish();

			return Console\Command\Command::SUCCESS;
		} catch (Exceptions\Terminate $ex) {
			if ($ex->getPrevious() !== null) {
				$this->logger->error('An error occurred. Stopping connector', [
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'command',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				]);
			} else {
				$this->logger->debug('Stopping connector', [
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'command',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				]);
			}

			$this->eventLoop->stop();

			return Console\Command\Command::SUCCESS;
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error('An unhandled error occurred', [
				'source' => MetadataTypes\ModuleSource::DEVICES,
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
	 * @throws ExchangeExceptions\InvalidArgument
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
			$input->hasOption('connector')
			&& is_string($input->getOption('connector'))
			&& $input->getOption('connector') !== ''
		) {
			$connectorId = $input->getOption('connector');

			$findConnectorQuery = new Queries\Configuration\FindConnectors();

			if (Uuid\Uuid::isValid($connectorId)) {
				$findConnectorQuery->byId(Uuid\Uuid::fromString($connectorId));
			} else {
				$findConnectorQuery->byIdentifier($connectorId);
			}

			$this->connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

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

			$findConnectorsQuery = new Queries\Configuration\FindConnectors();

			foreach ($this->connectorsConfigurationRepository->findAllBy($findConnectorsQuery) as $connector) {
				if ($this->mode === self::MODE_DISCOVER) {
					$findConnectorControlQuery = new Queries\Configuration\FindConnectorControls();
					$findConnectorControlQuery->forConnector($connector);
					$findConnectorControlQuery->byName(MetadataTypes\ControlName::DISCOVER);

					$control = $this->connectorsControlsConfigurationRepository->findOneBy($findConnectorControlQuery);

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
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'command',
				]);

				return;
			}

			$findConnectorQuery = new Queries\Configuration\FindConnectors();
			$findConnectorQuery->byIdentifier($connectorIdentifierKey);

			$this->connector = $this->connectorsConfigurationRepository->findOneBy($findConnectorQuery);

			if ($this->connector === null) {
				if ($input->getOption('quiet') === false) {
					$io->error(
						$this->translator->translate('//devices-module.cmd.connector.messages.loadConnectorError'),
					);
				}

				$this->logger->error('Connector was not found', [
					'source' => MetadataTypes\ModuleSource::DEVICES,
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
			$findConnectorControlQuery = new Queries\Configuration\FindConnectorControls();
			$findConnectorControlQuery->forConnector($this->connector);
			$findConnectorControlQuery->byName(MetadataTypes\ControlName::DISCOVER);

			$control = $this->connectorsControlsConfigurationRepository->findOneBy($findConnectorControlQuery);

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

		$this->consumer->enable(Consumers\Configuration::class);

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
					if ($this->executedAt !== null) {
						$this->progressBar?->setProgress(
							$this->dateTimeFactory->getNow()->getTimestamp() - $this->executedAt->getTimestamp(),
						);
					} else {
						$this->progressBar?->advance();
					}
				}),
			);
		}

		$this->eventLoop->futureTick(function (): void {
			assert($this->connector instanceof MetadataDocuments\DevicesModule\Connector);

			if ($this->mode === self::MODE_DISCOVER) {
				$this->dispatcher?->dispatch(new Events\BeforeConnectorDiscoveryStart($this->connector));

				$this->logger->info('Starting connector...', [
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'command',
				]);

				try {
					assert($this->service instanceof Connectors\Connector);

					$this->progressBar?->start();

					// Start connector service
					$this->service->discover();

					$this->discoveryTimer = $this->eventLoop->addTimer(
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
					'source' => MetadataTypes\ModuleSource::DEVICES,
					'type' => 'command',
				]);

				try {
					$this->resetConnector(
						$this->connector,
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::UNKNOWN),
					);

					assert($this->service instanceof Connectors\Connector);

					// Start connector service
					$this->service->execute();

					assert($this->connector instanceof MetadataDocuments\DevicesModule\Connector);

					$this->connectorConnectionManager->setState(
						$this->connector,
						MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::RUNNING),
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

		if ($this->isTerminating) {
			return;
		}

		$this->isTerminating = true;

		$service = $this->service;
		$connector = $this->connector;

		$this->logger->info('Stopping connector...', [
			'source' => MetadataTypes\ModuleSource::DEVICES,
			'type' => 'command',
		]);

		if ($this->discoveryTimer !== null) {
			$this->eventLoop->cancelTimer($this->discoveryTimer);
		}

		try {
			$this->dispatcher?->dispatch(new Events\BeforeConnectorTerminate($this->service));

			$this->service->terminate();

			if ($this->mode === self::MODE_EXECUTE) {
				$this->resetConnector(
					$this->connector,
					MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::DISCONNECTED),
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
							MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STOPPED),
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
				'source' => MetadataTypes\ModuleSource::DEVICES,
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
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function resetConnector(
		MetadataDocuments\DevicesModule\Connector $connector,
		MetadataTypes\ConnectionState $state,
	): void
	{
		$findConnectorPropertiesQuery = new Queries\Configuration\FindConnectorDynamicProperties();
		$findConnectorPropertiesQuery->forConnector($connector);

		$properties = $this->connectorsPropertiesConfigurationRepository->findAllBy(
			$findConnectorPropertiesQuery,
			MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class,
		);

		foreach ($properties as $property) {
			$this->connectorPropertiesStatesManager->setValidState($property, false);
		}

		$findDevicesQuery = new Queries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($connector);

		foreach ($this->devicesConfigurationRepository->findAllBy($findDevicesQuery) as $device) {
			$this->resetDevice($device, $state);
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function resetDevice(
		MetadataDocuments\DevicesModule\Device $device,
		MetadataTypes\ConnectionState $state,
	): void
	{
		$this->deviceConnectionManager->setState($device, $state);

		$findDevicePropertiesQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertiesQuery->forDevice($device);

		$properties = $this->devicesPropertiesConfigurationRepository->findAllBy(
			$findDevicePropertiesQuery,
			MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
		);

		foreach ($properties as $property) {
			$this->devicePropertiesStatesManager->setValidState($property, false);
		}

		$findChannelsQuery = new Queries\Configuration\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

		foreach ($channels as $channel) {
			$findChannelPropertiesQuery = new Queries\Configuration\FindChannelDynamicProperties();
			$findChannelPropertiesQuery->forChannel($channel);

			$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
				$findChannelPropertiesQuery,
				MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
			);

			foreach ($properties as $property) {
				$this->channelPropertiesStatesManager->setValidState($property, false);
			}
		}

		$findChildrenQuery = new Queries\Configuration\FindDevices();
		$findChildrenQuery->forParent($device);

		foreach ($this->devicesConfigurationRepository->findAllBy($findChildrenQuery) as $child) {
			$this->resetDevice($child, $state);
		}
	}

}
