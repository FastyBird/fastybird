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
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Helpers as DevicesHelpers;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
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
		private readonly DevicesModels\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
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
				$this->editDevice($io, $connector);

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
				'identifier' => Virtual\Types\DevicePropertyIdentifier::MODEL,
				'device' => $device,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => Entities\Devices\Thermostat::TYPE,
			]));

			$modes = $this->askThermostatModes($io);

			$thermostatChannel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Channels\Thermostat::class,
				'device' => $device,
				'identifier' => Virtual\Types\ChannelIdentifier::THERMOSTAT,
			]));
			assert($thermostatChannel instanceof Entities\Channels\Thermostat);

			$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
				'identifier' => Virtual\Types\ChannelPropertyIdentifier::HVAC_MODE,
				'channel' => $thermostatChannel,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'format' => array_merge(
					[Virtual\Types\HvacMode::OFF],
					$modes,
				),
			]));

			$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
				'identifier' => Virtual\Types\ChannelPropertyIdentifier::HVAC_STATE,
				'channel' => $thermostatChannel,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'format' => array_merge(
					[Virtual\Types\HvacState::OFF, Virtual\Types\HvacState::INACTIVE],
					array_filter(
						array_map(static fn (string $mode): string|null => match ($mode) {
								Virtual\Types\HvacMode::HEAT => Virtual\Types\HvacState::HEATING,
								Virtual\Types\HvacMode::COOL => Virtual\Types\HvacState::COOLING,
								default => null,
						}, $modes),
						static fn (string|null $state): bool => $state !== null,
					),
				),
			]));

			$heaters = $coolers = $openings = $sensors = $floorSensors = [];

			$actorsChannel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Channels\Actors::class,
				'device' => $device,
				'identifier' => Virtual\Types\ChannelIdentifier::ACTORS,
			]));
			assert($actorsChannel instanceof Entities\Channels\Actors);

			$sensorsChannel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Channels\Sensors::class,
				'device' => $device,
				'identifier' => Virtual\Types\ChannelIdentifier::SENSORS,
			]));
			assert($sensorsChannel instanceof Entities\Channels\Sensors);

			if (in_array(Virtual\Types\HvacMode::HEAT, $modes, true)) {
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

					$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Mapped::class,
						'parent' => $heater,
						'identifier' => $this->findChannelPropertyIdentifier(
							$actorsChannel,
							Virtual\Types\ChannelPropertyIdentifier::HEATER,
						),
						'channel' => $actorsChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
					]));

					$question = new Console\Question\ConfirmationQuestion(
						$this->translator->translate(
							'//virtual-connector.cmd.devices.thermostat.questions.addAnotherHeater',
						),
						false,
					);

					$continue = (bool) $io->askQuestion($question);
				} while ($continue);
			}

			if (in_array(Virtual\Types\HvacMode::COOL, $modes, true)) {
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

					$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Mapped::class,
						'parent' => $cooler,
						'identifier' => $this->findChannelPropertyIdentifier(
							$actorsChannel,
							Virtual\Types\ChannelPropertyIdentifier::COOLER,
						),
						'channel' => $actorsChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
					]));

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
					'identifier' => Virtual\Types\ChannelIdentifier::OPENINGS,
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

					$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Mapped::class,
						'parent' => $opening,
						'identifier' => $this->findChannelPropertyIdentifier(
							$openingsChannel,
							Virtual\Types\ChannelPropertyIdentifier::SENSOR,
						),
						'channel' => $openingsChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
					]));

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

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Mapped::class,
					'parent' => $sensor,
					'identifier' => $this->findChannelPropertyIdentifier(
						$sensorsChannel,
						Virtual\Types\ChannelPropertyIdentifier::TARGET_SENSOR,
					),
					'channel' => $sensorsChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				]));

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

					$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Mapped::class,
						'parent' => $sensor,
						'identifier' => $this->findChannelPropertyIdentifier(
							$sensorsChannel,
							Virtual\Types\ChannelPropertyIdentifier::FLOOR_SENSOR,
						),
						'channel' => $sensorsChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					]));

					$question = new Console\Question\ConfirmationQuestion(
						$this->translator->translate(
							'//virtual-connector.cmd.devices.thermostat.questions.addAnotherFloorSensor',
						),
						false,
					);

					$continue = (bool) $io->askQuestion($question);
				} while ($continue);
			}

			$targetTemperature = $this->askTargetTemperature(
				$io,
				Virtual\Types\ThermostatMode::get(Virtual\Types\ThermostatMode::MANUAL),
			);

			$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Channels\Properties\Variable::class,
				'identifier' => Virtual\Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE,
				'channel' => $thermostatChannel,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
				'step' => Entities\Devices\Thermostat::PRECISION,
				'value' => $targetTemperature,
			]));

			$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
				'identifier' => Virtual\Types\ChannelPropertyIdentifier::ACTUAL_TEMPERATURE,
				'channel' => $thermostatChannel,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
				'step' => Entities\Devices\Thermostat::PRECISION,
			]));

			$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Channels\Properties\Variable::class,
				'identifier' => Virtual\Types\ChannelPropertyIdentifier::LOW_TARGET_TEMPERATURE_TOLERANCE,
				'channel' => $thermostatChannel,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				'step' => Entities\Devices\Thermostat::PRECISION,
				'value' => Entities\Devices\Thermostat::COLD_TOLERANCE,
			]));

			$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Channels\Properties\Variable::class,
				'identifier' => Virtual\Types\ChannelPropertyIdentifier::HIGH_TARGET_TEMPERATURE_TOLERANCE,
				'channel' => $thermostatChannel,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				'step' => Entities\Devices\Thermostat::PRECISION,
				'value' => Entities\Devices\Thermostat::HOT_TOLERANCE,
			]));

			if ($useFloorSensor) {
				$maxFloorTemperature = $this->askMaxFloorTemperature($io);

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Virtual\Types\ChannelPropertyIdentifier::MAXIMUM_FLOOR_TEMPERATURE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => [null, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
					'step' => Entities\Devices\Thermostat::PRECISION,
					'value' => $maxFloorTemperature,
				]));

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Virtual\Types\ChannelPropertyIdentifier::ACTUAL_FLOOR_TEMPERATURE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => [null, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
					'step' => Entities\Devices\Thermostat::PRECISION,
				]));
			}

			if (in_array(Virtual\Types\HvacMode::AUTO, $modes, true)) {
				$heatingThresholdTemperature = $this->askHeatingThresholdTemperature(
					$io,
					Virtual\Types\ThermostatMode::get(Virtual\Types\ThermostatMode::MANUAL),
				);

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Virtual\Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
					'step' => Entities\Devices\Thermostat::PRECISION,
					'value' => $heatingThresholdTemperature,
				]));

				$coolingThresholdTemperature = $this->askCoolingThresholdTemperature(
					$io,
					Virtual\Types\ThermostatMode::get(Virtual\Types\ThermostatMode::MANUAL),
				);

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Virtual\Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE,
					'channel' => $thermostatChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
					'step' => Entities\Devices\Thermostat::PRECISION,
					'value' => $coolingThresholdTemperature,
				]));
			}

			$presets = $this->askPresets($io);

			$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
				'identifier' => Virtual\Types\ChannelPropertyIdentifier::PRESET_MODE,
				'channel' => $thermostatChannel,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'format' => array_merge(
					[Virtual\Types\ThermostatMode::MANUAL],
					$presets,
				),
			]));

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

				$targetTemperature = $this->askTargetTemperature(
					$io,
					Virtual\Types\ThermostatMode::get($preset),
				);

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Virtual\Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE,
					'channel' => $presetChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
					'step' => Entities\Devices\Thermostat::PRECISION,
					'value' => $targetTemperature,
				]));

				if (in_array(Virtual\Types\HvacMode::AUTO, $modes, true)) {
					$heatingThresholdTemperature = $this->askHeatingThresholdTemperature(
						$io,
						Virtual\Types\ThermostatMode::get($preset),
					);

					$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => Virtual\Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE,
						'channel' => $presetChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
						'step' => Entities\Devices\Thermostat::PRECISION,
						'value' => $heatingThresholdTemperature,
					]));

					$coolingThresholdTemperature = $this->askCoolingThresholdTemperature(
						$io,
						Virtual\Types\ThermostatMode::get($preset),
					);

					$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => Virtual\Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE,
						'channel' => $presetChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [Entities\Devices\Thermostat::MINIMUM_TEMPERATURE, Entities\Devices\Thermostat::MAXIMUM_TEMPERATURE],
						'step' => Entities\Devices\Thermostat::PRECISION,
						'value' => $coolingThresholdTemperature,
					]));
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
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\Runtime
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 */
	private function editDevice(Style\SymfonyStyle $io, Entities\VirtualConnector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

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

			return;
		}

		$name = $this->askDeviceName($io, $device);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->update($device, Utils\ArrayHash::from([
				'name' => $name,
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
	 * @return array<string>
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 */
	private function askThermostatModes(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Properties\Variable|null $property = null,
	): array
	{
		if (
			$property !== null
			&& (
				$property->getIdentifier() !== Virtual\Types\ChannelPropertyIdentifier::HVAC_MODE
				|| !$property->getFormat() instanceof MetadataValueObjects\StringEnumFormat
			)
		) {
			throw new Exceptions\InvalidArgument('Provided property is not valid');
		}

		$format = $property?->getFormat();
		assert($format === null || $format instanceof MetadataValueObjects\StringEnumFormat);

		$default = array_filter(
			array_unique(array_map(static fn ($item): int|null => match ($item) {
					Virtual\Types\HvacMode::HEAT => 0,
					Virtual\Types\HvacMode::COOL => 1,
					Virtual\Types\HvacMode::AUTO => 2,
					default => null,
			}, $format?->toArray() ?? [Virtual\Types\HvacMode::HEAT])),
			static fn (int|null $item): bool => $item !== null,
		);

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//virtual-connector.cmd.devices.thermostat.questions.select.mode'),
			[
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.mode.' . Virtual\Types\HvacMode::HEAT,
				),
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.mode.' . Virtual\Types\HvacMode::COOL,
				),
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.mode.' . Virtual\Types\HvacMode::AUTO,
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
						'//virtual-connector.cmd.devices.thermostat.answers.mode.' . Virtual\Types\HvacMode::HEAT,
					)
					|| $item === '0'
				) {
					$modes[] = Virtual\Types\HvacMode::HEAT;
				}

				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.mode.' . Virtual\Types\HvacMode::COOL,
					)
					|| $item === '1'
				) {
					$modes[] = Virtual\Types\HvacMode::COOL;
				}

				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.mode.' . Virtual\Types\HvacMode::AUTO,
					)
					|| $item === '2'
				) {
					$modes[] = Virtual\Types\HvacMode::AUTO;
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
		DevicesEntities\Channels\Properties\Variable|null $property = null,
	): array
	{
		if (
			$property !== null
			&& (
				$property->getIdentifier() !== Virtual\Types\ChannelPropertyIdentifier::PRESET_MODE
				|| !$property->getFormat() instanceof MetadataValueObjects\StringEnumFormat
			)
		) {
			throw new Exceptions\InvalidArgument('Provided property is not valid');
		}

		$format = $property?->getFormat();
		assert($format === null || $format instanceof MetadataValueObjects\StringEnumFormat);

		$default = array_filter(
			array_unique(array_map(static fn ($item): int|null => match ($item) {
					Virtual\Types\ThermostatMode::AWAY => 0,
					Virtual\Types\ThermostatMode::ECO => 1,
					Virtual\Types\ThermostatMode::HOME => 2,
					Virtual\Types\ThermostatMode::COMFORT => 3,
					Virtual\Types\ThermostatMode::SLEEP => 4,
					Virtual\Types\ThermostatMode::ANTI_FREEZE => 5,
					default => null,
			}, $format?->toArray() ?? [
				Virtual\Types\ThermostatMode::AWAY,
				Virtual\Types\ThermostatMode::ECO,
				Virtual\Types\ThermostatMode::HOME,
				Virtual\Types\ThermostatMode::COMFORT,
				Virtual\Types\ThermostatMode::SLEEP,
				Virtual\Types\ThermostatMode::ANTI_FREEZE,
			])),
			static fn (int|null $item): bool => $item !== null,
		);

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//virtual-connector.cmd.devices.thermostat.questions.select.preset'),
			[
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Virtual\Types\ThermostatMode::AWAY,
				),
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Virtual\Types\ThermostatMode::ECO,
				),
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Virtual\Types\ThermostatMode::HOME,
				),
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Virtual\Types\ThermostatMode::COMFORT,
				),
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Virtual\Types\ThermostatMode::SLEEP,
				),
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Virtual\Types\ThermostatMode::ANTI_FREEZE,
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
						'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Virtual\Types\ThermostatMode::AWAY,
					)
					|| $item === '0'
				) {
					$presets[] = Virtual\Types\ThermostatMode::AWAY;
				}

				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Virtual\Types\ThermostatMode::ECO,
					)
					|| $item === '1'
				) {
					$presets[] = Virtual\Types\ThermostatMode::ECO;
				}

				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Virtual\Types\ThermostatMode::HOME,
					)
					|| $item === '2'
				) {
					$presets[] = Virtual\Types\ThermostatMode::HOME;
				}

				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Virtual\Types\ThermostatMode::COMFORT,
					)
					|| $item === '3'
				) {
					$presets[] = Virtual\Types\ThermostatMode::COMFORT;
				}

				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Virtual\Types\ThermostatMode::SLEEP,
					)
					|| $item === '4'
				) {
					$presets[] = Virtual\Types\ThermostatMode::SLEEP;
				}

				if (
					$item === $this->translator->translate(
						'//virtual-connector.cmd.devices.thermostat.answers.preset.' . Virtual\Types\ThermostatMode::ANTI_FREEZE,
					)
					|| $item === '5'
				) {
					$presets[] = Virtual\Types\ThermostatMode::ANTI_FREEZE;
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
		DevicesEntities\Channels\Properties\Variable|null $property = null,
	): DevicesEntities\Channels\Properties\Dynamic
	{
		$property = $this->askProperty(
			$io,
			$ignoredIds,
			$allowedDataTypes,
			DevicesEntities\Channels\Properties\Dynamic::class,
			$property,
		);

		if (!$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
			$io->error(
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.messages.property.notSupported',
				),
			);

			return $this->askActor($io, $ignoredIds, $allowedDataTypes, $property);
		}

		return $property;
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
		DevicesEntities\Channels\Properties\Variable|null $property = null,
	): DevicesEntities\Channels\Properties\Dynamic
	{
		$property = $this->askProperty(
			$io,
			$ignoredIds,
			$allowedDataTypes,
			DevicesEntities\Channels\Properties\Dynamic::class,
			$property,
		);

		if (!$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
			$io->error(
				$this->translator->translate(
					'//virtual-connector.cmd.devices.thermostat.messages.property.notSupported',
				),
			);

			return $this->askSensor($io, $ignoredIds, $allowedDataTypes, $property);
		}

		return $property;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askTargetTemperature(
		Style\SymfonyStyle $io,
		Virtual\Types\ThermostatMode $thermostatMode,
		Entities\Devices\Thermostat|null $device = null,
	): float
	{
		$question = new Console\Question\Question(
			$this->translator->translate(
				'//virtual-connector.cmd.devices.thermostat.questions.provide.targetTemperature.' . $thermostatMode->getValue(),
			),
			$device?->getTargetTemp($thermostatMode),
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

			if (strval(floatval($answer)) === $answer) {
				return floatval($answer);
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$targetTemperature = $io->askQuestion($question);
		assert(is_float($targetTemperature));

		return $targetTemperature;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
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

			if (strval(floatval($answer)) === $answer) {
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askHeatingThresholdTemperature(
		Style\SymfonyStyle $io,
		Virtual\Types\ThermostatMode $thermostatMode,
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

			if (strval(floatval($answer)) === $answer) {
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askCoolingThresholdTemperature(
		Style\SymfonyStyle $io,
		Virtual\Types\ThermostatMode $thermostatMode,
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

			if (strval(floatval($answer)) === $answer) {
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
				. ($device->getConnector()->getName() !== null ? ' [' . $device->getConnector()->getName() . ']' : '[' . $device->getConnector()->getIdentifier() . ']')
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
				' [' . ($property->getName() ?? DevicesHelpers\Name::createName($property->getIdentifier())) . ']',
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
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichDevice(
		Style\SymfonyStyle $io,
		Entities\VirtualConnector $connector,
	): Entities\Devices\Thermostat|null
	{
		$devices = [];

		$findDevicesQuery = new Queries\FindThermostatDevices();
		$findDevicesQuery->forConnector($connector);

		$connectorDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\Devices\Thermostat::class,
		);
		usort(
			$connectorDevices,
			static fn (Entities\Devices\Thermostat $a, Entities\Devices\Thermostat $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($connectorDevices as $device) {
			$devices[$device->getIdentifier()] = $device->getIdentifier()
				. ($device->getName() !== null ? ' [' . $device->getName() . ']' : '');
		}

		if (count($devices) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//virtual-connector.cmd.devices.thermostat.questions.select.device'),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connector, $devices): Entities\Devices\Thermostat {
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
					$findDeviceQuery = new Queries\FindThermostatDevices();
					$findDeviceQuery->byIdentifier($identifier);
					$findDeviceQuery->forConnector($connector);

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
						$this->translator->translate('//virtual-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\Devices\Thermostat);

		return $device;
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
