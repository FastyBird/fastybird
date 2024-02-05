<?php declare(strict_types = 1);

/**
 * Build.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           04.02.24
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Commands;

use FastyBird\Addon\VirtualThermostat\Entities as VirtualThermostatEntities;
use FastyBird\Addon\VirtualThermostat\Queries as VirtualThermostatQueries;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Builders;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Entities;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Exceptions;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Queries;
use FastyBird\Connector\HomeKit\Entities as HomeKitEntities;
use FastyBird\Connector\HomeKit\Queries as HomeKitQueries;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette\Localization;
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
 * Virtual thermostat to HomeKit accessory bridge builder command
 *
 * @package        FastyBird:VirtualThermostatAddon!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Build extends Console\Command\Command
{

	public const NAME = 'fb:virtual-thermostat-addon-homekit-connector-bridge:build';

	public function __construct(
		private readonly VirtualThermostatAddonHomeKitConnector\Logger $logger,
		private readonly Builders\Builder $builder,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly ApplicationHelpers\Database $databaseHelper,
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
			->setDescription('Bridge builder for Virtual thermostat to HomeKit accessory');
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title($this->translator->translate('//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.title'));

		$io->note(
			$this->translator->translate('//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.subtitle'),
		);

		$this->askBuildAction($io);

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function createBridge(Style\SymfonyStyle $io): void
	{
		$device = $this->askWhichThermostat($io);

		if ($device === null) {
			$io->warning(
				$this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.messages.noVirtualThermostats',
				),
			);

			return;
		}

		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->warning(
				$this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.messages.noHomeKitConnectors',
				),
			);

			return;
		}

		try {
			$bridge = $this->builder->build($device, $connector);

			$io->success(
				$this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.messages.create.bridge.success',
					['name' => $bridge->getName() ?? $bridge->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\BridgeSource::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR,
					'type' => 'build-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.messages.create.bridge.error',
				),
			);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function editBridge(Style\SymfonyStyle $io): void
	{
		$bridge = $this->askWhichBridge($io);

		if ($bridge === null) {
			$io->warning(
				$this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.messages.noBridges',
				),
			);

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.questions.create.bridge',
				),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createBridge($io);
			}

			return;
		}

		try {
			$bridge = $this->builder->build(
				$bridge->getParent(),
				$bridge->getConnector(),
			);

			$io->success(
				$this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.messages.update.bridge.success',
					['name' => $bridge->getName() ?? $bridge->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\BridgeSource::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR,
					'type' => 'build-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.messages.update.bridge.error',
				),
			);
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 */
	private function deleteBridge(Style\SymfonyStyle $io): void
	{
		$device = $this->askWhichBridge($io);

		if ($device === null) {
			$io->info($this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.messages.noBridges',
			));

			return;
		}

		$io->warning(
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.messages.remove.bridge.confirm',
				['name' => $device->getName() ?? $device->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.questions.continue',
			),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->transaction(function () use ($device): void {
				$this->devicesManager->delete($device);
			});

			$io->success(
				$this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.messages.remove.bridge.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\BridgeSource::VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR,
					'type' => 'build-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.messages.remove.bridge.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	private function listBridges(Style\SymfonyStyle $io): void
	{
		$findDevicesQuery = new Queries\Entities\FindDevices();

		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Thermostat::class);
		usort(
			$devices,
			static fn (Entities\Devices\Thermostat $a, Entities\Devices\Thermostat $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.data.name'),
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.data.thermostat',
			),
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.data.connector',
			),
		]);

		foreach ($devices as $index => $device) {
			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
				$device->getParent()->getName() ?? $device->getParent()->getIdentifier(),
				$device->getConnector()->getName() ?? $device->getConnector()->getIdentifier(),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	private function askBuildAction(Style\SymfonyStyle $io): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.questions.whatToDo',
			),
			[
				0 => $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.create.bridge',
				),
				1 => $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.update.bridge',
				),
				2 => $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.remove.bridge',
				),
				3 => $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.list.bridges',
				),
				4 => $this->translator->translate(
					'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.nothing',
				),
			],
			4,
		);

		$question->setErrorMessage(
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
			),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.create.bridge',
			)
			|| $whatToDo === '0'
		) {
			$this->createBridge($io);

			$this->askBuildAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.update.bridge',
			)
			|| $whatToDo === '1'
		) {
			$this->editBridge($io);

			$this->askBuildAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.remove.bridge',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteBridge($io);

			$this->askBuildAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.build.actions.list.bridges',
			)
			|| $whatToDo === '3'
		) {
			$this->listBridges($io);

			$this->askBuildAction($io);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): HomeKitEntities\HomeKitConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new HomeKitQueries\Entities\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			HomeKitEntities\HomeKitConnector::class,
		);
		usort(
			$systemConnectors,
			static fn (HomeKitEntities\HomeKitConnector $a, HomeKitEntities\HomeKitConnector $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($systemConnectors as $connector) {
			$connectors[$connector->getIdentifier()] = $connector->getName() ?? $connector->getIdentifier();
		}

		if (count($connectors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.questions.select.item.connector',
			),
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
			),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connectors): HomeKitEntities\HomeKitConnector {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate(
								'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($connectors))) {
					$answer = array_values($connectors)[$answer];
				}

				$identifier = array_search($answer, $connectors, true);

				if ($identifier !== false) {
					$findConnectorQuery = new HomeKitQueries\Entities\FindConnectors();
					$findConnectorQuery->byIdentifier($identifier);

					$connector = $this->connectorsRepository->findOneBy(
						$findConnectorQuery,
						HomeKitEntities\HomeKitConnector::class,
					);

					if ($connector !== null) {
						return $connector;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$connector = $io->askQuestion($question);
		assert($connector instanceof HomeKitEntities\HomeKitConnector);

		return $connector;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichThermostat(
		Style\SymfonyStyle $io,
	): VirtualThermostatEntities\Devices\Thermostat|null
	{
		$devices = [];

		$findDevicesQuery = new VirtualThermostatQueries\Entities\FindThermostatDevices();

		$connectorDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			VirtualThermostatEntities\Devices\Thermostat::class,
		);
		usort(
			$connectorDevices,
			static fn (VirtualThermostatEntities\Devices\Thermostat $a, VirtualThermostatEntities\Devices\Thermostat $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($connectorDevices as $device) {
			$devices[$device->getIdentifier()] = $device->getName() ?? $device->getIdentifier();
		}

		if (count($devices) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.questions.select.item.device',
			),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
			),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($devices): VirtualThermostatEntities\Devices\Thermostat {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate(
								'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($devices))) {
					$answer = array_values($devices)[$answer];
				}

				$identifier = array_search($answer, $devices, true);

				if ($identifier !== false) {
					$findDeviceQuery = new VirtualThermostatQueries\Entities\FindThermostatDevices();
					$findDeviceQuery->byIdentifier($identifier);

					$device = $this->devicesRepository->findOneBy(
						$findDeviceQuery,
						VirtualThermostatEntities\Devices\Thermostat::class,
					);

					if ($device !== null) {
						return $device;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof VirtualThermostatEntities\Devices\Thermostat);

		return $device;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichBridge(
		Style\SymfonyStyle $io,
	): Entities\Devices\Thermostat|null
	{
		$devices = [];

		$findDevicesQuery = new Queries\Entities\FindDevices();

		$connectorDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\Devices\Thermostat::class,
		);
		usort(
			$connectorDevices,
			static fn (Entities\Devices\Thermostat $a, Entities\Devices\Thermostat $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($connectorDevices as $device) {
			$devices[$device->getIdentifier()] = $device->getName() ?? $device->getIdentifier();
		}

		if (count($devices) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.install.questions.select.item.device',
			),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			$this->translator->translate(
				'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
			),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($devices): Entities\Devices\Thermostat {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate(
								'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($devices))) {
					$answer = array_values($devices)[$answer];
				}

				$identifier = array_search($answer, $devices, true);

				if ($identifier !== false) {
					$findDeviceQuery = new Queries\Entities\FindDevices();
					$findDeviceQuery->byIdentifier($identifier);

					$device = $this->devicesRepository->findOneBy(
						$findDeviceQuery,
						Entities\Devices\Thermostat::class,
					);

					if ($device !== null) {
						return $device;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate(
							'//virtual-thermostat-addon-homekit-connector-bridge.cmd.base.messages.answerNotValid',
						),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\Devices\Thermostat);

		return $device;
	}

}
