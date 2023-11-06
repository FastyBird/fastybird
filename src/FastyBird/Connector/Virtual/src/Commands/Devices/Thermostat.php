<?php declare(strict_types = 1);

/**
 * Thermostat.php
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

namespace FastyBird\Connector\Virtual\Commands\Devices;

use Doctrine\DBAL;
use Doctrine\Persistence;
use Exception;
use FastyBird\Connector\Virtual;
use FastyBird\Connector\Virtual\Entities;
use FastyBird\Connector\Virtual\Exceptions;
use FastyBird\Connector\Virtual\Queries;
use FastyBird\Connector\Virtual\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette\Localization;
use Nette\Utils;
use Ramsey\Uuid;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_search;
use function array_unique;
use function array_values;
use function assert;
use function count;
use function explode;
use function floatval;
use function implode;
use function in_array;
use function is_array;
use function is_float;
use function is_numeric;
use function sprintf;
use function strval;
use function usort;

/**
 * Connector thermostat devices management command
 *
 * @package        FastyBird:VirtualConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Thermostat extends Device
{

	public const NAME = 'fb:virtual-connector:devices:thermostat';

	public function __construct(
		private readonly Virtual\Logger $logger,
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStatesManager,
		Persistence\ManagerRegistry $managerRegistry,
		Localization\Translator $translator,
		string|null $name = null,
	)
	{
		parent::__construct($translator, $managerRegistry, $name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		$this
			->setName(self::NAME)
			->setDescription('Virtual connector thermostat devices management')
			->setDefinition(
				new Input\InputDefinition([
					new Input\InputOption(
						'connector',
						'c',
						Input\InputOption::VALUE_REQUIRED,
						'Connector ID',
					),
					new Input\InputOption(
						'device',
						'd',
						Input\InputOption::VALUE_OPTIONAL,
						'Device ID',
					),
					new Input\InputOption(
						'action',
						'a',
						Input\InputOption::VALUE_REQUIRED,
						'Management action',
						[
							self::ACTION_CREATE => new Console\Completion\Suggestion(
								self::ACTION_CREATE,
								'Create new thermostat',
							),
							self::ACTION_EDIT => new Console\Completion\Suggestion(
								self::ACTION_EDIT,
								'Edit existing thermostat',
							),
						],
					),
				]),
			);
	}

	/**
	 * @throws Console\Exception\ExceptionInterface
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\Runtime
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$connector = $input->getOption('connector');

		if (!Uuid\Uuid::isValid(strval($connector))) {
			$io->warning(
				$this->translator->translate('//virtual-connector.cmd.devices.messages.noConnector'),
			);

			return Console\Command\Command::FAILURE;
		}

		$findConnectorsQuery = new Queries\FindConnectors();
		$findConnectorsQuery->byId(Uuid\Uuid::fromString(strval($connector)));

		$connector = $this->connectorsRepository->findOneBy($findConnectorsQuery, Entities\VirtualConnector::class);

		if ($connector === null) {
			$io->warning(
				$this->translator->translate('//virtual-connector.cmd.devices.messages.noConnector'),
			);

			return Console\Command\Command::FAILURE;
		}

		$action = $input->getOption('action');

		switch ($action) {
			case self::ACTION_CREATE:
				$this->createDevice($io, $connector);

				break;
			case self::ACTION_EDIT:
				$device = $input->getOption('device');

				if (!Uuid\Uuid::isValid(strval($device))) {
					$io->warning(
						$this->translator->translate('//virtual-connector.cmd.devices.messages.noDevice'),
					);

					return Console\Command\Command::FAILURE;
				}

				$findDeviceQuery = new Queries\FindThermostatDevices();
				$findDeviceQuery->forConnector($connector);
				$findDeviceQuery->byId(Uuid\Uuid::fromString(strval($device)));

				$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Thermostat::class);

				if ($device === null) {
					$io->warning($this->translator->translate('//virtual-connector.cmd.devices.messages.noDevices'));

					$question = new Console\Question\ConfirmationQuestion(
						$this->translator->translate('//virtual-connector.cmd.devices.questions.create.device'),
						false,
					);

					$continue = (bool) $io->askQuestion($question);

					if ($continue) {
						$this->createDevice($io, $connector);
					}

					break;
				}

				$this->editDevice($io, $device);

				break;
		}

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 */
	private function createDevice(Style\SymfonyStyle $io, Entities\VirtualConnector $connector): void
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//virtual-connector.cmd.devices.questions.provide.identifier'),
		);

		$question->setValidator(function (string|null $answer) {
			if ($answer !== '' && $answer !== null) {
				$findDeviceQuery = new Queries\FindDevices();
				$findDeviceQuery->byIdentifier($answer);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\VirtualDevice::class) !== null
				) {
					throw new Exceptions\Runtime(
						$this->translator->translate(
							'//virtual-connector.cmd.devices.messages.identifier.used',
						),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'virtual-thermostat-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findDeviceQuery = new Queries\FindDevices();
				$findDeviceQuery->byIdentifier($identifier);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\VirtualDevice::class) === null
				) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error(
				$this->translator->translate('//virtual-connector.cmd.devices.messages.identifier.missing'),
			);

			return;
		}

		$name = $this->askDeviceName($io);

		$setPresets = [];

		$hvacModeProperty = $targetTempProperty = $presetModeProperty = null;

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Devices\Thermostat::class,
				'connector' => $connector,
				'identifier' => $identifier,
				'name' => $name,
			]));
			assert($device instanceof Entities\Devices\Thermostat);

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::MODEL,
				'device' => $device,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => Entities\Devices\Thermostat::TYPE,
			]));

			$modes = $this->askThermostatModes($io);

			$thermostatChannel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Channels\Thermostat::class,
				'device' => $device,
				'identifier' => Types\ChannelIdentifier::THERMOSTAT,
			]));
			assert($thermostatChannel instanceof Entities\Channels\Thermostat);

			$hvacModeProperty = $this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::HVAC_MODE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
					'format' => array_merge(
						[Types\HvacMode::OFF],
						$modes,
					),
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => null,
					'settable' => true,
					'queryable' => true,
				]),
			);

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::HVAC_STATE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
					'format' => array_merge(
						[Types\HvacState::OFF, Types\HvacState::INACTIVE],
						array_filter(
							array_map(static fn (string $mode): string|null => match ($mode) {
								Types\HvacMode::HEAT => Types\HvacState::HEATING,
								Types\HvacMode::COOL => Types\HvacState::COOLING,
								default => null,
							}, $modes),
							static fn (string|null $state): bool => $state !== null,
						),
					),
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => null,
					'settable' => false,
					'queryable' => true,
				]),
			);

			$heaters = $coolers = $openings = $sensors = $floorSensors = [];

			$actorsChannel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Channels\Actors::class,
				'device' => $device,
				'identifier' => Types\ChannelIdentifier::ACTORS,
			]));
			assert($actorsChannel instanceof Entities\Channels\Actors);

			$sensorsChannel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Channels\Sensors::class,
				'device' => $device,
				'identifier' => Types\ChannelIdentifier::SENSORS,
			]));
			assert($sensorsChannel instanceof Entities\Channels\Sensors);

			if (in_array(Types\HvacMode::HEAT, $modes, true)) {
				$io->info(
					$this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.messages.configureHeaters',
					),
				);

				do {
					$heater = $this->askActor(
						$io,
						array_map(
							static fn (DevicesEntities\Channels\Properties\Dynamic $heater): string => $heater->getId()->toString(),
							$heaters,
						),
						[
							MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
							MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SWITCH),
						],
					);

					$heaters[] = $heater;

					$this->createOrUpdateProperty(
						DevicesEntities\Channels\Properties\Mapped::class,
						Utils\ArrayHash::from([
							'parent' => $heater,
							'entity' => DevicesEntities\Channels\Properties\Mapped::class,
							'identifier' => $this->findChannelPropertyIdentifier(
								$actorsChannel,
								Types\ChannelPropertyIdentifier::HEATER,
							),
							'channel' => $actorsChannel,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
							'format' => null,
							'unit' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'settable' => true,
							'queryable' => true,
						]),
					);

					$question = new Console\Question\ConfirmationQuestion(
						$this->translator->translate(
							'//virtual-connector.cmd.devices.thermostat.questions.addAnotherHeater',
						),
						false,
					);

					$continue = (bool) $io->askQuestion($question);
				} while ($continue);
			}

			if (in_array(Types\HvacMode::COOL, $modes, true)) {
				$io->info(
					$this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.messages.configureCoolers',
					),
				);

				do {
					$cooler = $this->askActor(
						$io,
						array_map(
							static fn (DevicesEntities\Channels\Properties\Dynamic $cooler): string => $cooler->getId()->toString(),
							$coolers,
						),
						[
							MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
							MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SWITCH),
						],
					);

					$coolers[] = $cooler;

					$this->createOrUpdateProperty(
						DevicesEntities\Channels\Properties\Mapped::class,
						Utils\ArrayHash::from([
							'parent' => $cooler,
							'entity' => DevicesEntities\Channels\Properties\Mapped::class,
							'identifier' => $this->findChannelPropertyIdentifier(
								$actorsChannel,
								Types\ChannelPropertyIdentifier::COOLER,
							),
							'channel' => $actorsChannel,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
							'format' => null,
							'unit' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'settable' => true,
							'queryable' => true,
						]),
					);

					$question = new Console\Question\ConfirmationQuestion(
						$this->translator->translate(
							'//virtual-connector.cmd.devices.thermostat.questions.addAnotherCooler',
						),
						false,
					);

					$continue = (bool) $io->askQuestion($question);
				} while ($continue);
			}

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//virtual-connector.cmd.devices.thermostat.questions.useOpenings'),
				false,
			);

			$useOpenings = (bool) $io->askQuestion($question);

			if ($useOpenings) {
				$openingsChannel = $this->channelsManager->create(Utils\ArrayHash::from([
					'entity' => Entities\Channels\Sensors::class,
					'device' => $device,
					'identifier' => Types\ChannelIdentifier::OPENINGS,
				]));
				assert($openingsChannel instanceof Entities\Channels\Sensors);

				do {
					$opening = $this->askSensor(
						$io,
						array_map(
							static fn (DevicesEntities\Channels\Properties\Dynamic $opening): string => $opening->getId()->toString(),
							$openings,
						),
						[
							MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
						],
					);

					$openings[] = $opening;

					$this->createOrUpdateProperty(
						DevicesEntities\Channels\Properties\Mapped::class,
						Utils\ArrayHash::from([
							'parent' => $opening,
							'entity' => DevicesEntities\Channels\Properties\Mapped::class,
							'identifier' => $this->findChannelPropertyIdentifier(
								$openingsChannel,
								Types\ChannelPropertyIdentifier::SENSOR,
							),
							'channel' => $openingsChannel,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
							'format' => null,
							'unit' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'settable' => false,
							'queryable' => true,
						]),
					);

					$question = new Console\Question\ConfirmationQuestion(
						$this->translator->translate(
							'//virtual-connector.cmd.devices.thermostat.questions.addAnotherOpening',
						),
						false,
					);

					$continue = (bool) $io->askQuestion($question);
				} while ($continue);
			}

			$io->info(
				$this->translator->translate('//virtual-connector.cmd.devices.thermostat.messages.configureSensors'),
			);

			do {
				$sensor = $this->askSensor(
					$io,
					array_map(
						static fn (DevicesEntities\Channels\Properties\Dynamic $sensor): string => $sensor->getId()->toString(),
						$sensors,
					),
					[
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SHORT),
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_USHORT),
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_INT),
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
					],
				);

				$sensors[] = $sensor;

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Mapped::class,
					Utils\ArrayHash::from([
						'parent' => $sensor,
						'entity' => DevicesEntities\Channels\Properties\Mapped::class,
						'identifier' => $this->findChannelPropertyIdentifier(
							$sensorsChannel,
							Types\ChannelPropertyIdentifier::TARGET_SENSOR,
						),
						'channel' => $sensorsChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => null,
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => null,
						'settable' => false,
						'queryable' => true,
					]),
				);

				$question = new Console\Question\ConfirmationQuestion(
					$this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.questions.addAnotherSensor',
					),
					false,
				);

				$continue = (bool) $io->askQuestion($question);
			} while ($continue);

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//virtual-connector.cmd.devices.thermostat.questions.useFloorSensor'),
				false,
			);

			$useFloorSensor = (bool) $io->askQuestion($question);

			if ($useFloorSensor) {
				do {
					$sensor = $this->askSensor(
						$io,
						array_map(
							static fn (DevicesEntities\Channels\Properties\Dynamic $sensor): string => $sensor->getId()->toString(),
							$floorSensors,
						),
						[
							MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
							MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
							MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
							MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SHORT),
							MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_USHORT),
							MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_INT),
							MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
						],
					);

					$floorSensors[] = $sensor;

					$this->createOrUpdateProperty(
						DevicesEntities\Channels\Properties\Mapped::class,
						Utils\ArrayHash::from([
							'parent' => $sensor,
							'entity' => DevicesEntities\Channels\Properties\Mapped::class,
							'identifier' => $this->findChannelPropertyIdentifier(
								$sensorsChannel,
								Types\ChannelPropertyIdentifier::FLOOR_SENSOR,
							),
							'channel' => $sensorsChannel,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
							'format' => null,
							'unit' => null,
							'invalid' => null,
							'scale' => null,
							'step' => null,
							'settable' => false,
							'queryable' => true,
						]),
					);

					$question = new Console\Question\ConfirmationQuestion(
						$this->translator->translate(
							'//virtual-connector.cmd.devices.thermostat.questions.addAnotherFloorSensor',
						),
						false,
					);

					$continue = (bool) $io->askQuestion($question);
				} while ($continue);
			}

			$targetTemp = $this->askTargetTemperature(
				$io,
				Types\ThermostatMode::get(Types\ThermostatMode::MANUAL),
			);

			$targetTempProperty = $this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\Devices\Thermostat::PRECISION,
					'settable' => true,
					'queryable' => true,
				]),
			);

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::ACTUAL_TEMPERATURE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\Devices\Thermostat::PRECISION,
					'settable' => false,
					'queryable' => true,
				]),
			);

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Variable::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Types\ChannelPropertyIdentifier::LOW_TARGET_TEMPERATURE_TOLERANCE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => null,
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\Devices\Thermostat::PRECISION,
					'default' => null,
					'value' => Entities\Devices\Thermostat::COLD_TOLERANCE,
				]),
			);

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Variable::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Types\ChannelPropertyIdentifier::HIGH_TARGET_TEMPERATURE_TOLERANCE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => null,
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\Devices\Thermostat::PRECISION,
					'default' => null,
					'value' => Entities\Devices\Thermostat::HOT_TOLERANCE,
				]),
			);

			if ($useFloorSensor) {
				$maxFloorTemp = $this->askMaxFloorTemperature($io);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Variable::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => Types\ChannelPropertyIdentifier::MAXIMUM_FLOOR_TEMPERATURE,
						'channel' => $thermostatChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [0, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Thermostat::PRECISION,
						'default' => null,
						'value' => $maxFloorTemp,
					]),
				);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Dynamic::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
						'identifier' => Types\ChannelPropertyIdentifier::ACTUAL_FLOOR_TEMPERATURE,
						'channel' => $thermostatChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [0, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Thermostat::PRECISION,
						'settable' => false,
						'queryable' => true,
					]),
				);
			}

			if (in_array(Types\HvacMode::AUTO, $modes, true)) {
				$heatingThresholdTemp = $this->askHeatingThresholdTemperature(
					$io,
					Types\ThermostatMode::get(Types\ThermostatMode::MANUAL),
				);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Variable::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE,
						'channel' => $thermostatChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Thermostat::PRECISION,
						'default' => null,
						'value' => $heatingThresholdTemp,
					]),
				);

				$coolingThresholdTemp = $this->askCoolingThresholdTemperature(
					$io,
					Types\ThermostatMode::get(Types\ThermostatMode::MANUAL),
				);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Variable::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE,
						'channel' => $thermostatChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Thermostat::PRECISION,
						'default' => null,
						'value' => $coolingThresholdTemp,
					]),
				);
			}

			$presets = $this->askPresets($io);

			$presetModeProperty = $this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::PRESET_MODE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
					'format' => array_merge(
						[Types\ThermostatMode::MANUAL],
						$presets,
					),
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => null,
					'settable' => false,
					'queryable' => true,
				]),
			);

			foreach ($presets as $preset) {
				$io->info(
					$this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.messages.preset.' . $preset,
					),
				);

				$presetChannel = $this->channelsManager->create(Utils\ArrayHash::from([
					'entity' => Entities\Channels\Preset::class,
					'device' => $device,
					'identifier' => 'preset_' . $preset,
				]));
				assert($presetChannel instanceof Entities\Channels\Preset);

				$setPresets[$preset] = [
					'value' => $this->askTargetTemperature(
						$io,
						Types\ThermostatMode::get($preset),
					),
					'property' => $this->createOrUpdateProperty(
						DevicesEntities\Channels\Properties\Dynamic::class,
						Utils\ArrayHash::from([
							'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
							'identifier' => Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE,
							'channel' => $presetChannel,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
							'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
							'unit' => null,
							'invalid' => null,
							'scale' => null,
							'step' => Entities\Devices\Thermostat::PRECISION,
							'settable' => true,
							'queryable' => true,
						]),
					),
				];

				if (in_array(Types\HvacMode::AUTO, $modes, true)) {
					$heatingThresholdTemp = $this->askHeatingThresholdTemperature(
						$io,
						Types\ThermostatMode::get($preset),
					);

					$this->createOrUpdateProperty(
						DevicesEntities\Channels\Properties\Variable::class,
						Utils\ArrayHash::from([
							'entity' => DevicesEntities\Channels\Properties\Variable::class,
							'identifier' => Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE,
							'channel' => $presetChannel,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
							'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
							'unit' => null,
							'invalid' => null,
							'scale' => null,
							'step' => Entities\Devices\Thermostat::PRECISION,
							'default' => null,
							'value' => $heatingThresholdTemp,
						]),
					);

					$coolingThresholdTemp = $this->askCoolingThresholdTemperature(
						$io,
						Types\ThermostatMode::get($preset),
					);

					$this->createOrUpdateProperty(
						DevicesEntities\Channels\Properties\Variable::class,
						Utils\ArrayHash::from([
							'entity' => DevicesEntities\Channels\Properties\Variable::class,
							'identifier' => Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE,
							'channel' => $presetChannel,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
							'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
							'unit' => null,
							'invalid' => null,
							'scale' => null,
							'step' => Entities\Devices\Thermostat::PRECISION,
							'default' => null,
							'value' => $coolingThresholdTemp,
						]),
					);
				}
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//virtual-connector.cmd.devices.messages.create.device.success',
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

			$io->error(
				$this->translator->translate('//virtual-connector.cmd.devices.messages.create.device.error'),
			);

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		$this->channelPropertiesStatesManager->setValue(
			$hvacModeProperty,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_KEY => Types\HvacMode::OFF,
				DevicesStates\Property::EXPECTED_VALUE_KEY => null,
				DevicesStates\Property::VALID_KEY => true,
				DevicesStates\Property::PENDING_KEY => false,
			]),
		);

		$this->channelPropertiesStatesManager->setValue(
			$targetTempProperty,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_KEY => $targetTemp,
				DevicesStates\Property::EXPECTED_VALUE_KEY => null,
				DevicesStates\Property::VALID_KEY => true,
				DevicesStates\Property::PENDING_KEY => false,
			]),
		);

		$this->channelPropertiesStatesManager->setValue(
			$presetModeProperty,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_KEY => Types\ThermostatMode::MANUAL,
				DevicesStates\Property::EXPECTED_VALUE_KEY => null,
				DevicesStates\Property::VALID_KEY => true,
				DevicesStates\Property::PENDING_KEY => false,
			]),
		);

		foreach ($setPresets as $data) {
			$this->channelPropertiesStatesManager->setValue(
				$data['property'],
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_KEY => $data['value'],
					DevicesStates\Property::EXPECTED_VALUE_KEY => null,
					DevicesStates\Property::VALID_KEY => true,
					DevicesStates\Property::PENDING_KEY => false,
				]),
			);
		}
	}

	/**
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 */
	private function editDevice(Style\SymfonyStyle $io, Entities\Devices\Thermostat $device): void
	{
		$this->askEditAction($io, $device);
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 */
	private function editThermostat(Style\SymfonyStyle $io, Entities\Devices\Thermostat $device): void
	{
		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::MODEL);

		$deviceModelProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		$findChannelQuery = new Queries\FindThermostatChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(Types\ChannelIdentifier::THERMOSTAT);

		$thermostatChannel = $this->channelsRepository->findOneBy(
			$findChannelQuery,
			Entities\Channels\Thermostat::class,
		);

		$hvacModeProperty = $hvacStateProperty = $presetModeProperty = null;
		$maxFloorTempProperty = $actualFloorTempProperty = $targetTempProperty = $actualTempProperty = null;
		$heatingThresholdTempProperty = $coolingThresholdTempProperty = null;

		if ($thermostatChannel !== null) {
			$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::HVAC_MODE);

			$hvacModeProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::HVAC_STATE);

			$hvacStateProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::MAXIMUM_FLOOR_TEMPERATURE);

			$maxFloorTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::ACTUAL_FLOOR_TEMPERATURE);

			$actualFloorTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE);

			$targetTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::ACTUAL_TEMPERATURE);

			$actualTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE);

			$heatingThresholdTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE);

			$coolingThresholdTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::PRESET_MODE);

			$presetModeProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);
		}

		$name = $this->askDeviceName($io, $device);

		$modes = $this->askThermostatModes(
			$io,
			$hvacModeProperty instanceof DevicesEntities\Channels\Properties\Dynamic ? $hvacModeProperty : null,
		);

		$targetTemp = $this->askTargetTemperature(
			$io,
			Types\ThermostatMode::get(Types\ThermostatMode::MANUAL),
			$device,
		);

		$maxFloorTemp = null;

		if ($device->hasFloorSensors()) {
			$maxFloorTemp = $this->askMaxFloorTemperature($io, $device);
		}

		$heatingThresholdTemp = $coolingThresholdTemp = null;

		if (in_array(Types\HvacMode::AUTO, $modes, true)) {
			$heatingThresholdTemp = $this->askHeatingThresholdTemperature(
				$io,
				Types\ThermostatMode::get(Types\ThermostatMode::MANUAL),
				$device,
			);

			$coolingThresholdTemp = $this->askCoolingThresholdTemperature(
				$io,
				Types\ThermostatMode::get(Types\ThermostatMode::MANUAL),
				$device,
			);
		}

		$presets = $this->askPresets(
			$io,
			$presetModeProperty instanceof DevicesEntities\Channels\Properties\Dynamic ? $presetModeProperty : null,
		);

		$setPresets = [];

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->update($device, Utils\ArrayHash::from([
				'name' => $name,
			]));
			assert($device instanceof Entities\Devices\Thermostat);

			if (
				$deviceModelProperty !== null
				&& !$deviceModelProperty instanceof DevicesEntities\Devices\Properties\Variable
			) {
				$this->devicesPropertiesManager->delete($deviceModelProperty);

				$deviceModelProperty = null;
			}

			if ($deviceModelProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::MODEL,
					'device' => $device,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => Entities\Devices\Thermostat::TYPE,
				]));
			} else {
				$this->devicesPropertiesManager->update($deviceModelProperty, Utils\ArrayHash::from([
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'format' => null,
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => null,
					'default' => null,
					'value' => Entities\Devices\Thermostat::TYPE,
				]));
			}

			if ($thermostatChannel === null) {
				$thermostatChannel = $this->channelsManager->create(Utils\ArrayHash::from([
					'entity' => Entities\Channels\Thermostat::class,
					'device' => $device,
					'identifier' => Types\ChannelIdentifier::THERMOSTAT,
				]));
				assert($thermostatChannel instanceof Entities\Channels\Thermostat);
			}

			$hvacModeProperty = $this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::HVAC_MODE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
					'format' => array_merge(
						[Types\HvacMode::OFF],
						$modes,
					),
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => null,
					'settable' => true,
					'queryable' => true,
				]),
				$hvacModeProperty,
			);

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::HVAC_STATE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
					'format' => array_merge(
						[Types\HvacState::OFF, Types\HvacState::INACTIVE],
						array_filter(
							array_map(static fn (string $mode): string|null => match ($mode) {
								Types\HvacMode::HEAT => Types\HvacState::HEATING,
								Types\HvacMode::COOL => Types\HvacState::COOLING,
								default => null,
							}, $modes),
							static fn (string|null $state): bool => $state !== null,
						),
					),
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => null,
					'settable' => false,
					'queryable' => true,
				]),
				$hvacStateProperty,
			);

			$targetTempProperty = $this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\Devices\Thermostat::PRECISION,
					'settable' => true,
					'queryable' => true,
				]),
				$targetTempProperty,
			);

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::ACTUAL_TEMPERATURE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\Devices\Thermostat::PRECISION,
					'settable' => false,
					'queryable' => true,
				]),
				$actualTempProperty,
			);

			if ($device->hasFloorSensors()) {
				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Variable::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => Types\ChannelPropertyIdentifier::MAXIMUM_FLOOR_TEMPERATURE,
						'channel' => $thermostatChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [0, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Thermostat::PRECISION,
						'default' => null,
						'value' => $maxFloorTemp,
					]),
					$maxFloorTempProperty,
				);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Dynamic::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
						'identifier' => Types\ChannelPropertyIdentifier::ACTUAL_FLOOR_TEMPERATURE,
						'channel' => $thermostatChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [0, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Thermostat::PRECISION,
						'settable' => false,
						'queryable' => true,
					]),
					$actualFloorTempProperty,
				);
			} else {
				if ($maxFloorTempProperty !== null) {
					$this->channelsPropertiesManager->delete($maxFloorTempProperty);
				}

				if ($actualFloorTempProperty !== null) {
					$this->channelsPropertiesManager->delete($actualFloorTempProperty);
				}
			}

			if (in_array(Types\HvacMode::AUTO, $modes, true)) {
				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Variable::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE,
						'channel' => $thermostatChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Thermostat::PRECISION,
						'default' => null,
						'value' => $heatingThresholdTemp,
					]),
					$heatingThresholdTempProperty,
				);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Variable::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE,
						'channel' => $thermostatChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Thermostat::PRECISION,
						'default' => null,
						'value' => $coolingThresholdTemp,
					]),
					$coolingThresholdTempProperty,
				);
			} else {
				if ($heatingThresholdTempProperty !== null) {
					$this->channelsPropertiesManager->delete($heatingThresholdTempProperty);
				}

				if ($coolingThresholdTempProperty !== null) {
					$this->channelsPropertiesManager->delete($coolingThresholdTempProperty);
				}
			}

			$presetModeProperty = $this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::PRESET_MODE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
					'format' => array_merge(
						[Types\ThermostatMode::MANUAL],
						$presets,
					),
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => null,
					'settable' => false,
					'queryable' => true,
				]),
				$presetModeProperty,
			);

			foreach (Types\ThermostatMode::getAvailableValues() as $preset) {
				if ($preset === Types\ThermostatMode::MANUAL) {
					continue;
				}

				$findPresetChannelQuery = new Queries\FindPresetChannels();
				$findPresetChannelQuery->forDevice($device);
				$findPresetChannelQuery->byIdentifier('preset_' . $preset);

				$presetChannel = $this->channelsRepository->findOneBy(
					$findPresetChannelQuery,
					Entities\Channels\Preset::class,
				);

				if (in_array($preset, $presets, true)) {
					if ($presetChannel === null) {
						$presetChannel = $this->channelsManager->create(Utils\ArrayHash::from([
							'entity' => Entities\Channels\Preset::class,
							'device' => $device,
							'identifier' => 'preset_' . $preset,
						]));
						assert($presetChannel instanceof Entities\Channels\Preset);

						$setPresets[$preset] = [
							'value' => $this->askTargetTemperature(
								$io,
								Types\ThermostatMode::get($preset),
							),
							'property' => $this->createOrUpdateProperty(
								DevicesEntities\Channels\Properties\Dynamic::class,
								Utils\ArrayHash::from([
									'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
									'identifier' => Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE,
									'channel' => $presetChannel,
									'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
									'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
									'unit' => null,
									'invalid' => null,
									'scale' => null,
									'step' => Entities\Devices\Thermostat::PRECISION,
									'settable' => true,
									'queryable' => true,
								]),
							),
						];

						if (in_array(Types\HvacMode::AUTO, $modes, true)) {
							$heatingThresholdTemp = $this->askHeatingThresholdTemperature(
								$io,
								Types\ThermostatMode::get($preset),
							);

							$this->createOrUpdateProperty(
								DevicesEntities\Channels\Properties\Variable::class,
								Utils\ArrayHash::from([
									'entity' => DevicesEntities\Channels\Properties\Variable::class,
									'identifier' => Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE,
									'channel' => $presetChannel,
									'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
									'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
									'unit' => null,
									'invalid' => null,
									'scale' => null,
									'step' => Entities\Devices\Thermostat::PRECISION,
									'default' => null,
									'value' => $heatingThresholdTemp,
								]),
							);

							$coolingThresholdTemp = $this->askCoolingThresholdTemperature(
								$io,
								Types\ThermostatMode::get($preset),
							);

							$this->createOrUpdateProperty(
								DevicesEntities\Channels\Properties\Variable::class,
								Utils\ArrayHash::from([
									'entity' => DevicesEntities\Channels\Properties\Variable::class,
									'identifier' => Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE,
									'channel' => $presetChannel,
									'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
									'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
									'unit' => null,
									'invalid' => null,
									'scale' => null,
									'step' => Entities\Devices\Thermostat::PRECISION,
									'default' => null,
									'value' => $coolingThresholdTemp,
								]),
							);
						}
					}
				} else {
					if ($presetChannel !== null) {
						$this->channelsManager->delete($presetChannel);
					}
				}
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//virtual-connector.cmd.devices.messages.update.device.success',
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

			$io->error(
				$this->translator->translate('//virtual-connector.cmd.devices.messages.update.device.error'),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		assert($hvacModeProperty instanceof DevicesEntities\Channels\Properties\Dynamic);
		$this->channelPropertiesStatesManager->setValue(
			$hvacModeProperty,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_KEY => Types\HvacMode::OFF,
				DevicesStates\Property::EXPECTED_VALUE_KEY => null,
				DevicesStates\Property::VALID_KEY => true,
				DevicesStates\Property::PENDING_KEY => false,
			]),
		);

		assert($targetTempProperty instanceof DevicesEntities\Channels\Properties\Dynamic);
		$this->channelPropertiesStatesManager->setValue(
			$targetTempProperty,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_KEY => $targetTemp,
				DevicesStates\Property::EXPECTED_VALUE_KEY => null,
				DevicesStates\Property::VALID_KEY => true,
				DevicesStates\Property::PENDING_KEY => false,
			]),
		);

		assert($presetModeProperty instanceof DevicesEntities\Channels\Properties\Dynamic);
		$this->channelPropertiesStatesManager->setValue(
			$presetModeProperty,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_KEY => Types\ThermostatMode::MANUAL,
				DevicesStates\Property::EXPECTED_VALUE_KEY => null,
				DevicesStates\Property::VALID_KEY => true,
				DevicesStates\Property::PENDING_KEY => false,
			]),
		);

		foreach ($setPresets as $data) {
			$this->channelPropertiesStatesManager->setValue(
				$data['property'],
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_KEY => $data['value'],
					DevicesStates\Property::EXPECTED_VALUE_KEY => null,
					DevicesStates\Property::VALID_KEY => true,
					DevicesStates\Property::PENDING_KEY => false,
				]),
			);
		}
	}

	private function createActor(Style\SymfonyStyle $io, Entities\Devices\Thermostat $device): void
	{
		// TODO: Implement actor creation
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	private function editActor(Style\SymfonyStyle $io, Entities\Devices\Thermostat $device): void
	{
		$property = $this->askWhichActor($io, $device);

		if ($property === null) {
			$io->warning($this->translator->translate('//virtual-connector.cmd.devices.thermostat.messages.noActors'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//virtual-connector.cmd.devices.thermostat.questions.create.actor'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createActor($io, $device);
			}

			return;
		}

		$parent = $property->getParent();
		assert($parent instanceof DevicesEntities\Channels\Properties\Dynamic);

		$parent = $this->askActor(
			$io,
			[],
			[
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SWITCH),
			],
			$parent,
		);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
				'parent' => $parent,
			]));

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//virtual-connector.cmd.devices.messages.update.device.success',
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

			$io->error(
				$this->translator->translate('//virtual-connector.cmd.devices.messages.update.device.error'),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}
	}

	private function createSensor(Style\SymfonyStyle $io, Entities\Devices\Thermostat $device): void
	{
		// TODO: Implement sensor creation
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	private function editSensor(Style\SymfonyStyle $io, Entities\Devices\Thermostat $device): void
	{
		$property = $this->askWhichSensor($io, $device);

		if ($property === null) {
			$io->warning($this->translator->translate('//virtual-connector.cmd.devices.thermostat.messages.noSensors'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//virtual-connector.cmd.devices.thermostat.questions.create.sensor'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createSensor($io, $device);
			}

			return;
		}

		$parent = $property->getParent();
		assert($parent instanceof DevicesEntities\Channels\Properties\Dynamic);

		$parent = $this->askSensor(
			$io,
			[],
			[
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SHORT),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_USHORT),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_INT),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
			],
			$parent,
		);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
				'parent' => $parent,
			]));

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//virtual-connector.cmd.devices.messages.update.device.success',
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

			$io->error(
				$this->translator->translate('//virtual-connector.cmd.devices.messages.update.device.error'),
			);
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
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function editPreset(Style\SymfonyStyle $io, Entities\Devices\Thermostat $device): void
	{
		$preset = $this->askWhichPreset($io, $device);

		if ($preset === null) {
			$io->warning($this->translator->translate('//virtual-connector.cmd.devices.thermostat.messages.noPresets'));

			return;
		}

		$findChannelQuery = new Queries\FindPresetChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->endWithIdentifier($preset->getValue());

		$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Preset::class);

		$targetTempProperty = $heatingThresholdTempProperty = $coolingThresholdTempProperty = null;

		if ($channel !== null) {
			$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE);

			$targetTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE);

			$heatingThresholdTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE);

			$coolingThresholdTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);
		}

		$targetTemp = $this->askTargetTemperature($io, $preset, $device);

		$heatingThresholdTemp = $coolingThresholdTemp = null;

		if (in_array(Types\HvacMode::AUTO, $device->getHvacModes(), true)) {
			$heatingThresholdTemp = $this->askHeatingThresholdTemperature($io, $preset, $device);

			$coolingThresholdTemp = $this->askCoolingThresholdTemperature($io, $preset, $device);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if ($channel === null) {
				$channel = $this->channelsManager->create(Utils\ArrayHash::from([
					'entity' => Entities\Channels\Preset::class,
					'device' => $device,
					'identifier' => 'preset_' . $preset,
				]));
				assert($channel instanceof Entities\Channels\Preset);
			}

			$targetTempProperty = $this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE,
					'channel' => $channel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\Devices\Thermostat::PRECISION,
					'settable' => true,
					'queryable' => true,
				]),
				$targetTempProperty,
			);

			if (in_array(Types\HvacMode::AUTO, $device->getHvacModes(), true)) {
				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Variable::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE,
						'channel' => $channel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Thermostat::PRECISION,
						'default' => null,
						'value' => $heatingThresholdTemp,
					]),
					$heatingThresholdTempProperty,
				);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Variable::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE,
						'channel' => $channel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\Devices\Thermostat::PRECISION,
						'default' => null,
						'value' => $coolingThresholdTemp,
					]),
					$coolingThresholdTempProperty,
				);
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//virtual-connector.cmd.devices.messages.update.device.success',
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

			$io->error(
				$this->translator->translate('//virtual-connector.cmd.devices.messages.update.device.error'),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		assert($targetTempProperty instanceof DevicesEntities\Channels\Properties\Dynamic);
		$this->channelPropertiesStatesManager->setValue(
			$targetTempProperty,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_KEY => $targetTemp,
				DevicesStates\Property::EXPECTED_VALUE_KEY => null,
				DevicesStates\Property::VALID_KEY => true,
				DevicesStates\Property::PENDING_KEY => false,
			]),
		);
	}

	/**
	 * @return array<string>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 */
	private function askThermostatModes(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Properties\Dynamic|null $property = null,
	): array
	{
		if (
			$property !== null
			&& (
				$property->getIdentifier() !== Types\ChannelPropertyIdentifier::HVAC_MODE
				|| !$property->getFormat() instanceof MetadataValueObjects\StringEnumFormat
			)
		) {
			throw new Exceptions\InvalidArgument('Provided property is not valid');
		}

		$format = $property?->getFormat();
		assert($format === null || $format instanceof MetadataValueObjects\StringEnumFormat);

		$default = array_filter(
			array_unique(array_map(static fn ($item): int|null => match ($item) {
					Types\HvacMode::HEAT => 0,
					Types\HvacMode::COOL => 1,
					Types\HvacMode::AUTO => 2,
					default => null,
			}, $format?->toArray() ?? [Types\HvacMode::HEAT])),
			static fn (int|null $item): bool => $item !== null,
		);

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//virtual-connector.cmd.devices.thermostat.questions.select.mode'),
			[
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.mode.' . Types\HvacMode::HEAT,
				),
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.mode.' . Types\HvacMode::COOL,
				),
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.mode.' . Types\HvacMode::AUTO,
				),
			],
			implode(',', $default),
		);
		$question->setMultiselect(true);
		$question->setErrorMessage(
			$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer): array {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			$modes = [];

			foreach (explode(',', strval($answer)) as $item) {
				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.mode.' . Types\HvacMode::HEAT,
					)
					|| $item === '0'
				) {
					$modes[] = Types\HvacMode::HEAT;
				}

				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.mode.' . Types\HvacMode::COOL,
					)
					|| $item === '1'
				) {
					$modes[] = Types\HvacMode::COOL;
				}

				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.mode.' . Types\HvacMode::AUTO,
					)
					|| $item === '2'
				) {
					$modes[] = Types\HvacMode::AUTO;
				}
			}

			if ($modes !== []) {
				return $modes;
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$modes = $io->askQuestion($question);
		assert(is_array($modes));

		if (in_array(Types\HvacMode::AUTO, $modes, true)) {
			$modes[] = Types\HvacMode::COOL;
			$modes[] = Types\HvacMode::HEAT;

			$modes = array_unique($modes);
		}

		return $modes;
	}

	/**
	 * @return array<string>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 */
	private function askPresets(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Properties\Dynamic|null $property = null,
	): array
	{
		if (
			$property !== null
			&& (
				$property->getIdentifier() !== Types\ChannelPropertyIdentifier::PRESET_MODE
				|| !$property->getFormat() instanceof MetadataValueObjects\StringEnumFormat
			)
		) {
			throw new Exceptions\InvalidArgument('Provided property is not valid');
		}

		$format = $property?->getFormat();
		assert($format === null || $format instanceof MetadataValueObjects\StringEnumFormat);

		$default = array_filter(
			array_unique(array_map(static fn ($item): int|null => match ($item) {
					Types\ThermostatMode::AWAY => 0,
					Types\ThermostatMode::ECO => 1,
					Types\ThermostatMode::HOME => 2,
					Types\ThermostatMode::COMFORT => 3,
					Types\ThermostatMode::SLEEP => 4,
					Types\ThermostatMode::ANTI_FREEZE => 5,
					default => null,
			}, $format?->toArray() ?? [
				Types\ThermostatMode::AWAY,
				Types\ThermostatMode::ECO,
				Types\ThermostatMode::HOME,
				Types\ThermostatMode::COMFORT,
				Types\ThermostatMode::SLEEP,
				Types\ThermostatMode::ANTI_FREEZE,
			])),
			static fn (int|null $item): bool => $item !== null,
		);

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//virtual-connector.cmd.devices.thermostat.questions.select.preset'),
			[
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Types\ThermostatMode::AWAY,
				),
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Types\ThermostatMode::ECO,
				),
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Types\ThermostatMode::HOME,
				),
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Types\ThermostatMode::COMFORT,
				),
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Types\ThermostatMode::SLEEP,
				),
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Types\ThermostatMode::ANTI_FREEZE,
				),
			],
			implode(',', $default),
		);
		$question->setMultiselect(true);
		$question->setErrorMessage(
			$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer): array {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			$presets = [];

			foreach (explode(',', strval($answer)) as $item) {
				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Types\ThermostatMode::AWAY,
					)
					|| $item === '0'
				) {
					$presets[] = Types\ThermostatMode::AWAY;
				}

				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Types\ThermostatMode::ECO,
					)
					|| $item === '1'
				) {
					$presets[] = Types\ThermostatMode::ECO;
				}

				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Types\ThermostatMode::HOME,
					)
					|| $item === '2'
				) {
					$presets[] = Types\ThermostatMode::HOME;
				}

				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Types\ThermostatMode::COMFORT,
					)
					|| $item === '3'
				) {
					$presets[] = Types\ThermostatMode::COMFORT;
				}

				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Types\ThermostatMode::SLEEP,
					)
					|| $item === '4'
				) {
					$presets[] = Types\ThermostatMode::SLEEP;
				}

				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Types\ThermostatMode::ANTI_FREEZE,
					)
					|| $item === '5'
				) {
					$presets[] = Types\ThermostatMode::ANTI_FREEZE;
				}
			}

			if ($presets !== []) {
				return $presets;
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$presets = $io->askQuestion($question);
		assert(is_array($presets));

		return $presets;
	}

	/**
	 * @param array<string> $ignoredIds
	 * @param array<MetadataTypes\DataType>|null $allowedDataTypes
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	private function askActor(
		Style\SymfonyStyle $io,
		array $ignoredIds = [],
		array|null $allowedDataTypes = null,
		DevicesEntities\Channels\Properties\Dynamic|null $property = null,
	): DevicesEntities\Channels\Properties\Dynamic
	{
		$parent = $this->askProperty(
			$io,
			$ignoredIds,
			$allowedDataTypes,
			DevicesEntities\Channels\Properties\Dynamic::class,
			$property,
		);

		if (!$parent instanceof DevicesEntities\Channels\Properties\Dynamic) {
			$io->error(
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.messages.property.notSupported',
				),
			);

			return $this->askActor($io, $ignoredIds, $allowedDataTypes, $property);
		}

		return $parent;
	}

	/**
	 * @param array<string> $ignoredIds
	 * @param array<MetadataTypes\DataType>|null $allowedDataTypes
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	private function askSensor(
		Style\SymfonyStyle $io,
		array $ignoredIds = [],
		array|null $allowedDataTypes = null,
		DevicesEntities\Channels\Properties\Dynamic|null $property = null,
	): DevicesEntities\Channels\Properties\Dynamic
	{
		$parent = $this->askProperty(
			$io,
			$ignoredIds,
			$allowedDataTypes,
			DevicesEntities\Channels\Properties\Dynamic::class,
			$property,
		);

		if (!$parent instanceof DevicesEntities\Channels\Properties\Dynamic) {
			$io->error(
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.messages.property.notSupported',
				),
			);

			return $this->askSensor($io, $ignoredIds, $allowedDataTypes, $property);
		}

		return $parent;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askTargetTemperature(
		Style\SymfonyStyle $io,
		Types\ThermostatMode $thermostatMode,
		Entities\Devices\Thermostat|null $device = null,
	): float
	{
		try {
			$property = $device?->getTargetTemp($thermostatMode);
		} catch (Exceptions\InvalidState) {
			$property = null;
		}

		$targetTemp = null;

		if ($property !== null) {
			$state = $this->channelPropertiesStatesManager->readValue($property);

			$targetTemp = $state?->getActualValue();
			assert(is_numeric($targetTemp) || $targetTemp === null);
		}

		$question = new Console\Question\Question(
			$this->translator->translate(
				'//virtual-connector.cmd.devices.thermostat.questions.provide.targetTemperature.' . $thermostatMode->getValue(),
			),
			$targetTemp,
		);
		$question->setValidator(function (string|int|null $answer): float {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (strval(floatval($answer)) === strval($answer)) {
				return floatval($answer);
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$targetTemp = $io->askQuestion($question);
		assert(is_float($targetTemp));

		return $targetTemp;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askMaxFloorTemperature(
		Style\SymfonyStyle $io,
		Entities\Devices\Thermostat|null $device = null,
	): float
	{
		$question = new Console\Question\Question(
			$this->translator->translate(
				'//virtual-connector.cmd.devices.thermostat.questions.provide.maximumFloorTemperature',
			),
			$device?->getMaximumFloorTemp(),
		);
		$question->setValidator(function (string|int|null $answer): float {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (strval(floatval($answer)) === strval($answer)) {
				return floatval($answer);
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$maximumFloorTemperature = $io->askQuestion($question);
		assert(is_float($maximumFloorTemperature));

		return $maximumFloorTemperature;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askHeatingThresholdTemperature(
		Style\SymfonyStyle $io,
		Types\ThermostatMode $thermostatMode,
		Entities\Devices\Thermostat|null $device = null,
	): float
	{
		$question = new Console\Question\Question(
			$this->translator->translate(
				'//virtual-connector.cmd.devices.thermostat.questions.provide.heatingThresholdTemperature',
			),
			$device?->getHeatingThresholdTemp($thermostatMode),
		);
		$question->setValidator(function (string|int|null $answer): float {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (strval(floatval($answer)) === strval($answer)) {
				return floatval($answer);
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$maximumFloorTemperature = $io->askQuestion($question);
		assert(is_float($maximumFloorTemperature));

		return $maximumFloorTemperature;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askCoolingThresholdTemperature(
		Style\SymfonyStyle $io,
		Types\ThermostatMode $thermostatMode,
		Entities\Devices\Thermostat|null $device = null,
	): float
	{
		$question = new Console\Question\Question(
			$this->translator->translate(
				'//virtual-connector.cmd.devices.thermostat.questions.provide.coolingThresholdTemperature',
			),
			$device?->getCoolingThresholdTemp($thermostatMode),
		);
		$question->setValidator(function (string|int|null $answer): float {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (strval(floatval($answer)) === strval($answer)) {
				return floatval($answer);
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$maximumFloorTemperature = $io->askQuestion($question);
		assert(is_float($maximumFloorTemperature));

		return $maximumFloorTemperature;
	}

	/**
	 * @param array<string> $ignoredIds
	 * @param array<MetadataTypes\DataType>|null $allowedDataTypes
	 * @param class-string<DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Variable> $onlyType
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	private function askProperty(
		Style\SymfonyStyle $io,
		array $ignoredIds = [],
		array|null $allowedDataTypes = null,
		string|null $onlyType = null,
		DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Variable|null $connectedProperty = null,
	): DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Variable|null
	{
		$devices = [];

		$connectedDevice = null;
		$connectedChannel = null;

		if (
			$connectedProperty instanceof DevicesEntities\Channels\Properties\Dynamic
			|| $connectedProperty instanceof DevicesEntities\Channels\Properties\Variable
		) {
			$connectedChannel = $connectedProperty->getChannel();
			$connectedDevice = $connectedProperty->getChannel()->getDevice();
		}

		$findDevicesQuery = new DevicesQueries\FindDevices();

		$systemDevices = $this->devicesRepository->findAllBy($findDevicesQuery);
		usort(
			$systemDevices,
			static fn (DevicesEntities\Devices\Device $a, DevicesEntities\Devices\Device $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($systemDevices as $device) {
			if ($device instanceof Entities\VirtualDevice) {
				continue;
			}

			$findChannelsQuery = new DevicesQueries\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

			$hasProperty = false;

			foreach ($channels as $channel) {
				if ($onlyType === null || $onlyType === DevicesEntities\Channels\Properties\Dynamic::class) {
					$findChannelPropertiesQuery = new DevicesQueries\FindChannelDynamicProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					if ($allowedDataTypes === null) {
						if (
							$this->channelsPropertiesRepository->getResultSet(
								$findChannelPropertiesQuery,
								DevicesEntities\Channels\Properties\Dynamic::class,
							)->count() > 0
						) {
							$hasProperty = true;

							break;
						}
					} else {
						$properties = $this->channelsPropertiesRepository->findAllBy(
							$findChannelPropertiesQuery,
							DevicesEntities\Channels\Properties\Dynamic::class,
						);
						$properties = array_filter(
							$properties,
							static fn (DevicesEntities\Channels\Properties\Dynamic $property): bool => in_array(
								$property->getDataType(),
								$allowedDataTypes,
								true,
							),
						);

						if ($properties !== []) {
							$hasProperty = true;

							break;
						}
					}
				}

				if ($onlyType === null || $onlyType === DevicesEntities\Channels\Properties\Variable::class) {
					$findChannelPropertiesQuery = new DevicesQueries\FindChannelVariableProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					if ($allowedDataTypes === null) {
						if (
							$this->channelsPropertiesRepository->getResultSet(
								$findChannelPropertiesQuery,
								DevicesEntities\Channels\Properties\Variable::class,
							)->count() > 0
						) {
							$hasProperty = true;

							break;
						}
					} else {
						$properties = $this->channelsPropertiesRepository->findAllBy(
							$findChannelPropertiesQuery,
							DevicesEntities\Channels\Properties\Variable::class,
						);
						$properties = array_filter(
							$properties,
							static fn (DevicesEntities\Channels\Properties\Variable $property): bool => in_array(
								$property->getDataType(),
								$allowedDataTypes,
								true,
							),
						);

						if ($properties !== []) {
							$hasProperty = true;

							break;
						}
					}
				}
			}

			if (!$hasProperty) {
				continue;
			}

			$devices[$device->getId()->toString()] = $device->getIdentifier()
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				. ($device->getConnector()->getName() !== null ? ' [' . $device->getConnector()->getName() . ']' : ' [' . $device->getConnector()->getIdentifier() . ']')
				. ($device->getName() !== null ? ' [' . $device->getName() . ']' : '');
		}

		if (count($devices) === 0) {
			$io->warning(
				$this->translator->translate('//virtual-connector.cmd.devices.thermostat.messages.noHardwareDevices'),
			);

			return null;
		}

		$default = count($devices) === 1 ? 0 : null;

		if ($connectedDevice !== null) {
			foreach (array_values($devices) as $index => $value) {
				if (Utils\Strings::contains($value, $connectedDevice->getIdentifier())) {
					$default = $index;

					break;
				}
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//virtual-connector.cmd.devices.thermostat.questions.select.mappedDevice'),
			array_values($devices),
			$default,
		);
		$question->setErrorMessage(
			$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($devices): DevicesEntities\Devices\Device {
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
				$findDeviceQuery = new DevicesQueries\FindDevices();
				$findDeviceQuery->byId(Uuid\Uuid::fromString($identifier));

				$device = $this->devicesRepository->findOneBy($findDeviceQuery);

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
		});

		$device = $io->askQuestion($question);
		assert($device instanceof DevicesEntities\Devices\Device);

		$channels = [];

		$findChannelsQuery = new DevicesQueries\FindChannels();
		$findChannelsQuery->forDevice($device);
		$findChannelsQuery->withProperties();

		$deviceChannels = $this->channelsRepository->findAllBy($findChannelsQuery);
		usort(
			$deviceChannels,
			static function (DevicesEntities\Channels\Channel $a, DevicesEntities\Channels\Channel $b): int {
				if ($a->getIdentifier() === $b->getIdentifier()) {
					return $a->getName() <=> $b->getName();
				}

				return $a->getIdentifier() <=> $b->getIdentifier();
			},
		);

		foreach ($deviceChannels as $channel) {
			$hasProperty = false;

			if ($onlyType === null || $onlyType === DevicesEntities\Channels\Properties\Dynamic::class) {
				$findChannelPropertiesQuery = new DevicesQueries\FindChannelDynamicProperties();
				$findChannelPropertiesQuery->forChannel($channel);

				if ($allowedDataTypes === null) {
					if (
						$this->channelsPropertiesRepository->getResultSet(
							$findChannelPropertiesQuery,
							DevicesEntities\Channels\Properties\Dynamic::class,
						)->count() > 0
					) {
						$hasProperty = true;
					}
				} else {
					$properties = $this->channelsPropertiesRepository->findAllBy(
						$findChannelPropertiesQuery,
						DevicesEntities\Channels\Properties\Dynamic::class,
					);
					$properties = array_filter(
						$properties,
						static fn (DevicesEntities\Channels\Properties\Dynamic $property): bool => in_array(
							$property->getDataType(),
							$allowedDataTypes,
							true,
						),
					);

					if ($properties !== []) {
						$hasProperty = true;
					}
				}
			}

			if ($onlyType === null || $onlyType === DevicesEntities\Channels\Properties\Variable::class) {
				$findChannelPropertiesQuery = new DevicesQueries\FindChannelVariableProperties();
				$findChannelPropertiesQuery->forChannel($channel);

				if ($allowedDataTypes === null) {
					if (
						$this->channelsPropertiesRepository->getResultSet(
							$findChannelPropertiesQuery,
							DevicesEntities\Channels\Properties\Variable::class,
						)->count() > 0
					) {
						$hasProperty = true;
					}
				} else {
					$properties = $this->channelsPropertiesRepository->findAllBy(
						$findChannelPropertiesQuery,
						DevicesEntities\Channels\Properties\Variable::class,
					);
					$properties = array_filter(
						$properties,
						static fn (DevicesEntities\Channels\Properties\Variable $property): bool => in_array(
							$property->getDataType(),
							$allowedDataTypes,
							true,
						),
					);

					if ($properties !== []) {
						$hasProperty = true;
					}
				}
			}

			if (!$hasProperty) {
				continue;
			}

			$channels[$channel->getIdentifier()] = sprintf(
				'%s%s',
				$channel->getIdentifier(),
				($channel->getName() !== null ? ' [' . $channel->getName() . ']' : ''),
			);
		}

		$default = count($channels) === 1 ? 0 : null;

		if ($connectedChannel !== null) {
			foreach (array_values($channels) as $index => $value) {
				if (Utils\Strings::contains($value, $connectedChannel->getIdentifier())) {
					$default = $index;

					break;
				}
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate(
				'//virtual-connector.cmd.devices.thermostat.questions.select.mappedDeviceChannel',
			),
			array_values($channels),
			$default,
		);
		$question->setErrorMessage(
			$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|null $answer) use ($device, $channels): DevicesEntities\Channels\Channel {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($channels))) {
					$answer = array_values($channels)[$answer];
				}

				$identifier = array_search($answer, $channels, true);

				if ($identifier !== false) {
					$findChannelQuery = new DevicesQueries\FindChannels();
					$findChannelQuery->byIdentifier($identifier);
					$findChannelQuery->forDevice($device);

					$channel = $this->channelsRepository->findOneBy($findChannelQuery);

					if ($channel !== null) {
						return $channel;
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

		$channel = $io->askQuestion($question);
		assert($channel instanceof DevicesEntities\Channels\Channel);

		$properties = [];

		$findDevicePropertiesQuery = new DevicesQueries\FindChannelProperties();
		$findDevicePropertiesQuery->forChannel($channel);

		$channelProperties = $this->channelsPropertiesRepository->findAllBy($findDevicePropertiesQuery);
		usort(
			$channelProperties,
			static function (DevicesEntities\Channels\Properties\Property $a, DevicesEntities\Channels\Properties\Property $b): int {
				if ($a->getIdentifier() === $b->getIdentifier()) {
					return $a->getName() <=> $b->getName();
				}

				return $a->getIdentifier() <=> $b->getIdentifier();
			},
		);

		foreach ($channelProperties as $property) {
			if (
				!$property instanceof DevicesEntities\Channels\Properties\Dynamic
				&& !$property instanceof DevicesEntities\Channels\Properties\Variable
				|| in_array($property->getId()->toString(), $ignoredIds, true)
				|| (
					$onlyType !== null
					&& !$property instanceof $onlyType
				)
				|| (
					$allowedDataTypes !== null
					&& !in_array($property->getDataType(), $allowedDataTypes, true)
				)
			) {
				continue;
			}

			$properties[$property->getIdentifier()] = sprintf(
				'%s%s',
				$property->getIdentifier(),
				' [' . ($property->getName() ?? DevicesUtilities\Name::createName($property->getIdentifier())) . ']',
			);
		}

		$default = count($properties) === 1 ? 0 : null;

		if ($connectedProperty !== null) {
			foreach (array_values($properties) as $index => $value) {
				if (Utils\Strings::contains($value, $connectedProperty->getIdentifier())) {
					$default = $index;

					break;
				}
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate(
				'//virtual-connector.cmd.devices.thermostat.questions.select.mappedChannelProperty',
			),
			array_values($properties),
			$default,
		);
		$question->setErrorMessage(
			$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			function (string|null $answer) use ($channel, $properties): DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Variable {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($properties))) {
					$answer = array_values($properties)[$answer];
				}

				$identifier = array_search($answer, $properties, true);

				if ($identifier !== false) {
					$findPropertyQuery = new DevicesQueries\FindChannelProperties();
					$findPropertyQuery->byIdentifier($identifier);
					$findPropertyQuery->forChannel($channel);

					$property = $this->channelsPropertiesRepository->findOneBy($findPropertyQuery);

					if ($property !== null) {
						assert(
							$property instanceof DevicesEntities\Channels\Properties\Dynamic
							|| $property instanceof DevicesEntities\Channels\Properties\Variable,
						);

						return $property;
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

		$property = $io->askQuestion($question);
		assert(
			$property instanceof DevicesEntities\Channels\Properties\Dynamic || $property instanceof DevicesEntities\Channels\Properties\Variable,
		);

		return $property;
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askEditAction(
		Style\SymfonyStyle $io,
		Entities\Devices\Thermostat $device,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//virtual-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//virtual-connector.cmd.devices.thermostat.actions.editThermostat'),
				1 => $this->translator->translate('//virtual-connector.cmd.devices.thermostat.actions.editActor'),
				2 => $this->translator->translate('//virtual-connector.cmd.devices.thermostat.actions.editSensor'),
				3 => $this->translator->translate('//virtual-connector.cmd.devices.thermostat.actions.editPreset'),
				4 => $this->translator->translate('//virtual-connector.cmd.devices.thermostat.actions.back'),
			],
			4,
		);

		$question->setErrorMessage(
			$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//virtual-connector.cmd.devices.thermostat.actions.editThermostat',
			)
			|| $whatToDo === '0'
		) {
			$this->editThermostat($io, $device);

			$this->askEditAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//virtual-connector.cmd.devices.thermostat.actions.editActor',
			)
			|| $whatToDo === '1'
		) {
			$this->editActor($io, $device);

			$this->askEditAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//virtual-connector.cmd.devices.thermostat.actions.editSensor',
			)
			|| $whatToDo === '2'
		) {
			$this->editSensor($io, $device);

			$this->askEditAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//virtual-connector.cmd.devices.thermostat.actions.editPreset',
			)
			|| $whatToDo === '3'
		) {
			$this->editPreset($io, $device);

			$this->askEditAction($io, $device);
		}
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 */
	private function askWhichPreset(
		Style\SymfonyStyle $io,
		Entities\Devices\Thermostat $device,
	): Types\ThermostatMode|null
	{
		$allowedValues = $device->getPresetModes();

		if ($allowedValues === []) {
			return null;
		}

		$presets = [];

		foreach (Types\ThermostatMode::getAvailableValues() as $preset) {
			if (
				!in_array($preset, $allowedValues, true)
				|| in_array($preset, [Types\ThermostatMode::MANUAL, Types\ThermostatMode::AUTO], true)
			) {
				continue;
			}

			$presets[$preset] = $this->translator->translate(
				'//virtual-connector.cmd.devices.thermostat.answers.preset.' . $preset,
			);
		}

		if (count($presets) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//virtual-connector.cmd.devices.thermostat.questions.select.presetToUpdate'),
			array_values($presets),
			count($presets) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($presets): Types\ThermostatMode {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($presets))) {
					$answer = array_values($presets)[$answer];
				}

				$preset = array_search($answer, $presets, true);

				if ($preset !== false && Types\ThermostatMode::isValidValue($preset)) {
					return Types\ThermostatMode::get($preset);
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$preset = $io->askQuestion($question);
		assert($preset instanceof Types\ThermostatMode);

		return $preset;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichActor(
		Style\SymfonyStyle $io,
		Entities\Devices\Thermostat $device,
	): DevicesEntities\Channels\Properties\Mapped|null
	{
		$actors = [];

		$findChannelsQuery = new Queries\FindActorChannels();
		$findChannelsQuery->forDevice($device);

		$channel = $this->channelsRepository->findOneBy($findChannelsQuery, Entities\Channels\Actors::class);

		if ($channel === null) {
			return null;
		}

		$findChannelPropertiesQuery = new DevicesQueries\FindChannelMappedProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		$channelActors = $this->channelsPropertiesRepository->findAllBy(
			$findChannelPropertiesQuery,
			DevicesEntities\Channels\Properties\Mapped::class,
		);
		usort(
			$channelActors,
			static fn (DevicesEntities\Channels\Properties\Mapped $a, DevicesEntities\Channels\Properties\Mapped $b): int =>
				$a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($channelActors as $channelActor) {
			$actors[$channelActor->getIdentifier()] = $channelActor->getIdentifier()
				. (
					$channelActor->getName() !== null
						? '[' . $channelActor->getName() . ']'
						: ' [' . ($channelActor->getParent()->getName() ?? $channelActor->getParent()->getIdentifier()) . ']'
				);
		}

		if (count($actors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//virtual-connector.cmd.devices.thermostat.questions.select.actorToUpdate'),
			array_values($actors),
			count($actors) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($channel, $actors): DevicesEntities\Channels\Properties\Mapped {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($actors))) {
					$answer = array_values($actors)[$answer];
				}

				$identifier = array_search($answer, $actors, true);

				if ($identifier !== false) {
					$findChannelPropertiesQuery = new DevicesQueries\FindChannelMappedProperties();
					$findChannelPropertiesQuery->byIdentifier($identifier);
					$findChannelPropertiesQuery->forChannel($channel);

					$actor = $this->channelsPropertiesRepository->findOneBy(
						$findChannelPropertiesQuery,
						DevicesEntities\Channels\Properties\Mapped::class,
					);

					if ($actor !== null) {
						return $actor;
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

		$actor = $io->askQuestion($question);
		assert($actor instanceof DevicesEntities\Channels\Properties\Mapped);

		return $actor;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichSensor(
		Style\SymfonyStyle $io,
		Entities\Devices\Thermostat $device,
	): DevicesEntities\Channels\Properties\Mapped|null
	{
		$sensors = [];

		$findChannelsQuery = new Queries\FindSensorChannels();
		$findChannelsQuery->forDevice($device);

		$channel = $this->channelsRepository->findOneBy($findChannelsQuery, Entities\Channels\Sensors::class);

		if ($channel === null) {
			return null;
		}

		$findChannelPropertiesQuery = new DevicesQueries\FindChannelMappedProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		$channelActors = $this->channelsPropertiesRepository->findAllBy(
			$findChannelPropertiesQuery,
			DevicesEntities\Channels\Properties\Mapped::class,
		);
		usort(
			$channelActors,
			static fn (DevicesEntities\Channels\Properties\Mapped $a, DevicesEntities\Channels\Properties\Mapped $b): int =>
				$a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($channelActors as $channelActor) {
			$sensors[$channelActor->getIdentifier()] = $channelActor->getIdentifier()
				. (
					$channelActor->getName() !== null
						? '[' . $channelActor->getName() . ']'
						: ' [' . ($channelActor->getParent()->getName() ?? $channelActor->getParent()->getIdentifier()) . ']'
				);
		}

		if (count($sensors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//virtual-connector.cmd.devices.thermostat.questions.select.sensorToUpdate'),
			array_values($sensors),
			count($sensors) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($channel, $sensors): DevicesEntities\Channels\Properties\Mapped {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($sensors))) {
					$answer = array_values($sensors)[$answer];
				}

				$identifier = array_search($answer, $sensors, true);

				if ($identifier !== false) {
					$findChannelPropertiesQuery = new DevicesQueries\FindChannelMappedProperties();
					$findChannelPropertiesQuery->byIdentifier($identifier);
					$findChannelPropertiesQuery->forChannel($channel);

					$sensor = $this->channelsPropertiesRepository->findOneBy(
						$findChannelPropertiesQuery,
						DevicesEntities\Channels\Properties\Mapped::class,
					);

					if ($sensor !== null) {
						return $sensor;
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

		$sensor = $io->askQuestion($question);
		assert($sensor instanceof DevicesEntities\Channels\Properties\Mapped);

		return $sensor;
	}

	/**
	 * @template T as DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Variable|DevicesEntities\Channels\Properties\Mapped
	 *
	 * @param class-string<T> $propertyType
	 *
	 * @return T
	 *
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	private function createOrUpdateProperty(
		string $propertyType,
		Utils\ArrayHash $data,
		DevicesEntities\Channels\Properties\Property|null $property = null,
	): DevicesEntities\Channels\Properties\Property
	{
		if ($property !== null && !$property instanceof $propertyType) {
			$this->channelsPropertiesManager->delete($property);

			$property = null;
		}

		if ($property === null) {
			$property = $this->channelsPropertiesManager->create($data);
			assert($property instanceof $propertyType);
		} else {
			$property = $this->channelsPropertiesManager->update($property, $data);
			assert($property instanceof $propertyType);
		}

		return $property;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	private function findChannelPropertyIdentifier(DevicesEntities\Channels\Channel $channel, string $prefix): string
	{
		$identifierPattern = $prefix . '_%d';

		for ($i = 1; $i <= 100; $i++) {
			$identifier = sprintf($identifierPattern, $i);

			$findChannelPropertiesQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertiesQuery->forChannel($channel);
			$findChannelPropertiesQuery->byIdentifier($identifier);

			if ($this->channelsPropertiesRepository->getResultSet($findChannelPropertiesQuery)->isEmpty()) {
				return $identifier;
			}
		}

		throw new Exceptions\InvalidState('Channel property identifier could not be created');
	}

}
