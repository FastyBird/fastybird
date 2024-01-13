<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           23.10.23
 */

namespace FastyBird\Addon\ThermostatDevice\Commands;

use Doctrine\DBAL;
use Doctrine\Persistence;
use Exception;
use FastyBird\Addon\ThermostatDevice;
use FastyBird\Addon\ThermostatDevice\Entities;
use FastyBird\Addon\ThermostatDevice\Exceptions;
use FastyBird\Addon\ThermostatDevice\Queries;
use FastyBird\Addon\ThermostatDevice\Types;
use FastyBird\Connector\Virtual\Entities as VirtualEntities;
use FastyBird\Connector\Virtual\Queries as VirtualQueries;
use FastyBird\Connector\Virtual\Types as VirtualTypes;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
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
use function array_flip;
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
 * @package        FastyBird:ThermostatDeviceAddon!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Install extends Console\Command\Command
{

	public const NAME = 'fb:thermostat-device-addon:devices:install';

	public function __construct(
		private readonly ThermostatDevice\Logger $logger,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStatesManager,
		private readonly BootstrapHelpers\Database $databaseHelper,
		private readonly Localization\Translator $translator,
		private readonly Persistence\ManagerRegistry $managerRegistry,
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
			->setDescription('Thermostat device installer');
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

		$io->title($this->translator->translate('//thermostat-device-addon.cmd.install.title'));

		$io->note($this->translator->translate('//thermostat-device-addon.cmd.install.subtitle'));

		$this->askInstallAction($io);

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
	private function createDevice(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->warning($this->translator->translate('//thermostat-device-addon.cmd.base.messages.noConnectors'));

			return;
		}

		$question = new Console\Question\Question(
			$this->translator->translate(
				'//thermostat-device-addon.cmd.install.questions.provide.device.identifier',
			),
		);

		$question->setValidator(function (string|null $answer) {
			if ($answer !== '' && $answer !== null) {
				$findDeviceQuery = new Queries\Entities\FindDevices();
				$findDeviceQuery->byIdentifier($answer);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\ThermostatDevice::class) !== null
				) {
					throw new Exceptions\Runtime(
						$this->translator->translate(
							'//thermostat-device-addon.cmd.install.messages.identifier.device.used',
						),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'virtual-thermostat-device-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findDeviceQuery = new Queries\Entities\FindDevices();
				$findDeviceQuery->byIdentifier($identifier);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\ThermostatDevice::class) === null
				) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error(
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.messages.identifier.device.missing',
				),
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
				'entity' => Entities\ThermostatDevice::class,
				'connector' => $connector,
				'identifier' => $identifier,
				'name' => $name,
			]));
			assert($device instanceof Entities\ThermostatDevice);

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => VirtualTypes\DevicePropertyIdentifier::MODEL,
				'device' => $device,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => Entities\ThermostatDevice::TYPE,
			]));

			$modes = $this->askThermostatModes($io);

			$unit = $this->askThermostatUnits($io);

			$configurationChannel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Channels\Configuration::class,
				'device' => $device,
				'identifier' => Types\ChannelIdentifier::CONFIGURATION,
			]));
			assert($configurationChannel instanceof Entities\Channels\Configuration);

			$hvacModeProperty = $this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::HVAC_MODE,
					'channel' => $configurationChannel,
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
					'channel' => $configurationChannel,
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

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Variable::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Types\ChannelPropertyIdentifier::UNIT,
					'channel' => $configurationChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
					'format' => [Types\Unit::CELSIUS, Types\Unit::FAHRENHEIT],
					'value' => $unit->getValue(),
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
						'//thermostat-device-addon.cmd.install.messages.configureHeaters',
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

					if ($heater !== false) {
						$heaters[] = $heater;

						$this->createOrUpdateProperty(
							DevicesEntities\Channels\Properties\Mapped::class,
							Utils\ArrayHash::from([
								'parent' => $heater,
								'entity' => DevicesEntities\Channels\Properties\Mapped::class,
								'identifier' => $this->findChannelPropertyIdentifier(
									$actorsChannel,
									Types\ChannelPropertyIdentifier::HEATER_ACTOR,
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
								'//thermostat-device-addon.cmd.install.questions.addAnotherHeater',
							),
							false,
						);

						$continue = (bool) $io->askQuestion($question);
					} else {
						$continue = false;
					}
				} while ($continue);
			}

			if (in_array(Types\HvacMode::COOL, $modes, true)) {
				$io->info(
					$this->translator->translate(
						'//thermostat-device-addon.cmd.install.messages.configureCoolers',
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

					if ($cooler !== false) {
						$coolers[] = $cooler;

						$this->createOrUpdateProperty(
							DevicesEntities\Channels\Properties\Mapped::class,
							Utils\ArrayHash::from([
								'parent' => $cooler,
								'entity' => DevicesEntities\Channels\Properties\Mapped::class,
								'identifier' => $this->findChannelPropertyIdentifier(
									$actorsChannel,
									Types\ChannelPropertyIdentifier::COOLER_ACTOR,
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
								'//thermostat-device-addon.cmd.install.questions.addAnotherCooler',
							),
							false,
						);

						$continue = (bool) $io->askQuestion($question);
					} else {
						$continue = false;
					}
				} while ($continue);
			}

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//thermostat-device-addon.cmd.install.questions.useOpenings'),
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

					if ($opening !== false) {
						$openings[] = $opening;

						$this->createOrUpdateProperty(
							DevicesEntities\Channels\Properties\Mapped::class,
							Utils\ArrayHash::from([
								'parent' => $opening,
								'entity' => DevicesEntities\Channels\Properties\Mapped::class,
								'identifier' => $this->findChannelPropertyIdentifier(
									$openingsChannel,
									Types\ChannelPropertyIdentifier::OPENING_SENSOR,
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
								'//thermostat-device-addon.cmd.install.questions.addAnotherOpening',
							),
							false,
						);

						$continue = (bool) $io->askQuestion($question);
					} else {
						$continue = false;
					}
				} while ($continue);
			}

			$io->info(
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.messages.configureTemperatureSensor',
				),
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

				if ($sensor !== false) {
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
							'//thermostat-device-addon.cmd.install.questions.addAnotherTemperatureSensor',
						),
						false,
					);

					$continue = (bool) $io->askQuestion($question);
				} else {
					$continue = false;
				}
			} while ($continue);

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//thermostat-device-addon.cmd.install.questions.useFloorSensor'),
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

					if ($sensor !== false) {
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
								'//thermostat-device-addon.cmd.install.questions.addAnotherFloorSensor',
							),
							false,
						);

						$continue = (bool) $io->askQuestion($question);
					} else {
						$continue = false;
					}
				} while ($continue);
			}

			$targetTemp = $this->askTargetTemperature(
				$io,
				Types\Preset::get(Types\Preset::MANUAL),
				$unit,
			);

			$targetTempProperty = $this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE,
					'channel' => $configurationChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\ThermostatDevice::PRECISION,
					'settable' => true,
					'queryable' => true,
				]),
			);

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Dynamic::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::ACTUAL_TEMPERATURE,
					'channel' => $configurationChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\ThermostatDevice::PRECISION,
					'settable' => false,
					'queryable' => true,
				]),
			);

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Variable::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Types\ChannelPropertyIdentifier::LOW_TARGET_TEMPERATURE_TOLERANCE,
					'channel' => $configurationChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => null,
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\ThermostatDevice::PRECISION,
					'default' => null,
					'value' => Entities\ThermostatDevice::COLD_TOLERANCE,
				]),
			);

			$this->createOrUpdateProperty(
				DevicesEntities\Channels\Properties\Variable::class,
				Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Types\ChannelPropertyIdentifier::HIGH_TARGET_TEMPERATURE_TOLERANCE,
					'channel' => $configurationChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					'format' => null,
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\ThermostatDevice::PRECISION,
					'default' => null,
					'value' => Entities\ThermostatDevice::HOT_TOLERANCE,
				]),
			);

			if ($useFloorSensor) {
				$maxFloorTemp = $this->askMaxFloorTemperature($io, $unit);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Variable::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => Types\ChannelPropertyIdentifier::MAXIMUM_FLOOR_TEMPERATURE,
						'channel' => $configurationChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [0, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\ThermostatDevice::PRECISION,
						'default' => null,
						'value' => $maxFloorTemp,
					]),
				);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Dynamic::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
						'identifier' => Types\ChannelPropertyIdentifier::ACTUAL_FLOOR_TEMPERATURE,
						'channel' => $configurationChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [0, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\ThermostatDevice::PRECISION,
						'settable' => false,
						'queryable' => true,
					]),
				);
			}

			if (in_array(Types\HvacMode::AUTO, $modes, true)) {
				$heatingThresholdTemp = $this->askHeatingThresholdTemperature(
					$io,
					Types\Preset::get(Types\Preset::MANUAL),
					$unit,
				);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Variable::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE,
						'channel' => $configurationChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\ThermostatDevice::PRECISION,
						'default' => null,
						'value' => $heatingThresholdTemp,
					]),
				);

				$coolingThresholdTemp = $this->askCoolingThresholdTemperature(
					$io,
					Types\Preset::get(Types\Preset::MANUAL),
					$unit,
				);

				$this->createOrUpdateProperty(
					DevicesEntities\Channels\Properties\Variable::class,
					Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE,
						'channel' => $configurationChannel,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\ThermostatDevice::PRECISION,
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
					'channel' => $configurationChannel,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
					'format' => array_merge(
						[Types\Preset::MANUAL],
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
						'//thermostat-device-addon.cmd.install.messages.preset.' . $preset,
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
						Types\Preset::get($preset),
						$unit,
					),
					'property' => $this->createOrUpdateProperty(
						DevicesEntities\Channels\Properties\Dynamic::class,
						Utils\ArrayHash::from([
							'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
							'identifier' => Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE,
							'channel' => $presetChannel,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
							'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
							'unit' => null,
							'invalid' => null,
							'scale' => null,
							'step' => Entities\ThermostatDevice::PRECISION,
							'settable' => true,
							'queryable' => true,
						]),
					),
				];

				if (in_array(Types\HvacMode::AUTO, $modes, true)) {
					$heatingThresholdTemp = $this->askHeatingThresholdTemperature(
						$io,
						Types\Preset::get($preset),
						$unit,
					);

					$this->createOrUpdateProperty(
						DevicesEntities\Channels\Properties\Variable::class,
						Utils\ArrayHash::from([
							'entity' => DevicesEntities\Channels\Properties\Variable::class,
							'identifier' => Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE,
							'channel' => $presetChannel,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
							'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
							'unit' => null,
							'invalid' => null,
							'scale' => null,
							'step' => Entities\ThermostatDevice::PRECISION,
							'default' => null,
							'value' => $heatingThresholdTemp,
						]),
					);

					$coolingThresholdTemp = $this->askCoolingThresholdTemperature(
						$io,
						Types\Preset::get($preset),
						$unit,
					);

					$this->createOrUpdateProperty(
						DevicesEntities\Channels\Properties\Variable::class,
						Utils\ArrayHash::from([
							'entity' => DevicesEntities\Channels\Properties\Variable::class,
							'identifier' => Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE,
							'channel' => $presetChannel,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
							'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
							'unit' => null,
							'invalid' => null,
							'scale' => null,
							'step' => Entities\ThermostatDevice::PRECISION,
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
					'//thermostat-device-addon.cmd.install.messages.create.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
					'type' => 'thermostat-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate('//thermostat-device-addon.cmd.install.messages.create.device.error'),
			);

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}

		$hvacConfiguration = $this->channelsPropertiesConfigurationRepository->find(
			$hvacModeProperty->getId(),
			MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
		);
		assert($hvacConfiguration !== null);

		$this->channelPropertiesStatesManager->setValue(
			$hvacConfiguration,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_FIELD => Types\HvacMode::OFF,
				DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
				DevicesStates\Property::VALID_FIELD => true,
				DevicesStates\Property::PENDING_FIELD => false,
			]),
		);

		$targetTempConfiguration = $this->channelsPropertiesConfigurationRepository->find(
			$targetTempProperty->getId(),
			MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
		);
		assert($targetTempConfiguration !== null);

		$this->channelPropertiesStatesManager->setValue(
			$targetTempConfiguration,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_FIELD => $targetTemp,
				DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
				DevicesStates\Property::VALID_FIELD => true,
				DevicesStates\Property::PENDING_FIELD => false,
			]),
		);

		$presetModeConfiguration = $this->channelsPropertiesConfigurationRepository->find(
			$presetModeProperty->getId(),
			MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
		);
		assert($presetModeConfiguration !== null);

		$this->channelPropertiesStatesManager->setValue(
			$presetModeConfiguration,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_FIELD => Types\Preset::MANUAL,
				DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
				DevicesStates\Property::VALID_FIELD => true,
				DevicesStates\Property::PENDING_FIELD => false,
			]),
		);

		foreach ($setPresets as $data) {
			$presetConfiguration = $this->channelsPropertiesConfigurationRepository->find(
				$data['property']->getId(),
				MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
			);
			assert($presetConfiguration !== null);

			$this->channelPropertiesStatesManager->setValue(
				$presetConfiguration,
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_FIELD => $data['value'],
					DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
					DevicesStates\Property::VALID_FIELD => true,
					DevicesStates\Property::PENDING_FIELD => false,
				]),
			);
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
	private function editDevice(Style\SymfonyStyle $io): void
	{
		$device = $this->askWhichDevice($io);

		if ($device === null) {
			$io->warning($this->translator->translate('//thermostat-device-addon.cmd.install.messages.noDevices'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//thermostat-device-addon.cmd.install.questions.create.device'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createDevice($io);
			}

			return;
		}

		$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(VirtualTypes\DevicePropertyIdentifier::MODEL);

		$deviceModelProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		$findChannelQuery = new Queries\Entities\FindConfigurationChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(Types\ChannelIdentifier::CONFIGURATION);

		$thermostatChannel = $this->channelsRepository->findOneBy(
			$findChannelQuery,
			Entities\Channels\Configuration::class,
		);

		$hvacModeProperty = $unitProperty = $hvacStateProperty = $presetModeProperty = null;
		$maxFloorTempProperty = $actualFloorTempProperty = $targetTempProperty = $actualTempProperty = null;
		$heatingThresholdTempProperty = $coolingThresholdTempProperty = null;

		if ($thermostatChannel !== null) {
			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::HVAC_MODE);

			$hvacModeProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::UNIT);

			$unitProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::HVAC_STATE);

			$hvacStateProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::MAXIMUM_FLOOR_TEMPERATURE);

			$maxFloorTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::ACTUAL_FLOOR_TEMPERATURE);

			$actualFloorTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE);

			$targetTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::ACTUAL_TEMPERATURE);

			$actualTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE);

			$heatingThresholdTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE);

			$coolingThresholdTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($thermostatChannel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::PRESET_MODE);

			$presetModeProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);
		}

		$name = $this->askDeviceName($io, $device);

		$modes = $this->askThermostatModes(
			$io,
			$hvacModeProperty instanceof DevicesEntities\Channels\Properties\Dynamic ? $hvacModeProperty : null,
		);

		$unit = $this->askThermostatUnits(
			$io,
			$unitProperty instanceof DevicesEntities\Channels\Properties\Variable ? $unitProperty : null,
		);

		$targetTemp = $this->askTargetTemperature(
			$io,
			Types\Preset::get(Types\Preset::MANUAL),
			$unit,
			$device,
		);

		$maxFloorTemp = null;

		if ($device->hasFloorSensors()) {
			$maxFloorTemp = $this->askMaxFloorTemperature($io, $unit, $device);
		}

		$heatingThresholdTemp = $coolingThresholdTemp = null;

		if (in_array(Types\HvacMode::AUTO, $modes, true)) {
			$heatingThresholdTemp = $this->askHeatingThresholdTemperature(
				$io,
				Types\Preset::get(Types\Preset::MANUAL),
				$unit,
				$device,
			);

			$coolingThresholdTemp = $this->askCoolingThresholdTemperature(
				$io,
				Types\Preset::get(Types\Preset::MANUAL),
				$unit,
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
			assert($device instanceof Entities\ThermostatDevice);

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
					'identifier' => VirtualTypes\DevicePropertyIdentifier::MODEL,
					'device' => $device,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => Entities\ThermostatDevice::TYPE,
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
					'value' => Entities\ThermostatDevice::TYPE,
				]));
			}

			if ($thermostatChannel === null) {
				$thermostatChannel = $this->channelsManager->create(Utils\ArrayHash::from([
					'entity' => Entities\Channels\Configuration::class,
					'device' => $device,
					'identifier' => Types\ChannelIdentifier::CONFIGURATION,
				]));
				assert($thermostatChannel instanceof Entities\Channels\Configuration);
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
					'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\ThermostatDevice::PRECISION,
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
					'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\ThermostatDevice::PRECISION,
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
						'format' => [0, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\ThermostatDevice::PRECISION,
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
						'format' => [0, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\ThermostatDevice::PRECISION,
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
						'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\ThermostatDevice::PRECISION,
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
						'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\ThermostatDevice::PRECISION,
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
						[Types\Preset::MANUAL],
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

			foreach (Types\Preset::getAvailableValues() as $preset) {
				if ($preset === Types\Preset::MANUAL) {
					continue;
				}

				$findPresetChannelQuery = new Queries\Entities\FindPresetChannels();
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
								Types\Preset::get($preset),
								$unit,
							),
							'property' => $this->createOrUpdateProperty(
								DevicesEntities\Channels\Properties\Dynamic::class,
								Utils\ArrayHash::from([
									'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
									'identifier' => Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE,
									'channel' => $presetChannel,
									'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
									'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
									'unit' => null,
									'invalid' => null,
									'scale' => null,
									'step' => Entities\ThermostatDevice::PRECISION,
									'settable' => true,
									'queryable' => true,
								]),
							),
						];

						if (in_array(Types\HvacMode::AUTO, $modes, true)) {
							$heatingThresholdTemp = $this->askHeatingThresholdTemperature(
								$io,
								Types\Preset::get($preset),
								$unit,
							);

							$this->createOrUpdateProperty(
								DevicesEntities\Channels\Properties\Variable::class,
								Utils\ArrayHash::from([
									'entity' => DevicesEntities\Channels\Properties\Variable::class,
									'identifier' => Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE,
									'channel' => $presetChannel,
									'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
									'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
									'unit' => null,
									'invalid' => null,
									'scale' => null,
									'step' => Entities\ThermostatDevice::PRECISION,
									'default' => null,
									'value' => $heatingThresholdTemp,
								]),
							);

							$coolingThresholdTemp = $this->askCoolingThresholdTemperature(
								$io,
								Types\Preset::get($preset),
								$unit,
							);

							$this->createOrUpdateProperty(
								DevicesEntities\Channels\Properties\Variable::class,
								Utils\ArrayHash::from([
									'entity' => DevicesEntities\Channels\Properties\Variable::class,
									'identifier' => Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE,
									'channel' => $presetChannel,
									'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
									'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
									'unit' => null,
									'invalid' => null,
									'scale' => null,
									'step' => Entities\ThermostatDevice::PRECISION,
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
					'//thermostat-device-addon.cmd.install.messages.update.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
					'type' => 'thermostat-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate('//thermostat-device-addon.cmd.install.messages.update.device.error'),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}

		assert($hvacModeProperty instanceof DevicesEntities\Channels\Properties\Dynamic);

		$hvacModeConfiguration = $this->channelsPropertiesConfigurationRepository->find(
			$hvacModeProperty->getId(),
			MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
		);
		assert($hvacModeConfiguration !== null);

		$this->channelPropertiesStatesManager->setValue(
			$hvacModeConfiguration,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_FIELD => Types\HvacMode::OFF,
				DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
				DevicesStates\Property::VALID_FIELD => true,
				DevicesStates\Property::PENDING_FIELD => false,
			]),
		);

		assert($targetTempProperty instanceof DevicesEntities\Channels\Properties\Dynamic);

		$targetTempConfiguration = $this->channelsPropertiesConfigurationRepository->find(
			$targetTempProperty->getId(),
			MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
		);
		assert($targetTempConfiguration !== null);

		$this->channelPropertiesStatesManager->setValue(
			$targetTempConfiguration,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_FIELD => $targetTemp,
				DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
				DevicesStates\Property::VALID_FIELD => true,
				DevicesStates\Property::PENDING_FIELD => false,
			]),
		);

		assert($presetModeProperty instanceof DevicesEntities\Channels\Properties\Dynamic);

		$presetModeConfiguration = $this->channelsPropertiesConfigurationRepository->find(
			$presetModeProperty->getId(),
			MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
		);
		assert($presetModeConfiguration !== null);

		$this->channelPropertiesStatesManager->setValue(
			$presetModeConfiguration,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_FIELD => Types\Preset::MANUAL,
				DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
				DevicesStates\Property::VALID_FIELD => true,
				DevicesStates\Property::PENDING_FIELD => false,
			]),
		);

		foreach ($setPresets as $data) {
			$presetConfiguration = $this->channelsPropertiesConfigurationRepository->find(
				$data['property']->getId(),
				MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
			);
			assert($presetConfiguration !== null);

			$this->channelPropertiesStatesManager->setValue(
				$presetConfiguration,
				Utils\ArrayHash::from([
					DevicesStates\Property::ACTUAL_VALUE_FIELD => $data['value'],
					DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
					DevicesStates\Property::VALID_FIELD => true,
					DevicesStates\Property::PENDING_FIELD => false,
				]),
			);
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function deleteDevice(Style\SymfonyStyle $io): void
	{
		$device = $this->askWhichDevice($io);

		if ($device === null) {
			$io->info($this->translator->translate('//thermostat-device-addon.cmd.install.messages.noDevices'));

			return;
		}

		$io->warning(
			$this->translator->translate(
				'//thermostat-device-addon.cmd.install.messages.remove.device.confirm',
				['name' => $device->getName() ?? $device->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//thermostat-device-addon.cmd.base.questions.continue'),
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
					'//thermostat-device-addon.cmd.install.messages.remove.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
					'type' => 'initialize-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate('//thermostat-device-addon.cmd.install.messages.remove.device.error'),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function manageDevice(Style\SymfonyStyle $io): void
	{
		$device = $this->askWhichDevice($io);

		if ($device === null) {
			$io->info($this->translator->translate('//thermostat-device-addon.cmd.install.messages.noDevices'));

			return;
		}

		$this->askManageDeviceAction($io, $device);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 */
	private function listDevices(Style\SymfonyStyle $io): void
	{
		$findDevicesQuery = new Queries\Entities\FindDevices();

		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\ThermostatDevice::class);
		usort(
			$devices,
			static fn (Entities\ThermostatDevice $a, Entities\ThermostatDevice $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//thermostat-device-addon.cmd.install.data.name'),
			$this->translator->translate('//thermostat-device-addon.cmd.install.data.hvacModes'),
			$this->translator->translate('//thermostat-device-addon.cmd.install.data.presets'),
			$this->translator->translate('//thermostat-device-addon.cmd.install.data.connector'),
		]);

		foreach ($devices as $index => $device) {
			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
				implode(', ', array_filter(
					array_map(function (string $item): string|null {
						if ($item === Types\HvacMode::OFF) {
							return null;
						}

						return $this->translator->translate(
							'//thermostat-device-addon.cmd.install.answers.mode.' . $item,
						);
					}, $device->getHvacModes()),
					static fn (string|null $item): bool => $item !== null,
				)),
				implode(', ', array_filter(
					array_map(function (string $item): string|null {
						if ($item === Types\Preset::MANUAL) {
							return null;
						}

						return $this->translator->translate(
							'//thermostat-device-addon.cmd.install.answers.preset.' . $item,
						);
					}, $device->getPresetModes()),
					static fn (string|null $item): bool => $item !== null,
				)),
				$device->getConnector()->getName() ?? $device->getConnector()->getIdentifier(),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws Exception
	 */
	private function createActor(Style\SymfonyStyle $io, Entities\ThermostatDevice $device): void
	{
		$findChannelQuery = new Queries\Entities\FindActorChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(Types\ChannelIdentifier::ACTORS);

		$actorsChannel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Actors::class);
		assert($actorsChannel instanceof Entities\Channels\Actors);

		$actorType = $this->askActorType($io, $device);

		$name = $this->askActorName($io);

		$heater = $this->askActor(
			$io,
			array_map(
				static fn (DevicesEntities\Channels\Properties\Dynamic $heater): string => $heater->getId()->toString(),
				array_filter(
					$device->getActors(),
					// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
					static fn (DevicesEntities\Channels\Properties\Property $actor): bool => $actor instanceof DevicesEntities\Channels\Properties\Dynamic,
				),
			),
			[
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SWITCH),
			],
		);

		if ($heater === false) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$property = $this->channelsPropertiesManager->create(Utils\ArrayHash::from([
				'parent' => $heater,
				'entity' => DevicesEntities\Channels\Properties\Mapped::class,
				'identifier' => $this->findChannelPropertyIdentifier(
					$actorsChannel,
					$actorType->getValue(),
				),
				'name' => $name,
				'channel' => $actorsChannel,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
				'format' => null,
				'unit' => null,
				'invalid' => null,
				'scale' => null,
				'step' => null,
				'settable' => true,
				'queryable' => true,
			]));
			assert($property instanceof DevicesEntities\Channels\Properties\Mapped);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.messages.create.actor.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
					'type' => 'thermostat-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate('//thermostat-device-addon.cmd.install.messages.create.actor.error'),
			);

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	private function editActor(Style\SymfonyStyle $io, Entities\ThermostatDevice $device): void
	{
		$property = $this->askWhichActor($io, $device);

		if ($property === null) {
			$io->warning($this->translator->translate('//thermostat-device-addon.cmd.install.messages.noActors'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//thermostat-device-addon.cmd.install.questions.create.actor'),
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

		$name = $this->askActorName($io, $property);

		$parent = $this->askActor(
			$io,
			[],
			[
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SWITCH),
			],
			$parent,
		);

		if ($parent === false) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$property = $this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
				'name' => $name,
				'parent' => $parent,
			]));
			assert($property instanceof DevicesEntities\Channels\Properties\Mapped);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.messages.update.actor.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
					'type' => 'thermostat-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate('//thermostat-device-addon.cmd.install.messages.update.actor.error'),
			);

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}
	}

	private function listActors(Style\SymfonyStyle $io, Entities\ThermostatDevice $device): void
	{
		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//thermostat-device-addon.cmd.install.data.name'),
			$this->translator->translate('//thermostat-device-addon.cmd.install.data.type'),
		]);

		$actors = $device->getActors();
		usort(
			$actors,
			static fn (DevicesEntities\Channels\Properties\Property $a, DevicesEntities\Channels\Properties\Property $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);
		$actors = array_filter(
			$actors,
			static fn (DevicesEntities\Channels\Properties\Property $property): bool =>
				Utils\Strings::startsWith(
					$property->getIdentifier(),
					Types\ChannelPropertyIdentifier::HEATER_ACTOR,
				)
				|| Utils\Strings::startsWith(
					$property->getIdentifier(),
					Types\ChannelPropertyIdentifier::COOLER_ACTOR,
				),
		);

		foreach ($actors as $index => $property) {
			$type = 'N/A';

			if (Utils\Strings::startsWith($property->getIdentifier(), Types\ChannelPropertyIdentifier::HEATER_ACTOR)) {
				$type = $this->translator->translate(
					'//thermostat-device-addon.cmd.install.data.' . Types\ChannelPropertyIdentifier::HEATER_ACTOR,
				);
			} elseif (Utils\Strings::startsWith(
				$property->getIdentifier(),
				Types\ChannelPropertyIdentifier::COOLER_ACTOR,
			)) {
				$type = $this->translator->translate(
					'//thermostat-device-addon.cmd.install.data.' . Types\ChannelPropertyIdentifier::COOLER_ACTOR,
				);
			}

			$table->addRow([
				$index + 1,
				$property->getName() ?? $property->getIdentifier(),
				$type,
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function deleteActor(Style\SymfonyStyle $io, Entities\ThermostatDevice $device): void
	{
		$property = $this->askWhichActor($io, $device);

		if ($property === null) {
			$io->warning($this->translator->translate('//thermostat-device-addon.cmd.install.messages.noActors'));

			return;
		}

		$io->warning(
			$this->translator->translate(
				'//thermostat-device-addon.cmd.install.messages.remove.actor.confirm',
				['name' => $property->getName() ?? $property->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//thermostat-device-addon.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->channelsPropertiesManager->delete($property);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.messages.remove.actor.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'thermostat-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->success(
				$this->translator->translate('//thermostat-device-addon.cmd.install.messages.remove.actor.error'),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws Exception
	 */
	private function createSensor(Style\SymfonyStyle $io, Entities\ThermostatDevice $device): void
	{
		$findChannelQuery = new Queries\Entities\FindSensorChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(Types\ChannelIdentifier::SENSORS);

		$sensorsChannel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Sensors::class);
		assert($sensorsChannel instanceof Entities\Channels\Sensors);

		$sensorType = $this->askSensorType($io);

		if ($sensorType->equalsValue(Types\ChannelPropertyIdentifier::TARGET_SENSOR)) {
			$dataTypes = [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SHORT),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_USHORT),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_INT),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
			];
			$dataType = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT);

		} elseif ($sensorType->equalsValue(Types\ChannelPropertyIdentifier::FLOOR_SENSOR)) {
			$dataTypes = [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SHORT),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_USHORT),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_INT),
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
			];
			$dataType = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT);

		} elseif ($sensorType->equalsValue(Types\ChannelPropertyIdentifier::OPENING_SENSOR)) {
			$dataTypes = [
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
			];
			$dataType = MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN);

		} else {
			// Log caught exception
			$this->logger->error(
				'Invalid sensor type selected',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'thermostat-cmd',
				],
			);

			$io->error(
				$this->translator->translate('//thermostat-device-addon.cmd.install.messages.create.sensor.error'),
			);

			return;
		}

		$name = $this->askSensorName($io);

		$sensor = $this->askSensor(
			$io,
			array_map(
				static fn (DevicesEntities\Channels\Properties\Dynamic $heater): string => $heater->getId()->toString(),
				array_filter(
					$device->getActors(),
					// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
					static fn (DevicesEntities\Channels\Properties\Property $actor): bool => $actor instanceof DevicesEntities\Channels\Properties\Dynamic,
				),
			),
			$dataTypes,
		);

		if ($sensor === false) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$property = $this->channelsPropertiesManager->create(Utils\ArrayHash::from([
				'parent' => $sensor,
				'entity' => DevicesEntities\Channels\Properties\Mapped::class,
				'identifier' => $this->findChannelPropertyIdentifier(
					$sensorsChannel,
					$sensorType->getValue(),
				),
				'name' => $name,
				'channel' => $sensorsChannel,
				'dataType' => $dataType,
				'format' => null,
				'unit' => null,
				'invalid' => null,
				'scale' => null,
				'step' => null,
				'settable' => false,
				'queryable' => true,
			]));
			assert($property instanceof DevicesEntities\Channels\Properties\Mapped);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.messages.create.sensor.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
					'type' => 'thermostat-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate('//thermostat-device-addon.cmd.install.messages.create.sensor.error'),
			);

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	private function editSensor(Style\SymfonyStyle $io, Entities\ThermostatDevice $device): void
	{
		$property = $this->askWhichSensor($io, $device);

		if ($property === null) {
			$io->warning($this->translator->translate('//thermostat-device-addon.cmd.install.messages.noSensors'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//thermostat-device-addon.cmd.install.questions.create.sensor'),
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

		$name = $this->askSensorName($io, $property);

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

		if ($parent === false) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$property = $this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
				'parent' => $parent,
				'name' => $name,
			]));
			assert($property instanceof DevicesEntities\Channels\Properties\Mapped);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.messages.update.sensor.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
					'type' => 'thermostat-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate('//thermostat-device-addon.cmd.install.messages.update.sensor.error'),
			);

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}
	}

	private function listSensors(Style\SymfonyStyle $io, Entities\ThermostatDevice $device): void
	{
		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//thermostat-device-addon.cmd.install.data.name'),
			$this->translator->translate('//thermostat-device-addon.cmd.install.data.type'),
		]);

		$sensors = $device->getSensors();
		usort(
			$sensors,
			static fn (DevicesEntities\Channels\Properties\Property $a, DevicesEntities\Channels\Properties\Property $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);
		$sensors = array_filter(
			$sensors,
			static fn (DevicesEntities\Channels\Properties\Property $property): bool =>
				Utils\Strings::startsWith(
					$property->getIdentifier(),
					Types\ChannelPropertyIdentifier::TARGET_SENSOR,
				)
				|| Utils\Strings::startsWith(
					$property->getIdentifier(),
					Types\ChannelPropertyIdentifier::FLOOR_SENSOR,
				)
				|| Utils\Strings::startsWith(
					$property->getIdentifier(),
					Types\ChannelPropertyIdentifier::OPENING_SENSOR,
				),
		);

		foreach ($sensors as $index => $property) {
			$type = 'N/A';

			if (Utils\Strings::startsWith(
				$property->getIdentifier(),
				Types\ChannelPropertyIdentifier::TARGET_SENSOR,
			)) {
				$type = $this->translator->translate(
					'//thermostat-device-addon.cmd.install.data.' . Types\ChannelPropertyIdentifier::TARGET_SENSOR,
				);
			} elseif (Utils\Strings::startsWith(
				$property->getIdentifier(),
				Types\ChannelPropertyIdentifier::FLOOR_SENSOR,
			)) {
				$type = $this->translator->translate(
					'//thermostat-device-addon.cmd.install.data.' . Types\ChannelPropertyIdentifier::FLOOR_SENSOR,
				);
			} elseif (Utils\Strings::startsWith(
				$property->getIdentifier(),
				Types\ChannelPropertyIdentifier::OPENING_SENSOR,
			)) {
				$type = $this->translator->translate(
					'//thermostat-device-addon.cmd.install.data.' . Types\ChannelPropertyIdentifier::OPENING_SENSOR,
				);
			}

			$table->addRow([
				$index + 1,
				$property->getName() ?? $property->getIdentifier(),
				$type,
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function deleteSensor(Style\SymfonyStyle $io, Entities\ThermostatDevice $device): void
	{
		$property = $this->askWhichSensor($io, $device);

		if ($property === null) {
			$io->warning($this->translator->translate('//thermostat-device-addon.cmd.install.messages.noSensors'));

			return;
		}

		$io->warning(
			$this->translator->translate(
				'//thermostat-device-addon.cmd.install.messages.remove.sensor.confirm',
				['name' => $property->getName() ?? $property->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//thermostat-device-addon.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->channelsPropertiesManager->delete($property);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.messages.remove.sensor.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'thermostat-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->success(
				$this->translator->translate('//thermostat-device-addon.cmd.install.messages.remove.sensor.error'),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function editPreset(Style\SymfonyStyle $io, Entities\ThermostatDevice $device): void
	{
		$preset = $this->askWhichPreset($io, $device);

		if ($preset === null) {
			$io->warning($this->translator->translate('//thermostat-device-addon.cmd.install.messages.noPresets'));

			return;
		}

		$findChannelQuery = new Queries\Entities\FindConfigurationChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(Types\ChannelIdentifier::CONFIGURATION);

		$configuration = $this->channelsRepository->findOneBy(
			$findChannelQuery,
			Entities\Channels\Configuration::class,
		);
		assert($configuration instanceof Entities\Channels\Configuration);

		$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelVariableProperties();
		$findChannelPropertyQuery->forChannel($configuration);
		$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::UNIT);

		$unitProperty = $this->channelsPropertiesRepository->findOneBy(
			$findChannelPropertyQuery,
			DevicesEntities\Channels\Properties\Variable::class,
		);
		assert($unitProperty instanceof DevicesEntities\Channels\Properties\Variable);

		$findChannelQuery = new Queries\Entities\FindPresetChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->endWithIdentifier($preset->getValue());

		$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Preset::class);

		$targetTempProperty = $heatingThresholdTempProperty = $coolingThresholdTempProperty = null;

		if ($channel !== null) {
			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::TARGET_TEMPERATURE);

			$targetTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::HEATING_THRESHOLD_TEMPERATURE);

			$heatingThresholdTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::COOLING_THRESHOLD_TEMPERATURE);

			$coolingThresholdTempProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);
		}

		$targetTemp = $this->askTargetTemperature($io, $preset, Types\Unit::get($unitProperty->getValue()), $device);

		$heatingThresholdTemp = $coolingThresholdTemp = null;

		if (in_array(Types\HvacMode::AUTO, $device->getHvacModes(), true)) {
			$heatingThresholdTemp = $this->askHeatingThresholdTemperature(
				$io,
				$preset,
				Types\Unit::get($unitProperty->getValue()),
				$device,
			);

			$coolingThresholdTemp = $this->askCoolingThresholdTemperature(
				$io,
				$preset,
				Types\Unit::get($unitProperty->getValue()),
				$device,
			);
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
					'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
					'unit' => null,
					'invalid' => null,
					'scale' => null,
					'step' => Entities\ThermostatDevice::PRECISION,
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
						'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\ThermostatDevice::PRECISION,
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
						'format' => [Entities\ThermostatDevice::MINIMUM_TEMPERATURE, Entities\ThermostatDevice::MAXIMUM_TEMPERATURE],
						'unit' => null,
						'invalid' => null,
						'scale' => null,
						'step' => Entities\ThermostatDevice::PRECISION,
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
					'//thermostat-device-addon.cmd.install.messages.update.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIRTUAL,
					'type' => 'thermostat-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate('//thermostat-device-addon.cmd.install.messages.update.device.error'),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}

		assert($targetTempProperty instanceof DevicesEntities\Channels\Properties\Dynamic);

		$targetTempConfiguration = $this->channelsPropertiesConfigurationRepository->find(
			$targetTempProperty->getId(),
			MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
		);
		assert($targetTempConfiguration !== null);

		$this->channelPropertiesStatesManager->setValue(
			$targetTempConfiguration,
			Utils\ArrayHash::from([
				DevicesStates\Property::ACTUAL_VALUE_FIELD => $targetTemp,
				DevicesStates\Property::EXPECTED_VALUE_FIELD => null,
				DevicesStates\Property::VALID_FIELD => true,
				DevicesStates\Property::PENDING_FIELD => false,
			]),
		);
	}

	private function askDeviceName(Style\SymfonyStyle $io, Entities\ThermostatDevice|null $device = null): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//thermostat-device-addon.cmd.install.questions.provide.device.name'),
			$device?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
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
			$this->translator->translate('//thermostat-device-addon.cmd.install.questions.select.mode'),
			[
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.answers.mode.' . Types\HvacMode::HEAT,
				),
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.answers.mode.' . Types\HvacMode::COOL,
				),
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.answers.mode.' . Types\HvacMode::AUTO,
				),
			],
			implode(',', $default),
		);
		$question->setMultiselect(true);
		$question->setErrorMessage(
			$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer): array {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			$modes = [];

			foreach (explode(',', strval($answer)) as $item) {
				if (
					$item === $this->translator->translate(
						'//thermostat-device-addon.cmd.install.answers.mode.' . Types\HvacMode::HEAT,
					)
					|| $item === '0'
				) {
					$modes[] = Types\HvacMode::HEAT;
				}

				if (
					$item === $this->translator->translate(
						'//thermostat-device-addon.cmd.install.answers.mode.' . Types\HvacMode::COOL,
					)
					|| $item === '1'
				) {
					$modes[] = Types\HvacMode::COOL;
				}

				if (
					$item === $this->translator->translate(
						'//thermostat-device-addon.cmd.install.answers.mode.' . Types\HvacMode::AUTO,
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
					$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
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
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askThermostatUnits(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Properties\Variable|null $property = null,
	): Types\Unit
	{
		if (
			$property !== null
			&& (
				$property->getIdentifier() !== Types\ChannelPropertyIdentifier::UNIT
				|| !$property->getFormat() instanceof MetadataValueObjects\StringEnumFormat
			)
		) {
			throw new Exceptions\InvalidArgument('Provided property is not valid');
		}

		$default = match ($property?->getValue() ?? Types\Unit::CELSIUS) {
			Types\Unit::CELSIUS => 0,
			Types\Unit::FAHRENHEIT => 1,
			default => 0,
		};

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//thermostat-device-addon.cmd.install.questions.provide.device.unit'),
			[
				0 => $this->translator->translate('//thermostat-device-addon.cmd.install.answers.unit.celsius'),
				1 => $this->translator->translate('//thermostat-device-addon.cmd.install.answers.unit.fahrenheit'),
			],
			$default,
		);

		$question->setErrorMessage(
			$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer): Types\Unit {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (
				$answer === $this->translator->translate(
					'//thermostat-device-addon.cmd.install.answers.unit.celsius',
				)
				|| $answer === '0'
			) {
				return Types\Unit::get(Types\Unit::CELSIUS);
			}

			if (
				$answer === $this->translator->translate(
					'//thermostat-device-addon.cmd.install.answers.unit.fahrenheit',
				)
				|| $answer === '1'
			) {
				return Types\Unit::get(Types\Unit::FAHRENHEIT);
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\Unit);

		return $answer;
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
				Types\Preset::AWAY => 0,
				Types\Preset::ECO => 1,
				Types\Preset::HOME => 2,
				Types\Preset::COMFORT => 3,
				Types\Preset::SLEEP => 4,
				Types\Preset::ANTI_FREEZE => 5,
				default => null,
			}, $format?->toArray() ?? [
				Types\Preset::AWAY,
				Types\Preset::ECO,
				Types\Preset::HOME,
				Types\Preset::COMFORT,
				Types\Preset::SLEEP,
				Types\Preset::ANTI_FREEZE,
			])),
			static fn (int|null $item): bool => $item !== null,
		);

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//thermostat-device-addon.cmd.install.questions.select.preset'),
			[
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.answers.preset.' . Types\Preset::AWAY,
				),
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.answers.preset.' . Types\Preset::ECO,
				),
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.answers.preset.' . Types\Preset::HOME,
				),
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.answers.preset.' . Types\Preset::COMFORT,
				),
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.answers.preset.' . Types\Preset::SLEEP,
				),
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.answers.preset.' . Types\Preset::ANTI_FREEZE,
				),
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.answers.preset.none',
				),
			],
			$default !== [] ? implode(',', $default) : '6',
		);
		$question->setMultiselect(true);
		$question->setErrorMessage(
			$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer): array {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			$presets = [];

			foreach (explode(',', strval($answer)) as $item) {
				if (
					$item === $this->translator->translate(
						'//thermostat-device-addon.cmd.install.answers.preset.' . Types\Preset::AWAY,
					)
					|| $item === '0'
				) {
					$presets[] = Types\Preset::AWAY;
				}

				if (
					$item === $this->translator->translate(
						'//thermostat-device-addon.cmd.install.answers.preset.' . Types\Preset::ECO,
					)
					|| $item === '1'
				) {
					$presets[] = Types\Preset::ECO;
				}

				if (
					$item === $this->translator->translate(
						'//thermostat-device-addon.cmd.install.answers.preset.' . Types\Preset::HOME,
					)
					|| $item === '2'
				) {
					$presets[] = Types\Preset::HOME;
				}

				if (
					$item === $this->translator->translate(
						'//thermostat-device-addon.cmd.install.answers.preset.' . Types\Preset::COMFORT,
					)
					|| $item === '3'
				) {
					$presets[] = Types\Preset::COMFORT;
				}

				if (
					$item === $this->translator->translate(
						'//thermostat-device-addon.cmd.install.answers.preset.' . Types\Preset::SLEEP,
					)
					|| $item === '4'
				) {
					$presets[] = Types\Preset::SLEEP;
				}

				if (
					$item === $this->translator->translate(
						'//thermostat-device-addon.cmd.install.answers.preset.' . Types\Preset::ANTI_FREEZE,
					)
					|| $item === '5'
				) {
					$presets[] = Types\Preset::ANTI_FREEZE;
				}

				if (
					$item === $this->translator->translate(
						'//thermostat-device-addon.cmd.install.answers.preset.none',
					)
					|| $item === '6'
				) {
					return [];
				}
			}

			if ($presets !== []) {
				return $presets;
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$presets = $io->askQuestion($question);
		assert(is_array($presets));

		return $presets;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 */
	private function askActorType(
		Style\SymfonyStyle $io,
		Entities\ThermostatDevice $device,
	): Types\ChannelPropertyIdentifier
	{
		$types = [];

		if (in_array(Types\HvacMode::HEAT, $device->getHvacModes(), true)) {
			$types[Types\ChannelPropertyIdentifier::HEATER_ACTOR] = $this->translator->translate(
				'//thermostat-device-addon.cmd.install.answers.actor.heater',
			);
		}

		if (in_array(Types\HvacMode::COOL, $device->getHvacModes(), true)) {
			$types[Types\ChannelPropertyIdentifier::COOLER_ACTOR] = $this->translator->translate(
				'//thermostat-device-addon.cmd.install.answers.actor.cooler',
			);
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//thermostat-device-addon.cmd.install.questions.select.actorType'),
			array_values($types),
		);
		$question->setErrorMessage(
			$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($types): Types\ChannelPropertyIdentifier {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($types))) {
					$answer = array_values($types)[$answer];
				}

				$type = array_search($answer, $types, true);

				if ($type !== false && Types\ChannelPropertyIdentifier::isValidValue($type)) {
					return Types\ChannelPropertyIdentifier::get($type);
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$type = $io->askQuestion($question);
		assert($type instanceof Types\ChannelPropertyIdentifier);

		return $type;
	}

	private function askActorName(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Properties\Property|null $property = null,
	): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//thermostat-device-addon.cmd.install.questions.provide.actor.name'),
			$property?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
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
	): DevicesEntities\Channels\Properties\Dynamic|false
	{
		$parent = $this->askProperty(
			$io,
			$ignoredIds,
			$allowedDataTypes,
			DevicesEntities\Channels\Properties\Dynamic::class,
			$property,
			true,
		);

		if ($parent === null) {
			return false;
		}

		if (!$parent instanceof DevicesEntities\Channels\Properties\Dynamic) {
			$io->error(
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.messages.property.notSupported',
				),
			);

			return $this->askActor($io, $ignoredIds, $allowedDataTypes, $property);
		}

		return $parent;
	}

	private function askSensorType(
		Style\SymfonyStyle $io,
	): Types\ChannelPropertyIdentifier
	{
		$types = [
			Types\ChannelPropertyIdentifier::TARGET_SENSOR => $this->translator->translate(
				'//thermostat-device-addon.cmd.install.answers.sensor.targetTemperature',
			),
			Types\ChannelPropertyIdentifier::FLOOR_SENSOR => $this->translator->translate(
				'//thermostat-device-addon.cmd.install.answers.sensor.floorTemperature',
			),
			Types\ChannelPropertyIdentifier::OPENING_SENSOR => $this->translator->translate(
				'//thermostat-device-addon.cmd.install.answers.sensor.sensor',
			),
		];

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//thermostat-device-addon.cmd.install.questions.select.sensorType'),
			array_values($types),
		);
		$question->setErrorMessage(
			$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($types): Types\ChannelPropertyIdentifier {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($types))) {
					$answer = array_values($types)[$answer];
				}

				$type = array_search($answer, $types, true);

				if ($type !== false && Types\ChannelPropertyIdentifier::isValidValue($type)) {
					return Types\ChannelPropertyIdentifier::get($type);
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$type = $io->askQuestion($question);
		assert($type instanceof Types\ChannelPropertyIdentifier);

		return $type;
	}

	private function askSensorName(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Properties\Property|null $property = null,
	): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//thermostat-device-addon.cmd.install.questions.provide.sensor.name'),
			$property?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
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
	): DevicesEntities\Channels\Properties\Dynamic|false
	{
		$parent = $this->askProperty(
			$io,
			$ignoredIds,
			$allowedDataTypes,
			DevicesEntities\Channels\Properties\Dynamic::class,
			$property,
			null,
			true,
		);

		if ($parent === null) {
			return false;
		}

		if (!$parent instanceof DevicesEntities\Channels\Properties\Dynamic) {
			$io->error(
				$this->translator->translate(
					'//thermostat-device-addon.cmd.install.messages.property.notSupported',
				),
			);

			return $this->askSensor($io, $ignoredIds, $allowedDataTypes, $property);
		}

		return $parent;
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function askTargetTemperature(
		Style\SymfonyStyle $io,
		Types\Preset $thermostatMode,
		Types\Unit $unit,
		Entities\ThermostatDevice|null $device = null,
	): float
	{
		try {
			$property = $device?->getTargetTemp($thermostatMode);
		} catch (Exceptions\InvalidState) {
			$property = null;
		}

		$targetTemp = null;

		if ($property !== null) {
			$propertyConfiguration = $this->channelsPropertiesConfigurationRepository->find(
				$property->getId(),
				MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
			);
			assert($propertyConfiguration !== null);

			$state = $this->channelPropertiesStatesManager->readValue($propertyConfiguration);

			$targetTemp = $state?->getActualValue();
			assert(is_numeric($targetTemp) || $targetTemp === null);
		}

		$question = new Console\Question\Question(
			$this->translator->translate(
				'//thermostat-device-addon.cmd.install.questions.provide.targetTemperature.' . $thermostatMode->getValue(),
				['unit' => $unit->getValue()],
			),
			$targetTemp,
		);
		$question->setValidator(function (string|int|null $answer): float {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (strval(floatval($answer)) === strval($answer)) {
				return floatval($answer);
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
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
		Types\Unit $unit,
		Entities\ThermostatDevice|null $device = null,
	): float
	{
		$question = new Console\Question\Question(
			$this->translator->translate(
				'//thermostat-device-addon.cmd.install.questions.provide.maximumFloorTemperature',
				['unit' => $unit->getValue()],
			),
			$device?->getMaximumFloorTemp() ?? Entities\ThermostatDevice::MAXIMUM_FLOOR_TEMPERATURE,
		);
		$question->setValidator(function (string|int|null $answer): float {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (strval(floatval($answer)) === strval($answer)) {
				return floatval($answer);
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
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
		Types\Preset $thermostatMode,
		Types\Unit $unit,
		Entities\ThermostatDevice|null $device = null,
	): float
	{
		$question = new Console\Question\Question(
			$this->translator->translate(
				'//thermostat-device-addon.cmd.install.questions.provide.heatingThresholdTemperature',
				['unit' => $unit->getValue()],
			),
			$device?->getHeatingThresholdTemp($thermostatMode),
		);
		$question->setValidator(function (string|int|null $answer): float {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (strval(floatval($answer)) === strval($answer)) {
				return floatval($answer);
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
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
		Types\Preset $thermostatMode,
		Types\Unit $unit,
		Entities\ThermostatDevice|null $device = null,
	): float
	{
		$question = new Console\Question\Question(
			$this->translator->translate(
				'//thermostat-device-addon.cmd.install.questions.provide.coolingThresholdTemperature',
				['unit' => $unit->getValue()],
			),
			$device?->getCoolingThresholdTemp($thermostatMode),
		);
		$question->setValidator(function (string|int|null $answer): float {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (strval(floatval($answer)) === strval($answer)) {
				return floatval($answer);
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
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
		bool|null $settable = null,
		bool|null $queryable = null,
	): DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Variable|null
	{
		$devices = [];

		$connectedChannel = $connectedProperty?->getChannel();
		$connectedDevice = $connectedProperty?->getChannel()->getDevice();

		$findDevicesQuery = new DevicesQueries\Entities\FindDevices();

		$systemDevices = $this->devicesRepository->findAllBy($findDevicesQuery);
		usort(
			$systemDevices,
			static fn (DevicesEntities\Devices\Device $a, DevicesEntities\Devices\Device $b): int => (
				(
					($a->getConnector()->getName() ?? $a->getConnector()->getIdentifier())
					<=> ($b->getConnector()->getName() ?? $b->getConnector()->getIdentifier())
				) * 100 +
				(($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier()))
			)
		);

		foreach ($systemDevices as $device) {
			if ($device instanceof Entities\ThermostatDevice) {
				continue;
			}

			$findChannelsQuery = new DevicesQueries\Entities\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

			$hasProperty = false;

			foreach ($channels as $channel) {
				if ($onlyType === null || $onlyType === DevicesEntities\Channels\Properties\Dynamic::class) {
					$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					if ($settable === true) {
						$findChannelPropertiesQuery->settable(true);
					}

					if ($queryable === true) {
						$findChannelPropertiesQuery->queryable(true);
					}

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
					$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelVariableProperties();
					$findChannelPropertiesQuery->forChannel($channel);

					if ($settable === true) {
						$findChannelPropertiesQuery->settable(true);
					}

					if ($queryable === true) {
						$findChannelPropertiesQuery->queryable(true);
					}

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

			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			$devices[$device->getId()->toString()] = '[' . ($device->getConnector()->getName() ?? $device->getConnector()->getIdentifier()) . '] '
				. ($device->getName() ?? $device->getIdentifier());
		}

		if (count($devices) === 0) {
			$io->warning(
				$this->translator->translate('//thermostat-device-addon.cmd.install.messages.noHardwareDevices'),
			);

			return null;
		}

		$default = count($devices) === 1 ? 0 : null;

		if ($connectedDevice !== null) {
			foreach (array_values(array_flip($devices)) as $index => $value) {
				if ($value === $connectedDevice->getId()->toString()) {
					$default = $index;

					break;
				}
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//thermostat-device-addon.cmd.install.questions.select.mappedDevice'),
			array_values($devices),
			$default,
		);
		$question->setErrorMessage(
			$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($devices): DevicesEntities\Devices\Device {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($devices))) {
				$answer = array_values($devices)[$answer];
			}

			$identifier = array_search($answer, $devices, true);

			if ($identifier !== false) {
				$device = $this->devicesRepository->find(Uuid\Uuid::fromString($identifier));

				if ($device !== null) {
					return $device;
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$device = $io->askQuestion($question);
		assert($device instanceof DevicesEntities\Devices\Device);

		$channels = [];

		$findChannelsQuery = new DevicesQueries\Entities\FindChannels();
		$findChannelsQuery->forDevice($device);
		$findChannelsQuery->withProperties();

		$deviceChannels = $this->channelsRepository->findAllBy($findChannelsQuery);
		usort(
			$deviceChannels,
			static fn (DevicesEntities\Channels\Channel $a, DevicesEntities\Channels\Channel $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($deviceChannels as $channel) {
			$hasProperty = false;

			if ($onlyType === null || $onlyType === DevicesEntities\Channels\Properties\Dynamic::class) {
				$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
				$findChannelPropertiesQuery->forChannel($channel);

				if ($settable === true) {
					$findChannelPropertiesQuery->settable(true);
				}

				if ($queryable === true) {
					$findChannelPropertiesQuery->queryable(true);
				}

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
				$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelVariableProperties();
				$findChannelPropertiesQuery->forChannel($channel);

				if ($settable === true) {
					$findChannelPropertiesQuery->settable(true);
				}

				if ($queryable === true) {
					$findChannelPropertiesQuery->queryable(true);
				}

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

			$channels[$channel->getId()->toString()] = $channel->getName() ?? $channel->getIdentifier();
		}

		$default = count($channels) === 1 ? 0 : null;

		if ($connectedChannel !== null) {
			foreach (array_values(array_flip($channels)) as $index => $value) {
				if ($value === $connectedChannel->getId()->toString()) {
					$default = $index;

					break;
				}
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate(
				'//thermostat-device-addon.cmd.install.questions.select.mappedDeviceChannel',
			),
			array_values($channels),
			$default,
		);
		$question->setErrorMessage(
			$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|null $answer) use ($channels): DevicesEntities\Channels\Channel {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($channels))) {
					$answer = array_values($channels)[$answer];
				}

				$identifier = array_search($answer, $channels, true);

				if ($identifier !== false) {
					$channel = $this->channelsRepository->find(Uuid\Uuid::fromString($identifier));

					if ($channel !== null) {
						return $channel;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$channel = $io->askQuestion($question);
		assert($channel instanceof DevicesEntities\Channels\Channel);

		$properties = [];

		$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		if ($settable === true) {
			$findChannelPropertiesQuery->settable(true);
		}

		if ($queryable === true) {
			$findChannelPropertiesQuery->queryable(true);
		}

		$channelProperties = $this->channelsPropertiesRepository->findAllBy($findChannelPropertiesQuery);
		usort(
			$channelProperties,
			static fn (DevicesEntities\Channels\Properties\Property $a, DevicesEntities\Channels\Properties\Property $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
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

			$properties[$property->getId()->toString()] = $property->getName() ?? $property->getIdentifier();
		}

		$default = count($properties) === 1 ? 0 : null;

		if ($connectedProperty !== null) {
			foreach (array_values(array_flip($properties)) as $index => $value) {
				if ($value === $connectedProperty->getId()->toString()) {
					$default = $index;

					break;
				}
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate(
				'//thermostat-device-addon.cmd.install.questions.select.mappedChannelProperty',
			),
			array_values($properties),
			$default,
		);
		$question->setErrorMessage(
			$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			function (string|null $answer) use ($properties): DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Variable {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($properties))) {
					$answer = array_values($properties)[$answer];
				}

				$identifier = array_search($answer, $properties, true);

				if ($identifier !== false) {
					$property = $this->channelsPropertiesRepository->find(Uuid\Uuid::fromString($identifier));

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
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
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
	 * @throws BootstrapExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askInstallAction(Style\SymfonyStyle $io): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//thermostat-device-addon.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.create.device'),
				1 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.update.device'),
				2 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.remove.device'),
				3 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.manage.device'),
				4 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.list.devices'),
				5 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.nothing'),
			],
			5,
		);

		$question->setErrorMessage(
			$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//thermostat-device-addon.cmd.install.actions.create.device',
			)
			|| $whatToDo === '0'
		) {
			$this->createDevice($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//thermostat-device-addon.cmd.install.actions.update.device',
			)
			|| $whatToDo === '1'
		) {
			$this->editDevice($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//thermostat-device-addon.cmd.install.actions.remove.device',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteDevice($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//thermostat-device-addon.cmd.install.actions.manage.device',
			)
			|| $whatToDo === '3'
		) {
			$this->manageDevice($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//thermostat-device-addon.cmd.install.actions.list.devices',
			)
			|| $whatToDo === '4'
		) {
			$this->listDevices($io);

			$this->askInstallAction($io);
		}
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askManageDeviceAction(
		Style\SymfonyStyle $io,
		Entities\ThermostatDevice $device,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//thermostat-device-addon.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.create.actor'),
				1 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.update.actor'),
				2 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.list.actors'),
				3 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.remove.actor'),
				4 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.create.sensor'),
				5 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.update.sensor'),
				6 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.list.sensors'),
				7 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.remove.sensor'),
				8 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.update.preset'),
				9 => $this->translator->translate('//thermostat-device-addon.cmd.install.actions.nothing'),
			],
			9,
		);

		$question->setErrorMessage(
			$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//thermostat-device-addon.cmd.install.actions.create.actor',
			)
			|| $whatToDo === '0'
		) {
			$this->createActor($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//thermostat-device-addon.cmd.install.actions.update.actor',
			)
			|| $whatToDo === '1'
		) {
			$this->editActor($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//thermostat-device-addon.cmd.install.actions.list.actors',
			)
			|| $whatToDo === '2'
		) {
			$this->listActors($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//thermostat-device-addon.cmd.install.actions.remove.actor',
			)
			|| $whatToDo === '3'
		) {
			$this->deleteActor($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//thermostat-device-addon.cmd.install.actions.create.sensor',
			)
			|| $whatToDo === '4'
		) {
			$this->createSensor($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//thermostat-device-addon.cmd.install.actions.update.sensor',
			)
			|| $whatToDo === '5'
		) {
			$this->editSensor($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//thermostat-device-addon.cmd.install.actions.list.sensors',
			)
			|| $whatToDo === '6'
		) {
			$this->listSensors($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//thermostat-device-addon.cmd.install.actions.remove.sensor',
			)
			|| $whatToDo === '7'
		) {
			$this->deleteSensor($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//thermostat-device-addon.cmd.install.actions.update.preset',
			)
			|| $whatToDo === '8'
		) {
			$this->editPreset($io, $device);

			$this->askManageDeviceAction($io, $device);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): VirtualEntities\VirtualConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new VirtualQueries\Entities\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			VirtualEntities\VirtualConnector::class,
		);
		usort(
			$systemConnectors,
			static fn (VirtualEntities\VirtualConnector $a, VirtualEntities\VirtualConnector $b): int => (
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
			$this->translator->translate('//thermostat-device-addon.cmd.install.questions.select.item.connector'),
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connectors): VirtualEntities\VirtualConnector {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($connectors))) {
					$answer = array_values($connectors)[$answer];
				}

				$identifier = array_search($answer, $connectors, true);

				if ($identifier !== false) {
					$findConnectorQuery = new VirtualQueries\Entities\FindConnectors();
					$findConnectorQuery->byIdentifier($identifier);

					$connector = $this->connectorsRepository->findOneBy(
						$findConnectorQuery,
						VirtualEntities\VirtualConnector::class,
					);

					if ($connector !== null) {
						return $connector;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$connector = $io->askQuestion($question);
		assert($connector instanceof VirtualEntities\VirtualConnector);

		return $connector;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichDevice(
		Style\SymfonyStyle $io,
	): Entities\ThermostatDevice|null
	{
		$devices = [];

		$findDevicesQuery = new Queries\Entities\FindDevices();

		$connectorDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\ThermostatDevice::class,
		);
		usort(
			$connectorDevices,
			static fn (Entities\ThermostatDevice $a, Entities\ThermostatDevice $b): int => (
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
			$this->translator->translate('//thermostat-device-addon.cmd.install.questions.select.item.device'),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($devices): Entities\ThermostatDevice {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
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
						Entities\ThermostatDevice::class,
					);

					if ($device !== null) {
						return $device;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\ThermostatDevice);

		return $device;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 */
	private function askWhichPreset(
		Style\SymfonyStyle $io,
		Entities\ThermostatDevice $device,
	): Types\Preset|null
	{
		$allowedValues = $device->getPresetModes();

		if ($allowedValues === []) {
			return null;
		}

		$presets = [];

		foreach (Types\Preset::getAvailableValues() as $preset) {
			if (
				!in_array($preset, $allowedValues, true)
				|| in_array($preset, [Types\Preset::MANUAL, Types\Preset::AUTO], true)
			) {
				continue;
			}

			$presets[$preset] = $this->translator->translate(
				'//thermostat-device-addon.cmd.install.answers.preset.' . $preset,
			);
		}

		if (count($presets) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//thermostat-device-addon.cmd.install.questions.select.presetToUpdate'),
			array_values($presets),
			count($presets) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($presets): Types\Preset {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($presets))) {
					$answer = array_values($presets)[$answer];
				}

				$preset = array_search($answer, $presets, true);

				if ($preset !== false && Types\Preset::isValidValue($preset)) {
					return Types\Preset::get($preset);
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$preset = $io->askQuestion($question);
		assert($preset instanceof Types\Preset);

		return $preset;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichActor(
		Style\SymfonyStyle $io,
		Entities\ThermostatDevice $device,
	): DevicesEntities\Channels\Properties\Mapped|null
	{
		$actors = [];

		$findChannelsQuery = new Queries\Entities\FindActorChannels();
		$findChannelsQuery->forDevice($device);

		$channel = $this->channelsRepository->findOneBy($findChannelsQuery, Entities\Channels\Actors::class);

		if ($channel === null) {
			return null;
		}

		$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelMappedProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		$channelActors = $this->channelsPropertiesRepository->findAllBy(
			$findChannelPropertiesQuery,
			DevicesEntities\Channels\Properties\Mapped::class,
		);
		usort(
			$channelActors,
			static fn (DevicesEntities\Channels\Properties\Mapped $a, DevicesEntities\Channels\Properties\Mapped $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($channelActors as $channelActor) {
			$actors[$channelActor->getIdentifier()] = sprintf(
				'%s [%s: %s, %s: %s, %s: %s]',
				($channelActor->getName() ?? $channelActor->getIdentifier()),
				$this->translator->translate('//thermostat-device-addon.cmd.install.answers.device'),
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				($channelActor->getParent()->getChannel()->getDevice()->getName() ?? $channelActor->getParent()->getChannel()->getDevice()->getIdentifier()),
				$this->translator->translate('//thermostat-device-addon.cmd.install.answers.channel'),
				($channelActor->getParent()->getChannel()->getName() ?? $channelActor->getParent()->getChannel()->getIdentifier()),
				$this->translator->translate('//thermostat-device-addon.cmd.install.answers.property'),
				($channelActor->getParent()->getName() ?? $channelActor->getParent()->getIdentifier()),
			);
		}

		if (count($actors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//thermostat-device-addon.cmd.install.questions.select.actorToUpdate'),
			array_values($actors),
			count($actors) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($channel, $actors): DevicesEntities\Channels\Properties\Mapped {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($actors))) {
					$answer = array_values($actors)[$answer];
				}

				$identifier = array_search($answer, $actors, true);

				if ($identifier !== false) {
					$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelMappedProperties();
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
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
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
		Entities\ThermostatDevice $device,
	): DevicesEntities\Channels\Properties\Mapped|null
	{
		$sensors = [];

		$findChannelsQuery = new Queries\Entities\FindSensorChannels();
		$findChannelsQuery->forDevice($device);

		$channel = $this->channelsRepository->findOneBy($findChannelsQuery, Entities\Channels\Sensors::class);

		if ($channel === null) {
			return null;
		}

		$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelMappedProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		$channelSensors = $this->channelsPropertiesRepository->findAllBy(
			$findChannelPropertiesQuery,
			DevicesEntities\Channels\Properties\Mapped::class,
		);
		usort(
			$channelSensors,
			static fn (DevicesEntities\Channels\Properties\Mapped $a, DevicesEntities\Channels\Properties\Mapped $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($channelSensors as $channelSensor) {
			$sensors[$channelSensor->getIdentifier()] = sprintf(
				'%s [%s: %s, %s: %s, %s: %s]',
				($channelSensor->getName() ?? $channelSensor->getIdentifier()),
				$this->translator->translate('//thermostat-device-addon.cmd.install.answers.device'),
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				($channelSensor->getParent()->getChannel()->getDevice()->getName() ?? $channelSensor->getParent()->getChannel()->getDevice()->getIdentifier()),
				$this->translator->translate('//thermostat-device-addon.cmd.install.answers.channel'),
				($channelSensor->getParent()->getChannel()->getName() ?? $channelSensor->getParent()->getChannel()->getIdentifier()),
				$this->translator->translate('//thermostat-device-addon.cmd.install.answers.property'),
				($channelSensor->getParent()->getName() ?? $channelSensor->getParent()->getIdentifier()),
			);
		}

		if (count($sensors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//thermostat-device-addon.cmd.install.questions.select.sensorToUpdate'),
			array_values($sensors),
			count($sensors) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($channel, $sensors): DevicesEntities\Channels\Properties\Mapped {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($sensors))) {
					$answer = array_values($sensors)[$answer];
				}

				$identifier = array_search($answer, $sensors, true);

				if ($identifier !== false) {
					$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelMappedProperties();
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
						$this->translator->translate('//thermostat-device-addon.cmd.base.messages.answerNotValid'),
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

		$property = $property === null
			? $this->channelsPropertiesManager->create($data)
			: $this->channelsPropertiesManager->update($property, $data);

		assert($property instanceof $propertyType);

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

			$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertiesQuery->forChannel($channel);
			$findChannelPropertiesQuery->byIdentifier($identifier);

			if ($this->channelsPropertiesRepository->getResultSet($findChannelPropertiesQuery)->isEmpty()) {
				return $identifier;
			}
		}

		throw new Exceptions\InvalidState('Channel property identifier could not be created');
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	protected function getOrmConnection(): DBAL\Connection
	{
		$connection = $this->managerRegistry->getConnection();

		if ($connection instanceof DBAL\Connection) {
			return $connection;
		}

		throw new Exceptions\Runtime('Database connection could not be established');
	}

}
