<?php declare(strict_types = 1);

/**
 * Devices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           23.10.23
 */

namespace FastyBird\Connector\Virtual\Commands;

use Doctrine\DBAL;
use Doctrine\Persistence;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Queries;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use InvalidArgumentException as InvalidArgumentExceptionAlias;
use Nette\Localization;
use RuntimeException;
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
use function usort;

/**
 * Connector devices management command
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Devices extends Console\Command\Command
{

	public const NAME = 'fb:virtual-connector:devices';

	/**
	 * @param array<string, Console\Command\Command> $commands
	 */
	public function __construct(
		private readonly Virtual\Logger $logger,
		private readonly array $commands,
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\DevicesManager $devicesManager,
		private readonly Persistence\ManagerRegistry $managerRegistry,
		private readonly Localization\Translator $translator,
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
			->setDescription('Virtual connector devices management');
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentExceptionAlias
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$symfonyApp = $this->getApplication();

		if ($symfonyApp === null) {
			return Console\Command\Command::FAILURE;
		}

		foreach ($this->commands as $command) {
			$symfonyApp->add($command);
		}

		$io = new Style\SymfonyStyle($input, $output);

		$io->title($this->translator->translate('//virtual-connector.cmd.devices.title'));

		$io->note($this->translator->translate('//virtual-connector.cmd.devices.subtitle'));

		if ($input->getOption('no-interaction') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//virtual-connector.cmd.base.questions.continue'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				return Console\Command\Command::SUCCESS;
			}
		}

		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->warning($this->translator->translate('//virtual-connector.cmd.base.messages.noConnectors'));

			return Console\Command\Command::SUCCESS;
		}

		$this->askConnectorAction($input, $output, $io, $connector);

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DevicesExceptions\InvalidState
	 */
	private function createDevice(
		Input\InputInterface $input,
		Output\OutputInterface $output,
		Style\SymfonyStyle $io,
		Entities\VirtualConnector $connector,
	): void
	{
		$serviceCmd = $this->askWhichDeviceType($io);

		if ($serviceCmd === null) {
			return;
		}

		$serviceCmd->run(new Input\ArrayInput([
			'--action' => Virtual\Commands\Devices\Device::ACTION_CREATE,
			'--connector' => $connector->getId()->toString(),
			'--no-interaction' => $input->getOption('no-interaction'),
			'--quiet' => $input->getOption('quiet'),
		]), $output);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DevicesExceptions\InvalidState
	 */
	private function editDevice(
		Input\InputInterface $input,
		Output\OutputInterface $output,
		Style\SymfonyStyle $io,
		Entities\VirtualConnector $connector,
	): void
	{
		$serviceCmd = $this->askWhichDeviceType($io);

		if ($serviceCmd === null) {
			return;
		}

		$serviceCmd->run(new Input\ArrayInput([
			'--action' => Virtual\Commands\Devices\Device::ACTION_EDIT,
			'--connector' => $connector->getId()->toString(),
			'--no-interaction' => $input->getOption('no-interaction'),
			'--quiet' => $input->getOption('quiet'),
		]), $output);
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function deleteDevice(Style\SymfonyStyle $io, Entities\VirtualConnector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->warning($this->translator->translate('//virtual-connector.cmd.devices.messages.noDevices'));

			return;
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//virtual-connector.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->devicesManager->delete($device);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//virtual-connector.cmd.devices.messages.remove.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//virtual-connector.cmd.devices.messages.remove.error'));
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
	private function listDevices(Style\SymfonyStyle $io, Entities\VirtualConnector $connector): void
	{
		$findDevicesQuery = new Queries\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\VirtualDevice::class);
		usort(
			$devices,
			static function (Entities\VirtualDevice $a, Entities\VirtualDevice $b): int {
				if ($a->getIdentifier() === $b->getIdentifier()) {
					return $a->getName() <=> $b->getName();
				}

				return $a->getIdentifier() <=> $b->getIdentifier();
			},
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//virtual-connector.cmd.devices.data.name'),
			$this->translator->translate('//virtual-connector.cmd.devices.data.model'),
		]);

		foreach ($devices as $index => $device) {
			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
				$device->getType(),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\VirtualConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new Queries\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\VirtualConnector::class,
		);
		usort(
			$systemConnectors,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			static fn (Entities\VirtualConnector $a, Entities\VirtualConnector $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($systemConnectors as $connector) {
			$connectors[$connector->getIdentifier()] = $connector->getIdentifier()
				. ($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
		}

		if (count($connectors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//virtual-connector.cmd.devices.questions.select.connector'),
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer) use ($connectors): Entities\VirtualConnector {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
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
					Entities\VirtualConnector::class,
				);

				if ($connector !== null) {
					return $connector;
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$connector = $io->askQuestion($question);
		assert($connector instanceof Entities\VirtualConnector);

		return $connector;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichDevice(
		Style\SymfonyStyle $io,
		Entities\VirtualConnector $connector,
	): Entities\VirtualDevice|null
	{
		$devices = [];

		$findDevicesQuery = new Queries\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$connectorDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\VirtualDevice::class,
		);
		usort(
			$connectorDevices,
			static fn (DevicesEntities\Devices\Device $a, DevicesEntities\Devices\Device $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($connectorDevices as $device) {
			$devices[$device->getIdentifier()] = $device->getIdentifier()
				. ($device->getName() !== null ? ' [' . $device->getName() . ']' : '');
		}

		if (count($devices) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//virtual-connector.cmd.devices.questions.select.device'),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connector, $devices): Entities\VirtualDevice {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($devices))) {
					$answer = array_values($devices)[$answer];
				}

				$identifier = array_search($answer, $devices, true);

				if ($identifier !== false) {
					$findDeviceQuery = new Queries\FindDevices();
					$findDeviceQuery->byIdentifier($identifier);
					$findDeviceQuery->forConnector($connector);

					$device = $this->devicesRepository->findOneBy(
						$findDeviceQuery,
						Entities\VirtualDevice::class,
					);

					if ($device !== null) {
						return $device;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\VirtualDevice);

		return $device;
	}

	private function askWhichDeviceType(
		Style\SymfonyStyle $io,
	): Console\Command\Command|null
	{
		$types = [];

		foreach ($this->commands as $type => $command) {
			$types[$type] = $this->translator->translate('//virtual-connector.cmd.devices.answers.types.' . $type);
		}

		if ($this->commands === []) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//virtual-connector.cmd.devices.questions.select.type'),
			array_values($types),
			count($types) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($types): Console\Command\Command {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($types))) {
					$answer = array_values($types)[$answer];
				}

				$type = array_search($answer, $types, true);

				if ($type !== false && array_key_exists($type, $this->commands)) {
					return $this->commands[$type];
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$command = $io->askQuestion($question);
		assert($command instanceof Console\Command\Command);

		return $command;
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	private function askConnectorAction(
		Input\InputInterface $input,
		Output\OutputInterface $output,
		Style\SymfonyStyle $io,
		Entities\VirtualConnector $connector,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//virtual-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//virtual-connector.cmd.devices.actions.create.device'),
				1 => $this->translator->translate('//virtual-connector.cmd.devices.actions.update.device'),
				2 => $this->translator->translate('//virtual-connector.cmd.devices.actions.remove.device'),
				3 => $this->translator->translate('//virtual-connector.cmd.devices.actions.list.devices'),
				4 => $this->translator->translate('//virtual-connector.cmd.devices.actions.nothing'),
			],
			4,
		);

		$question->setErrorMessage(
			$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//virtual-connector.cmd.devices.actions.create.device',
			)
			|| $whatToDo === '0'
		) {
			$this->createDevice($input, $output, $io, $connector);

			$this->askConnectorAction($input, $output, $io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//virtual-connector.cmd.devices.actions.update.device',
			)
			|| $whatToDo === '1'
		) {
			$this->editDevice($input, $output, $io, $connector);

			$this->askConnectorAction($input, $output, $io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//virtual-connector.cmd.devices.actions.remove.device',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteDevice($io, $connector);

			$this->askConnectorAction($input, $output, $io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//virtual-connector.cmd.devices.actions.list.devices',
			)
			|| $whatToDo === '3'
		) {
			$this->listDevices($io, $connector);

			$this->askConnectorAction($input, $output, $io, $connector);
		}
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

		throw new Exceptions\Runtime('Database connection could not be established');
	}

}
