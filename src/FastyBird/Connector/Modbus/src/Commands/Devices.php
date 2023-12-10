<?php declare(strict_types = 1);

/**
 * Devices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           21.01.23
 */

namespace FastyBird\Connector\Modbus\Commands;

use Doctrine\DBAL;
use Doctrine\Persistence;
use FastyBird\Connector\Modbus;
use FastyBird\Connector\Modbus\Entities;
use FastyBird\Connector\Modbus\Exceptions;
use FastyBird\Connector\Modbus\Queries;
use FastyBird\Connector\Modbus\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette\Localization;
use Nette\Utils;
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
use function intval;
use function is_array;
use function is_int;
use function is_string;
use function preg_match;
use function range;
use function sprintf;
use function strval;
use function trim;
use function usort;

/**
 * Connector devices management command
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Devices extends Console\Command\Command
{

	public const NAME = 'fb:modbus-connector:devices';
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private const MATCH_IP_ADDRESS_PORT = '/^(?P<address>((?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])[.]){3}(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5]))(:(?P<port>[0-9]{1,5}))?$/';

	public function __construct(
		private readonly Modbus\Logger $logger,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
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
			->setDescription('Modbus devices management');
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title($this->translator->translate('//modbus-connector.cmd.devices.title'));

		$io->note($this->translator->translate('//modbus-connector.cmd.devices.subtitle'));

		if ($input->getOption('no-interaction') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//modbus-connector.cmd.base.questions.continue'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				return Console\Command\Command::SUCCESS;
			}
		}

		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->warning($this->translator->translate('//modbus-connector.cmd.base.messages.noConnectors'));

			return Console\Command\Command::SUCCESS;
		}

		$this->askConnectorAction($io, $connector);

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	private function createDevice(Style\SymfonyStyle $io, Entities\ModbusConnector $connector): void
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.provide.identifier'),
		);

		$question->setValidator(function (string|null $answer) {
			if ($answer !== '' && $answer !== null) {
				$findDeviceQuery = new Queries\Entities\FindDevices();
				$findDeviceQuery->byIdentifier($answer);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\ModbusDevice::class) !== null
				) {
					throw new Exceptions\Runtime(
						$this->translator->translate(
							'//modbus-connector.cmd.devices.messages.identifier.used',
						),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'modbus-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findDeviceQuery = new Queries\Entities\FindDevices();
				$findDeviceQuery->byIdentifier($identifier);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\ModbusDevice::class) === null
				) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error(
				$this->translator->translate('//modbus-connector.cmd.devices.messages.identifier.missing'),
			);

			return;
		}

		$name = $this->askDeviceName($io);

		$address = $ipAddress = $port = $unitId = null;

		if ($connector->getClientMode()->equalsValue(Types\ClientMode::RTU)) {
			$address = $this->askDeviceAddress($io, $connector);
		}

		if ($connector->getClientMode()->equalsValue(Types\ClientMode::TCP)) {
			$ipAddress = $this->askDeviceIpAddress($io);

			if (
				preg_match(self::MATCH_IP_ADDRESS_PORT, $ipAddress, $matches) === 1
				&& array_key_exists('address', $matches)
				&& array_key_exists('port', $matches)
			) {
				$ipAddress = $matches['address'];
				$port = intval($matches['port']);
			} else {
				$port = $this->askDeviceIpAddressPort($io);
			}

			$unitId = $this->askDeviceUnitId($io, $connector);
		}

		$byteOrder = $this->askDeviceByteOrder($io);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->create(Utils\ArrayHash::from([
				'entity' => Entities\ModbusDevice::class,
				'connector' => $connector,
				'identifier' => $identifier,
				'name' => $name,
			]));
			assert($device instanceof Entities\ModbusDevice);

			if ($connector->getClientMode()->equalsValue(Types\ClientMode::RTU)) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::ADDRESS,
					'name' => DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::ADDRESS),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
					'value' => $address,
					'device' => $device,
				]));
			}

			if ($connector->getClientMode()->equalsValue(Types\ClientMode::TCP)) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::IP_ADDRESS,
					'name' => DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::IP_ADDRESS),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $ipAddress,
					'device' => $device,
				]));

				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::PORT,
					'name' => DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::PORT),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
					'value' => $port,
					'device' => $device,
				]));

				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::UNIT_ID,
					'name' => DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::UNIT_ID),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
					'value' => $unitId,
					'device' => $device,
				]));
			}

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::BYTE_ORDER,
				'name' => DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::BYTE_ORDER),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $byteOrder->getValue(),
				'device' => $device,
			]));

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//modbus-connector.cmd.devices.messages.create.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate('//modbus-connector.cmd.devices.messages.create.device.error'),
			);

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.create.registers'),
			true,
		);

		$createRegisters = (bool) $io->askQuestion($question);

		if ($createRegisters) {
			$this->createRegister($io, $device);
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	private function editDevice(Style\SymfonyStyle $io, Entities\ModbusConnector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->warning($this->translator->translate('//modbus-connector.cmd.devices.messages.noDevices'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//modbus-connector.cmd.devices.questions.create.device'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createDevice($io, $connector);
			}

			return;
		}

		$name = $this->askDeviceName($io, $device);

		$address = $ipAddress = $port = $unitId = null;

		$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::ADDRESS);

		$addressProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IP_ADDRESS);

		$ipAddressProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::PORT);

		$portProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::UNIT_ID);

		$unitIdProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::BYTE_ORDER);

		$byteOrderProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($connector->getClientMode()->equalsValue(Types\ClientMode::RTU)) {
			$address = $this->askDeviceAddress($io, $connector, $device);
		}

		if ($connector->getClientMode()->equalsValue(Types\ClientMode::TCP)) {
			$ipAddress = $this->askDeviceIpAddress($io, $device);

			if (
				preg_match(self::MATCH_IP_ADDRESS_PORT, $ipAddress, $matches) === 1
				&& array_key_exists('address', $matches)
				&& array_key_exists('port', $matches)
			) {
				$ipAddress = $matches['address'];
				$port = intval($matches['port']);
			} else {
				$port = $this->askDeviceIpAddressPort($io, $device);
			}

			$unitId = $this->askDeviceUnitId($io, $connector, $device);
		}

		$byteOrder = $this->askDeviceByteOrder($io, $device);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->update($device, Utils\ArrayHash::from([
				'name' => $name,
			]));

			if ($connector->getClientMode()->equalsValue(Types\ClientMode::RTU)) {
				if ($addressProperty === null) {
					$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Devices\Properties\Variable::class,
						'identifier' => Types\DevicePropertyIdentifier::ADDRESS,
						'name' => DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::ADDRESS),
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
						'value' => $address,
						'device' => $device,
					]));
				} elseif ($addressProperty instanceof DevicesEntities\Devices\Properties\Variable) {
					$this->devicesPropertiesManager->update($addressProperty, Utils\ArrayHash::from([
						'value' => $address,
					]));
				}
			} elseif ($addressProperty !== null) {
				$this->devicesPropertiesManager->delete($addressProperty);
			}

			if ($connector->getClientMode()->equalsValue(Types\ClientMode::TCP)) {
				if ($ipAddressProperty === null) {
					$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Devices\Properties\Variable::class,
						'identifier' => Types\DevicePropertyIdentifier::IP_ADDRESS,
						'name' => DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::IP_ADDRESS),
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
						'value' => $ipAddress,
						'device' => $device,
					]));
				} elseif ($ipAddressProperty instanceof DevicesEntities\Devices\Properties\Variable) {
					$this->devicesPropertiesManager->update($ipAddressProperty, Utils\ArrayHash::from([
						'value' => $ipAddress,
					]));
				}

				if ($portProperty === null) {
					$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Devices\Properties\Variable::class,
						'identifier' => Types\DevicePropertyIdentifier::PORT,
						'name' => DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::PORT),
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
						'value' => $port,
						'device' => $device,
					]));
				} elseif ($portProperty instanceof DevicesEntities\Devices\Properties\Variable) {
					$this->devicesPropertiesManager->update($portProperty, Utils\ArrayHash::from([
						'value' => $port,
					]));
				}

				if ($unitIdProperty === null) {
					$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Devices\Properties\Variable::class,
						'identifier' => Types\DevicePropertyIdentifier::UNIT_ID,
						'name' => DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::UNIT_ID),
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
						'value' => $unitId,
						'device' => $device,
					]));
				} elseif ($unitIdProperty instanceof DevicesEntities\Devices\Properties\Variable) {
					$this->devicesPropertiesManager->update($unitIdProperty, Utils\ArrayHash::from([
						'value' => $unitId,
					]));
				}
			} else {
				if ($ipAddressProperty !== null) {
					$this->devicesPropertiesManager->delete($ipAddressProperty);
				}

				if ($portProperty !== null) {
					$this->devicesPropertiesManager->delete($portProperty);
				}

				if ($unitIdProperty !== null) {
					$this->devicesPropertiesManager->delete($unitIdProperty);
				}
			}

			if ($byteOrderProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::BYTE_ORDER,
					'name' => DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::BYTE_ORDER),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $byteOrder->getValue(),
					'device' => $device,
				]));
			} elseif ($byteOrderProperty instanceof DevicesEntities\Devices\Properties\Variable) {
				$this->devicesPropertiesManager->update($byteOrderProperty, Utils\ArrayHash::from([
					'value' => $byteOrder->getValue(),
				]));
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//modbus-connector.cmd.devices.messages.update.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//modbus-connector.cmd.devices.messages.update.error'));
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		assert($device instanceof Entities\ModbusDevice);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.manage.registers'),
			false,
		);

		$manage = (bool) $io->askQuestion($question);

		if (!$manage) {
			return;
		}

		$this->askDeviceAction($io, $device);
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function deleteDevice(Style\SymfonyStyle $io, Entities\ModbusConnector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->warning($this->translator->translate('//modbus-connector.cmd.devices.messages.noDevices'));

			return;
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//modbus-connector.cmd.base.questions.continue'),
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
					'//modbus-connector.cmd.devices.messages.remove.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//modbus-connector.cmd.devices.messages.remove.error'));
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
	private function listDevices(Style\SymfonyStyle $io, Entities\ModbusConnector $connector): void
	{
		$findDevicesQuery = new Queries\Entities\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\ModbusDevice::class);
		usort(
			$devices,
			static function (Entities\ModbusDevice $a, Entities\ModbusDevice $b): int {
				if ($a->getIdentifier() === $b->getIdentifier()) {
					return $a->getName() <=> $b->getName();
				}

				return $a->getIdentifier() <=> $b->getIdentifier();
			},
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//modbus-connector.cmd.devices.data.name'),
		]);

		foreach ($devices as $index => $device) {
			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function createRegister(Style\SymfonyStyle $io, Entities\ModbusDevice $device, bool $editMode = false): void
	{
		$type = $this->askRegisterType($io);

		$dataType = $this->askRegisterDataType($io, $type);

		$addresses = $this->askRegisterAddress($io, $device);

		if (is_int($addresses)) {
			$addresses = [$addresses, $addresses];
		}

		$name = $addresses[0] === $addresses[1] ? $this->askRegisterName($io) : null;

		$readingDelay = $this->askRegisterReadingDelay($io);

		$format = null;

		if (
			$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
		) {
			$format = $this->askRegisterFormat($io, $dataType);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			foreach (range($addresses[0], $addresses[1]) as $address) {
				$channel = $this->channelsManager->create(Utils\ArrayHash::from([
					'entity' => Entities\ModbusChannel::class,
					'identifier' => $type . '_' . $address,
					'name' => $name,
					'device' => $device,
				]));

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Types\ChannelPropertyIdentifier::ADDRESS,
					'name' => DevicesUtilities\Name::createName(Types\ChannelPropertyIdentifier::ADDRESS),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
					'value' => $address,
					'channel' => $channel,
				]));

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Types\ChannelPropertyIdentifier::TYPE,
					'name' => DevicesUtilities\Name::createName(Types\ChannelPropertyIdentifier::TYPE),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $type->getValue(),
					'channel' => $channel,
				]));

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Types\ChannelPropertyIdentifier::READING_DELAY,
					'name' => DevicesUtilities\Name::createName(Types\ChannelPropertyIdentifier::READING_DELAY),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
					'value' => $readingDelay,
					'channel' => $channel,
				]));

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::VALUE,
					'name' => DevicesUtilities\Name::createName(Types\ChannelPropertyIdentifier::VALUE),
					'dataType' => $dataType,
					'format' => $format,
					'settable' => (
						$type->equalsValue(Types\ChannelType::COIL)
						|| $type->equalsValue(Types\ChannelType::HOLDING_REGISTER)
					),
					'queryable' => true,
					'channel' => $channel,
				]));
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			if ($addresses[0] === $addresses[1]) {
				$io->success(
					$this->translator->translate(
						'//modbus-connector.cmd.devices.messages.create.register.success',
						['name' => $device->getName() ?? $device->getIdentifier()],
					),
				);
			} else {
				$io->success(
					$this->translator->translate(
						'//modbus-connector.cmd.devices.messages.create.registers.success',
						['name' => $device->getName() ?? $device->getIdentifier()],
					),
				);
			}
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate('//modbus-connector.cmd.devices.messages.create.register.error'),
			);

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		if ($editMode) {
			return;
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.create.register'),
			false,
		);

		$create = (bool) $io->askQuestion($question);

		if ($create) {
			$this->createRegister($io, $device, $editMode);
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function editRegister(Style\SymfonyStyle $io, Entities\ModbusDevice $device): void
	{
		$channel = $this->askWhichRegister($io, $device);

		if ($channel === null) {
			$io->warning($this->translator->translate('//modbus-connector.cmd.devices.messages.noRegisters'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//modbus-connector.cmd.devices.questions.create.registers'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createRegister($io, $device);
			}

			return;
		}

		$type = $channel->getRegisterType();

		if ($type === null) {
			$type = $this->askRegisterType($io, $channel);
		}

		$dataType = $this->askRegisterDataType($io, $type, $channel);

		$address = $this->askRegisterAddress($io, $device, $channel);

		if (is_array($address)) {
			$address = $address[0];
		}

		$name = $this->askRegisterName($io, $channel);

		$readingDelay = $this->askRegisterReadingDelay($io, $channel);

		$format = null;

		if (
			$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
			|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
		) {
			$format = $this->askRegisterFormat($io, $dataType, $channel);
		}

		$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::ADDRESS);

		$addressProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

		$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::TYPE);

		$typeProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

		$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::READING_DELAY);

		$readingDelayProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

		$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::VALUE);

		$valueProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$channel = $this->channelsManager->update($channel, Utils\ArrayHash::from([
				'name' => $name,
			]));

			if ($addressProperty === null) {
				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Types\ChannelPropertyIdentifier::ADDRESS,
					'name' => DevicesUtilities\Name::createName(Types\ChannelPropertyIdentifier::ADDRESS),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
					'value' => $address,
					'channel' => $channel,
				]));
			} else {
				$this->channelsPropertiesManager->update($addressProperty, Utils\ArrayHash::from([
					'value' => $address,
				]));
			}

			if ($typeProperty === null) {
				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Types\ChannelPropertyIdentifier::TYPE,
					'name' => DevicesUtilities\Name::createName(Types\ChannelPropertyIdentifier::TYPE),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $type->getValue(),
					'channel' => $channel,
				]));
			}

			if ($readingDelayProperty === null) {
				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Types\ChannelPropertyIdentifier::READING_DELAY,
					'name' => DevicesUtilities\Name::createName(Types\ChannelPropertyIdentifier::READING_DELAY),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
					'value' => $readingDelay,
					'channel' => $channel,
				]));
			} else {
				$this->channelsPropertiesManager->update($readingDelayProperty, Utils\ArrayHash::from([
					'value' => $readingDelay,
				]));
			}

			if ($valueProperty === null) {
				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::VALUE,
					'name' => DevicesUtilities\Name::createName(Types\ChannelPropertyIdentifier::VALUE),
					'dataType' => $dataType,
					'format' => $format,
					'settable' => (
						$type->equalsValue(Types\ChannelType::COIL)
						|| $type->equalsValue(Types\ChannelType::HOLDING_REGISTER)
					),
					'queryable' => true,
					'channel' => $channel,
				]));
			} else {
				$this->channelsPropertiesManager->update($valueProperty, Utils\ArrayHash::from([
					'dataType' => $dataType,
					'format' => $format,
					'settable' => (
						$type->equalsValue(Types\ChannelType::COIL)
						|| $type->equalsValue(Types\ChannelType::HOLDING_REGISTER)
					),
				]));
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//modbus-connector.cmd.devices.messages.update.register.success',
					['name' => $channel->getName() ?? $channel->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate('//modbus-connector.cmd.devices.messages.update.register.error'),
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
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function deleteRegister(Style\SymfonyStyle $io, Entities\ModbusDevice $device): void
	{
		$channel = $this->askWhichRegister($io, $device);

		if ($channel === null) {
			$io->warning($this->translator->translate('//modbus-connector.cmd.devices.messages.noRegisters'));

			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->channelsManager->delete($channel);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//modbus-connector.cmd.devices.messages.remove.register.success',
					['name' => $channel->getName() ?? $channel->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate('//modbus-connector.cmd.devices.messages.remove.register.error'),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function listRegisters(Style\SymfonyStyle $io, Entities\ModbusDevice $device): void
	{
		$findChannelsQuery = new Queries\Entities\FindChannels();
		$findChannelsQuery->forDevice($device);

		$deviceChannels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\ModbusChannel::class);
		usort(
			$deviceChannels,
			static function (Entities\ModbusChannel $a, Entities\ModbusChannel $b): int {
				if ($a->getRegisterType() === $b->getRegisterType()) {
					return $a->getAddress() <=> $b->getAddress();
				}

				return $a->getRegisterType() <=> $b->getRegisterType();
			},
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//modbus-connector.cmd.devices.data.name'),
			$this->translator->translate('//modbus-connector.cmd.devices.data.type'),
			$this->translator->translate('//modbus-connector.cmd.devices.data.address'),
			$this->translator->translate('//modbus-connector.cmd.devices.data.dataType'),
		]);

		foreach ($deviceChannels as $index => $channel) {
			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::VALUE);

			$valueProperty = $this->channelsPropertiesRepository->findOneBy(
				$findChannelPropertyQuery,
				DevicesEntities\Channels\Properties\Dynamic::class,
			);

			$table->addRow([
				$index + 1,
				$channel->getName() ?? $channel->getIdentifier(),
				strval($channel->getRegisterType()?->getValue()),
				$channel->getAddress(),
				$valueProperty?->getDataType()->getValue(),
			]);
		}

		$table->render();

		$io->newLine();
	}

	private function askDeviceName(Style\SymfonyStyle $io, Entities\ModbusDevice|null $device = null): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.provide.name'),
			$device?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askDeviceAddress(
		Style\SymfonyStyle $io,
		Entities\ModbusConnector $connector,
		Entities\ModbusDevice|null $device = null,
	): int
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.provide.hardwareAddress'),
			$device?->getAddress(),
		);
		$question->setValidator(function (string|null $answer) use ($connector, $device) {
			if (strval(intval($answer)) !== strval($answer)) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			$findDevicesQuery = new Queries\Entities\FindDevices();
			$findDevicesQuery->forConnector($connector);

			foreach ($this->devicesRepository->findAllBy(
				$findDevicesQuery,
				Entities\ModbusDevice::class,
			) as $connectorDevice) {
				if (
					$connectorDevice->getAddress() === intval($answer)
					&& ($device === null || !$device->getId()->equals($connectorDevice->getId()))
				) {
					throw new Exceptions\Runtime(
						$this->translator->translate('//modbus-connector.cmd.devices.messages.deviceAddressTaken'),
					);
				}
			}

			return $answer;
		});

		return intval($io->askQuestion($question));
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askDeviceIpAddress(
		Style\SymfonyStyle $io,
		Entities\ModbusDevice|null $device = null,
	): string
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.provide.ipAddress'),
			$device?->getIpAddress(),
		);
		$question->setValidator(function (string|null $answer) {
			if (
				preg_match(self::MATCH_IP_ADDRESS_PORT, strval($answer), $matches) === 1
				&& array_key_exists('address', $matches)
			) {
				if (array_key_exists('port', $matches)) {
					return $matches['address'] . ':' . $matches['port'];
				}

				return $matches['address'];
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		return strval($io->askQuestion($question));
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askDeviceIpAddressPort(
		Style\SymfonyStyle $io,
		Entities\ModbusDevice|null $device = null,
	): int
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.provide.port'),
			$device?->getPort(),
		);
		$question->setValidator(function (string|null $answer) {
			if (strval(intval($answer)) !== strval($answer)) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			return $answer;
		});

		return intval($io->askQuestion($question));
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askDeviceUnitId(
		Style\SymfonyStyle $io,
		Entities\ModbusConnector $connector,
		Entities\ModbusDevice|null $device = null,
	): int
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.provide.unitIdentifier'),
			$device?->getUnitId(),
		);
		$question->setValidator(function (string|null $answer) use ($connector, $device) {
			if (strval(intval($answer)) !== strval($answer)) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			$findDevicesQuery = new Queries\Entities\FindDevices();
			$findDevicesQuery->forConnector($connector);

			foreach ($this->devicesRepository->findAllBy(
				$findDevicesQuery,
				Entities\ModbusDevice::class,
			) as $connectorDevice) {
				if (
					$connectorDevice->getUnitId() === intval($answer)
					&& ($device === null || !$device->getId()->equals($connectorDevice->getId()))
				) {
					throw new Exceptions\Runtime(
						$this->translator->translate('//modbus-connector.cmd.devices.messages.unitIdentifierTaken'),
					);
				}
			}

			return $answer;
		});

		return intval($io->askQuestion($question));
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askDeviceByteOrder(
		Style\SymfonyStyle $io,
		Entities\ModbusDevice|null $device = null,
	): Types\ByteOrder
	{
		$default = 0;

		if ($device !== null) {
			if ($device->getByteOrder()->equalsValue(Types\ByteOrder::BIG_SWAP)) {
				$default = 1;
			} elseif ($device->getByteOrder()->equalsValue(Types\ByteOrder::LITTLE)) {
				$default = 2;
			} elseif ($device->getByteOrder()->equalsValue(Types\ByteOrder::LITTLE_SWAP)) {
				$default = 3;
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.select.byteOrder'),
			[
				0 => $this->translator->translate('//modbus-connector.cmd.devices.answers.endian.big'),
				1 => $this->translator->translate('//modbus-connector.cmd.devices.answers.endian.bigSwap'),
				2 => $this->translator->translate('//modbus-connector.cmd.devices.answers.endian.little'),
				3 => $this->translator->translate('//modbus-connector.cmd.devices.answers.endian.littleSwap'),
			],
			$default,
		);

		$question->setErrorMessage(
			$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer): Types\ByteOrder {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (
				$answer === $this->translator->translate('//modbus-connector.cmd.devices.answers.endian.big')
				|| $answer === '0'
			) {
				return Types\ByteOrder::get(Types\ByteOrder::BIG);
			}

			if (
				$answer === $this->translator->translate('//modbus-connector.cmd.devices.answers.endian.bigSwap')
				|| $answer === '1'
			) {
				return Types\ByteOrder::get(Types\ByteOrder::BIG_SWAP);
			}

			if (
				$answer === $this->translator->translate('//modbus-connector.cmd.devices.answers.endian.little')
				|| $answer === '2'
			) {
				return Types\ByteOrder::get(Types\ByteOrder::LITTLE);
			}

			if (
				$answer === $this->translator->translate('//modbus-connector.cmd.devices.answers.endian.littleSwap')
				|| $answer === '3'
			) {
				return Types\ByteOrder::get(Types\ByteOrder::LITTLE_SWAP);
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\ByteOrder);

		return $answer;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askRegisterType(
		Style\SymfonyStyle $io,
		Entities\ModbusChannel|null $channel = null,
	): Types\ChannelType
	{
		if ($channel !== null) {
			$type = $channel->getRegisterType();

			$default = 0;

			if ($type !== null && $type->equalsValue(Types\ChannelType::COIL)) {
				$default = 1;
			} elseif ($type !== null && $type->equalsValue(Types\ChannelType::INPUT_REGISTER)) {
				$default = 2;
			} elseif ($type !== null && $type->equalsValue(Types\ChannelType::HOLDING_REGISTER)) {
				$default = 3;
			}

			$question = new Console\Question\ChoiceQuestion(
				$this->translator->translate('//modbus-connector.cmd.devices.questions.select.registerType'),
				[
					$this->translator->translate('//modbus-connector.cmd.devices.answers.registerType.discreteInput'),
					$this->translator->translate('//modbus-connector.cmd.devices.answers.registerType.coil'),
					$this->translator->translate('//modbus-connector.cmd.devices.answers.registerType.inputRegister'),
					$this->translator->translate('//modbus-connector.cmd.devices.answers.registerType.holdingRegister'),
				],
				$default,
			);
		} else {
			$question = new Console\Question\ChoiceQuestion(
				$this->translator->translate('//modbus-connector.cmd.devices.questions.select.newRegisterType'),
				[
					$this->translator->translate('//modbus-connector.cmd.devices.answers.registerType.discreteInput'),
					$this->translator->translate('//modbus-connector.cmd.devices.answers.registerType.coil'),
					$this->translator->translate('//modbus-connector.cmd.devices.answers.registerType.inputRegister'),
					$this->translator->translate('//modbus-connector.cmd.devices.answers.registerType.holdingRegister'),
				],
				0,
			);
		}

		$question->setErrorMessage(
			$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer): Types\ChannelType {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (
				$answer === $this->translator->translate(
					'//modbus-connector.cmd.devices.answers.registerType.discreteInput',
				)
				|| $answer === '0'
			) {
				return Types\ChannelType::get(Types\ChannelType::DISCRETE_INPUT);
			}

			if (
				$answer === $this->translator->translate('//modbus-connector.cmd.devices.answers.registerType.coil')
				|| $answer === '1'
			) {
				return Types\ChannelType::get(Types\ChannelType::COIL);
			}

			if (
				$answer === $this->translator->translate(
					'//modbus-connector.cmd.devices.answers.registerType.inputRegister',
				)
				|| $answer === '2'
			) {
				return Types\ChannelType::get(Types\ChannelType::INPUT_REGISTER);
			}

			if (
				$answer === $this->translator->translate(
					'//modbus-connector.cmd.devices.answers.registerType.holdingRegister',
				)
				|| $answer === '3'
			) {
				return Types\ChannelType::get(Types\ChannelType::HOLDING_REGISTER);
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\ChannelType);

		return $answer;
	}

	/**
	 * @return int|array<int>
	 *
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askRegisterAddress(
		Style\SymfonyStyle $io,
		Entities\ModbusDevice $device,
		Entities\ModbusChannel|null $channel = null,
	): int|array
	{
		$address = $channel?->getAddress();

		$question = new Console\Question\Question(
			(
				$channel !== null
					? $this->translator->translate('//modbus-connector.cmd.devices.questions.provide.register.address')
					: $this->translator->translate(
						'//modbus-connector.cmd.devices.questions.provide.register.addresses',
					)
			),
			$address,
		);
		$question->setValidator(function (string|null $answer) use ($device, $channel) {
			if (strval(intval($answer)) === strval($answer)) {
				$findChannelsQuery = new Queries\Entities\FindChannels();
				$findChannelsQuery->forDevice($device);

				$channels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\ModbusChannel::class);

				foreach ($channels as $deviceChannel) {
					$address = $deviceChannel->getAddress();

					if (
						intval($address) === intval($answer)
						&& (
							$channel === null
							|| !$channel->getId()->equals($deviceChannel->getId())
						)
					) {
						throw new Exceptions\Runtime(
							$this->translator->translate(
								'//modbus-connector.cmd.devices.messages.registerAddressTaken',
								['address' => intval($address)],
							),
						);
					}
				}

				return intval($answer);
			}

			if ($channel === null) {
				if (
					preg_match('/^([0-9]+)-([0-9]+)$/', strval($answer), $matches) === 1
					&& count($matches) === 3
				) {
					$start = intval($matches[1]);
					$end = intval($matches[2]);

					if ($start < $end) {
						$findChannelsQuery = new Queries\Entities\FindChannels();
						$findChannelsQuery->forDevice($device);

						$channels = $this->channelsRepository->findAllBy(
							$findChannelsQuery,
							Entities\ModbusChannel::class,
						);

						foreach ($channels as $deviceChannel) {
							$address = $deviceChannel->getAddress();

							if (intval($address) >= $start && intval($address) <= $end) {
								throw new Exceptions\Runtime(
									$this->translator->translate(
										'//modbus-connector.cmd.devices.messages.registerAddressTaken',
										['address' => intval($address)],
									),
								);
							}
						}

						return [$start, $end];
					}
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		/** @var int|array<int> $address */
		$address = $io->askQuestion($question);

		return $address;
	}

	private function askRegisterName(
		Style\SymfonyStyle $io,
		Entities\ModbusChannel|null $channel = null,
	): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.provide.register.name'),
			$channel?->getName(),
		);

		$name = strval($io->askQuestion($question));

		return $name === '' ? null : $name;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askRegisterReadingDelay(
		Style\SymfonyStyle $io,
		Entities\ModbusChannel|null $channel = null,
	): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.provide.register.readingDelay'),
			$channel?->getReadingDelay() ?? Entities\ModbusChannel::READING_DELAY,
		);

		$name = strval($io->askQuestion($question));

		return $name === '' ? null : $name;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 */
	private function askRegisterDataType(
		Style\SymfonyStyle $io,
		Types\ChannelType $type,
		Entities\ModbusChannel|null $channel = null,
	): MetadataTypes\DataType
	{
		$default = null;

		if (
			$type->equalsValue(Types\ChannelType::DISCRETE_INPUT)
			|| $type->equalsValue(Types\ChannelType::COIL)
		) {
			return MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN);
		} elseif (
			$type->equalsValue(Types\ChannelType::HOLDING_REGISTER)
			|| $type->equalsValue(Types\ChannelType::INPUT_REGISTER)
		) {
			$dataTypes = [
				MetadataTypes\DataType::DATA_TYPE_CHAR,
				MetadataTypes\DataType::DATA_TYPE_UCHAR,
				MetadataTypes\DataType::DATA_TYPE_SHORT,
				MetadataTypes\DataType::DATA_TYPE_USHORT,
				MetadataTypes\DataType::DATA_TYPE_INT,
				MetadataTypes\DataType::DATA_TYPE_UINT,
				MetadataTypes\DataType::DATA_TYPE_FLOAT,
				MetadataTypes\DataType::DATA_TYPE_STRING,
			];

			$dataTypes[] = $type->equalsValue(Types\ChannelType::HOLDING_REGISTER)
				? MetadataTypes\DataType::DATA_TYPE_SWITCH
				: MetadataTypes\DataType::DATA_TYPE_BUTTON;

			if ($channel !== null) {
				$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
				$findChannelPropertyQuery->forChannel($channel);
				$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::VALUE);

				$valueProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

				switch ($valueProperty?->getDataType()->getValue()) {
					case MetadataTypes\DataType::DATA_TYPE_CHAR:
						$default = 0;

						break;
					case MetadataTypes\DataType::DATA_TYPE_UCHAR:
						$default = 1;

						break;
					case MetadataTypes\DataType::DATA_TYPE_SHORT:
						$default = 2;

						break;
					case MetadataTypes\DataType::DATA_TYPE_USHORT:
						$default = 3;

						break;
					case MetadataTypes\DataType::DATA_TYPE_INT:
						$default = 4;

						break;
					case MetadataTypes\DataType::DATA_TYPE_UINT:
						$default = 5;

						break;
					case MetadataTypes\DataType::DATA_TYPE_FLOAT:
						$default = 6;

						break;
					case MetadataTypes\DataType::DATA_TYPE_STRING:
						$default = 7;

						break;
					case MetadataTypes\DataType::DATA_TYPE_SWITCH:
						$default = 8;

						break;
					case MetadataTypes\DataType::DATA_TYPE_BUTTON:
						$default = 9;

						break;
				}
			}
		} else {
			throw new Exceptions\InvalidArgument('Unknown register type');
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.provide.register.dataType'),
			$dataTypes,
			$default ?? $dataTypes[0],
		);

		$question->setErrorMessage(
			$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($dataTypes): MetadataTypes\DataType {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (MetadataTypes\DataType::isValidValue($answer)) {
				return MetadataTypes\DataType::get($answer);
			}

			if (
				array_key_exists($answer, $dataTypes)
				&& MetadataTypes\DataType::isValidValue($dataTypes[$answer])
			) {
				return MetadataTypes\DataType::get($dataTypes[$answer]);
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof MetadataTypes\DataType);

		return $answer;
	}

	/**
	 * @return array<int, array<int, array<int, string>>>|null
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askRegisterFormat(
		Style\SymfonyStyle $io,
		MetadataTypes\DataType $dataType,
		Entities\ModbusChannel|null $channel = null,
	): array|null
	{
		$format = [];

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)) {
			foreach ([
				MetadataTypes\SwitchPayload::get(MetadataTypes\SwitchPayload::PAYLOAD_ON),
				MetadataTypes\SwitchPayload::get(MetadataTypes\SwitchPayload::PAYLOAD_OFF),
				MetadataTypes\SwitchPayload::get(MetadataTypes\SwitchPayload::PAYLOAD_TOGGLE),
			] as $payloadType) {
				$result = $this->askFormatSwitchAction($io, $payloadType, $channel);

				if ($result !== null) {
					$format[] = $result;
				}
			}

			return $format;
		} elseif ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)) {
			foreach ([
				MetadataTypes\ButtonPayload::get(MetadataTypes\ButtonPayload::PAYLOAD_PRESSED),
				MetadataTypes\ButtonPayload::get(MetadataTypes\ButtonPayload::PAYLOAD_RELEASED),
				MetadataTypes\ButtonPayload::get(MetadataTypes\ButtonPayload::PAYLOAD_CLICKED),
				MetadataTypes\ButtonPayload::get(MetadataTypes\ButtonPayload::PAYLOAD_DOUBLE_CLICKED),
				MetadataTypes\ButtonPayload::get(MetadataTypes\ButtonPayload::PAYLOAD_TRIPLE_CLICKED),
				MetadataTypes\ButtonPayload::get(MetadataTypes\ButtonPayload::PAYLOAD_LONG_CLICKED),
				MetadataTypes\ButtonPayload::get(MetadataTypes\ButtonPayload::PAYLOAD_EXTRA_LONG_CLICKED),
			] as $payloadType) {
				$result = $this->askFormatButtonAction($io, $payloadType, $channel);

				if ($result !== null) {
					$format[] = $result;
				}
			}

			return $format;
		}

		return null;
	}

	/**
	 * @return array<int, array<int, string>>|null
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askFormatSwitchAction(
		Style\SymfonyStyle $io,
		MetadataTypes\SwitchPayload $payload,
		Entities\ModbusChannel|null $channel = null,
	): array|null
	{
		$defaultReading = $defaultWriting = null;

		$existingProperty = null;

		if ($channel !== null) {
			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::VALUE);

			$existingProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);
		}

		$hasSupport = false;

		if ($existingProperty !== null) {
			$format = $existingProperty->getFormat();

			if ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				foreach ($format->getItems() as $item) {
					if (count($item) === 3) {
						if (
							$item[0] !== null
							&& $item[0]->getValue() instanceof MetadataTypes\SwitchPayload
							&& $item[0]->getValue()->equals($payload)
						) {
							$defaultReading = $item[1]?->toArray();
							$defaultWriting = $item[2]?->toArray();

							$hasSupport = true;
						}
					}
				}
			}
		}

		if ($payload->equalsValue(MetadataTypes\SwitchPayload::PAYLOAD_ON)) {
			$questionText = $this->translator->translate('//modbus-connector.cmd.devices.questions.switch.hasOn');
		} elseif ($payload->equalsValue(MetadataTypes\SwitchPayload::PAYLOAD_OFF)) {
			$questionText = $this->translator->translate('//modbus-connector.cmd.devices.questions.switch.hasOff');
		} elseif ($payload->equalsValue(MetadataTypes\SwitchPayload::PAYLOAD_TOGGLE)) {
			$questionText = $this->translator->translate('//modbus-connector.cmd.devices.questions.switch.hasToggle');
		} else {
			throw new Exceptions\InvalidArgument('Provided payload type is not valid');
		}

		$question = new Console\Question\ConfirmationQuestion($questionText, $hasSupport);

		$support = (bool) $io->askQuestion($question);

		if (!$support) {
			return null;
		}

		return [
			[
				MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH,
				strval($payload->getValue()),
			],
			$this->askFormatSwitchActionValues($io, $payload, true, $defaultReading),
			$this->askFormatSwitchActionValues($io, $payload, false, $defaultWriting),
		];
	}

	/**
	 * @param array<int, bool|float|int|string>|null $default
	 *
	 * @return array<int, string>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private function askFormatSwitchActionValues(
		Style\SymfonyStyle $io,
		MetadataTypes\SwitchPayload $payload,
		bool $reading,
		array|null $default,
	): array
	{
		assert((is_array($default) && count($default) === 2) || $default === null);

		if ($reading) {
			if ($payload->equalsValue(MetadataTypes\SwitchPayload::PAYLOAD_ON)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.switch.readOnValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.switch.readOnValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\SwitchPayload::PAYLOAD_OFF)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.switch.readOffValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.switch.readOffValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\SwitchPayload::PAYLOAD_TOGGLE)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.switch.readToggleValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.switch.readToggleValueError',
				);
			} else {
				throw new Exceptions\InvalidArgument('Provided payload type is not valid');
			}
		} else {
			if ($payload->equalsValue(MetadataTypes\SwitchPayload::PAYLOAD_ON)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.switch.writeOnValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.switch.writeOnValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\SwitchPayload::PAYLOAD_OFF)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.switch.writeOffValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.switch.writeOffValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\SwitchPayload::PAYLOAD_TOGGLE)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.switch.writeToggleValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.switch.writeToggleValueError',
				);
			} else {
				throw new Exceptions\InvalidArgument('Provided payload type is not valid');
			}
		}

		$question = new Console\Question\Question($questionText, $default !== null ? $default[1] : null);
		$question->setValidator(function (string|null $answer) use ($io, $questionError): string|null {
			if (trim(strval($answer)) === '') {
				$question = new Console\Question\ConfirmationQuestion(
					$this->translator->translate('//modbus-connector.cmd.devices.questions.skipValue'),
					true,
				);

				$skip = (bool) $io->askQuestion($question);

				if ($skip) {
					return null;
				}

				throw new Exceptions\Runtime($questionError);
			}

			return strval($answer);
		});

		$switchReading = $io->askQuestion($question);
		assert(is_string($switchReading) || $switchReading === null);

		if ($switchReading === null) {
			return [];
		}

		if (strval(intval($switchReading)) === $switchReading) {
			$dataTypes = [
				MetadataTypes\DataTypeShort::DATA_TYPE_BOOLEAN,
				MetadataTypes\DataTypeShort::DATA_TYPE_CHAR,
				MetadataTypes\DataTypeShort::DATA_TYPE_UCHAR,
				MetadataTypes\DataTypeShort::DATA_TYPE_SHORT,
				MetadataTypes\DataTypeShort::DATA_TYPE_USHORT,
				MetadataTypes\DataTypeShort::DATA_TYPE_INT,
				MetadataTypes\DataTypeShort::DATA_TYPE_UINT,
				MetadataTypes\DataTypeShort::DATA_TYPE_FLOAT,
			];

			$selected = null;

			if ($default !== null) {
				if ($default[0] === MetadataTypes\DataTypeShort::DATA_TYPE_BOOLEAN) {
					$selected = 0;
				} elseif ($default[0] === MetadataTypes\DataTypeShort::DATA_TYPE_CHAR) {
					$selected = 1;
				} elseif ($default[0] === MetadataTypes\DataTypeShort::DATA_TYPE_UCHAR) {
					$selected = 2;
				} elseif ($default[0] === MetadataTypes\DataTypeShort::DATA_TYPE_SHORT) {
					$selected = 3;
				} elseif ($default[0] === MetadataTypes\DataTypeShort::DATA_TYPE_USHORT) {
					$selected = 4;
				} elseif ($default[0] === MetadataTypes\DataTypeShort::DATA_TYPE_INT) {
					$selected = 5;
				} elseif ($default[0] === MetadataTypes\DataTypeShort::DATA_TYPE_UINT) {
					$selected = 6;
				} elseif ($default[0] === MetadataTypes\DataTypeShort::DATA_TYPE_FLOAT) {
					$selected = 7;
				}
			}

			$question = new Console\Question\ChoiceQuestion(
				$this->translator->translate('//modbus-connector.cmd.devices.questions.select.valueDataType'),
				$dataTypes,
				$selected,
			);

			$question->setErrorMessage(
				$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
			);
			$question->setValidator(function (string|null $answer) use ($dataTypes): string {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (MetadataTypes\DataTypeShort::isValidValue($answer)) {
					return $answer;
				}

				if (
					array_key_exists($answer, $dataTypes)
					&& MetadataTypes\DataTypeShort::isValidValue($dataTypes[$answer])
				) {
					return $dataTypes[$answer];
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			});

			$dataType = strval($io->askQuestion($question));

			return [
				$dataType,
				$switchReading,
			];
		}

		return [
			MetadataTypes\DataTypeShort::DATA_TYPE_STRING,
			$switchReading,
		];
	}

	/**
	 * @return array<int, array<int, string>>|null
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askFormatButtonAction(
		Style\SymfonyStyle $io,
		MetadataTypes\ButtonPayload $payload,
		Entities\ModbusChannel|null $channel = null,
	): array|null
	{
		$defaultReading = $defaultWriting = null;

		$existingProperty = null;

		if ($channel !== null) {
			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(Types\ChannelPropertyIdentifier::VALUE);

			$existingProperty = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);
		}

		$hasSupport = false;

		if ($existingProperty !== null) {
			$format = $existingProperty->getFormat();

			if ($format instanceof MetadataValueObjects\CombinedEnumFormat) {
				foreach ($format->getItems() as $item) {
					if (count($item) === 3) {
						if (
							$item[0] !== null
							&& $item[0]->getValue() instanceof MetadataTypes\SwitchPayload
							&& $item[0]->getValue()->equals($payload)
						) {
							$defaultReading = $item[1]?->toArray();
							$defaultWriting = $item[2]?->toArray();

							$hasSupport = true;
						}
					}
				}
			}
		}

		if ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_PRESSED)) {
			$questionText = $this->translator->translate('//modbus-connector.cmd.devices.questions.button.hasPress');
		} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_RELEASED)) {
			$questionText = $this->translator->translate('//modbus-connector.cmd.devices.questions.button.hasRelease');
		} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_CLICKED)) {
			$questionText = $this->translator->translate('//modbus-connector.cmd.devices.questions.button.hasClick');
		} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_DOUBLE_CLICKED)) {
			$questionText = $this->translator->translate(
				'//modbus-connector.cmd.devices.questions.button.hasDoubleClick',
			);
		} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_TRIPLE_CLICKED)) {
			$questionText = $this->translator->translate(
				'//modbus-connector.cmd.devices.questions.button.hasTripleClick',
			);
		} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_LONG_CLICKED)) {
			$questionText = $this->translator->translate(
				'//modbus-connector.cmd.devices.questions.button.hasLongClick',
			);
		} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_EXTRA_LONG_CLICKED)) {
			$questionText = $this->translator->translate(
				'//modbus-connector.cmd.devices.questions.button.hasExtraLongClick',
			);
		} else {
			throw new Exceptions\InvalidArgument('Provided payload type is not valid');
		}

		$question = new Console\Question\ConfirmationQuestion($questionText, $hasSupport);

		$support = (bool) $io->askQuestion($question);

		if (!$support) {
			return null;
		}

		return [
			[
				MetadataTypes\DataTypeShort::DATA_TYPE_BUTTON,
				strval($payload->getValue()),
			],
			$this->askFormatButtonActionValues($io, $payload, true, $defaultReading),
			$this->askFormatButtonActionValues($io, $payload, false, $defaultWriting),
		];
	}

	/**
	 * @param array<int, bool|float|int|string>|null $default
	 *
	 * @return array<int, string>
	 *
	 * @throws Exceptions\InvalidArgument
	 */
	private function askFormatButtonActionValues(
		Style\SymfonyStyle $io,
		MetadataTypes\ButtonPayload $payload,
		bool $reading,
		array|null $default,
	): array
	{
		assert((is_array($default) && count($default) === 2) || $default === null);

		if ($reading) {
			if ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_PRESSED)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.button.readPressValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.button.readPressValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_RELEASED)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.button.readReleaseValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.button.readReleaseValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_CLICKED)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.button.readClickValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.button.readClickValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_DOUBLE_CLICKED)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.button.readDoubleClickValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.button.readDoubleClickValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_TRIPLE_CLICKED)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.button.readTripleClickValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.button.readTripleClickValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_LONG_CLICKED)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.button.readLongClickValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.button.readLongClickValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_EXTRA_LONG_CLICKED)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.button.readExtraLongClickValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.button.readExtraLongClickValueError',
				);
			} else {
				throw new Exceptions\InvalidArgument('Provided payload type is not valid');
			}
		} else {
			if ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_PRESSED)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.button.writePressValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.button.writePressValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_RELEASED)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.button.writeReleaseValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.button.writeReleaseValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_CLICKED)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.button.writeClickValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.button.writeClickValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_DOUBLE_CLICKED)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.button.writeDoubleClickValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.button.writeDoubleClickValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_TRIPLE_CLICKED)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.button.writeTripleClickValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.button.writeTripleClickValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_LONG_CLICKED)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.button.writeLongClickValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.button.writeLongClickValueError',
				);
			} elseif ($payload->equalsValue(MetadataTypes\ButtonPayload::PAYLOAD_EXTRA_LONG_CLICKED)) {
				$questionText = $this->translator->translate(
					'//modbus-connector.cmd.devices.questions.provide.button.writeExtraLongClickValue',
				);
				$questionError = $this->translator->translate(
					'//modbus-connector.cmd.devices.messages.provide.button.writeExtraLongClickValueError',
				);
			} else {
				throw new Exceptions\InvalidArgument('Provided payload type is not valid');
			}
		}

		$question = new Console\Question\Question($questionText, $default !== null ? $default[1] : null);
		$question->setValidator(function (string|null $answer) use ($io, $questionError): string|null {
			if (trim(strval($answer)) === '') {
				$question = new Console\Question\ConfirmationQuestion(
					$this->translator->translate('//modbus-connector.cmd.devices.questions.skipValue'),
					false,
				);

				$skip = (bool) $io->askQuestion($question);

				if ($skip) {
					return null;
				}

				throw new Exceptions\Runtime($questionError);
			}

			return $answer;
		});

		$switchReading = $io->askQuestion($question);
		assert(is_string($switchReading) || $switchReading === null);

		if ($switchReading === null) {
			return [];
		}

		if (strval(intval($switchReading)) === $switchReading) {
			$dataTypes = [
				MetadataTypes\DataTypeShort::DATA_TYPE_CHAR,
				MetadataTypes\DataTypeShort::DATA_TYPE_UCHAR,
				MetadataTypes\DataTypeShort::DATA_TYPE_SHORT,
				MetadataTypes\DataTypeShort::DATA_TYPE_USHORT,
				MetadataTypes\DataTypeShort::DATA_TYPE_INT,
				MetadataTypes\DataTypeShort::DATA_TYPE_UINT,
				MetadataTypes\DataTypeShort::DATA_TYPE_FLOAT,
			];

			$selected = null;

			if ($default !== null) {
				if ($default[0] === MetadataTypes\DataTypeShort::DATA_TYPE_CHAR) {
					$selected = 0;
				} elseif ($default[0] === MetadataTypes\DataTypeShort::DATA_TYPE_UCHAR) {
					$selected = 1;
				} elseif ($default[0] === MetadataTypes\DataTypeShort::DATA_TYPE_SHORT) {
					$selected = 2;
				} elseif ($default[0] === MetadataTypes\DataTypeShort::DATA_TYPE_USHORT) {
					$selected = 3;
				} elseif ($default[0] === MetadataTypes\DataTypeShort::DATA_TYPE_INT) {
					$selected = 4;
				} elseif ($default[0] === MetadataTypes\DataTypeShort::DATA_TYPE_UINT) {
					$selected = 5;
				} elseif ($default[0] === MetadataTypes\DataTypeShort::DATA_TYPE_FLOAT) {
					$selected = 6;
				}
			}

			$question = new Console\Question\ChoiceQuestion(
				$this->translator->translate('//modbus-connector.cmd.devices.questions.select.valueDataType'),
				$dataTypes,
				$selected,
			);

			$question->setErrorMessage(
				$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
			);
			$question->setValidator(function (string|null $answer) use ($dataTypes): string {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (MetadataTypes\DataTypeShort::isValidValue($answer)) {
					return $answer;
				}

				if (
					array_key_exists($answer, $dataTypes)
					&& MetadataTypes\DataTypeShort::isValidValue($dataTypes[$answer])
				) {
					return $dataTypes[$answer];
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			});

			$dataType = strval($io->askQuestion($question));

			return [
				$dataType,
				$switchReading,
			];
		}

		return [
			MetadataTypes\DataTypeShort::DATA_TYPE_STRING,
			$switchReading,
		];
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\ModbusConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\ModbusConnector::class,
		);
		usort(
			$systemConnectors,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			static fn (Entities\ModbusConnector $a, Entities\ModbusConnector $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($systemConnectors as $connector) {
			$connectors[$connector->getIdentifier()] = $connector->getIdentifier()
				. ($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
		}

		if (count($connectors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.select.connector'),
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer) use ($connectors): Entities\ModbusConnector {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($connectors))) {
				$answer = array_values($connectors)[$answer];
			}

			$identifier = array_search($answer, $connectors, true);

			if ($identifier !== false) {
				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\ModbusConnector::class,
				);

				if ($connector !== null) {
					return $connector;
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$connector = $io->askQuestion($question);
		assert($connector instanceof Entities\ModbusConnector);

		return $connector;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichDevice(
		Style\SymfonyStyle $io,
		Entities\ModbusConnector $connector,
	): Entities\ModbusDevice|null
	{
		$devices = [];

		$findDevicesQuery = new Queries\Entities\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$connectorDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\ModbusDevice::class,
		);
		usort(
			$connectorDevices,
			static fn (Entities\ModbusDevice $a, Entities\ModbusDevice $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($connectorDevices as $device) {
			$devices[$device->getIdentifier()] = $device->getIdentifier()
				. ($device->getName() !== null ? ' [' . $device->getName() . ']' : '');
		}

		if (count($devices) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.select.device'),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connector, $devices): Entities\ModbusDevice {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
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
					$findDeviceQuery->forConnector($connector);

					$device = $this->devicesRepository->findOneBy(
						$findDeviceQuery,
						Entities\ModbusDevice::class,
					);

					if ($device !== null) {
						return $device;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\ModbusDevice);

		return $device;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askWhichRegister(
		Style\SymfonyStyle $io,
		Entities\ModbusDevice $device,
	): Entities\ModbusChannel|null
	{
		$channels = [];

		$findChannelsQuery = new Queries\Entities\FindChannels();
		$findChannelsQuery->forDevice($device);

		$deviceChannels = $this->channelsRepository->findAllBy(
			$findChannelsQuery,
			Entities\ModbusChannel::class,
		);
		usort(
			$deviceChannels,
			static fn (Entities\ModbusChannel $a, Entities\ModbusChannel $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($deviceChannels as $channel) {
			$channels[$channel->getIdentifier()] = sprintf(
				'%s %s, Type: %s, Address: %d',
				$channel->getIdentifier(),
				($channel->getName() !== null ? ' [' . $channel->getName() . ']' : ''),
				strval($channel->getRegisterType()?->getValue()),
				$channel->getAddress(),
			);
		}

		if (count($channels) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//modbus-connector.cmd.devices.questions.select.channel'),
			array_values($channels),
			count($channels) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($device, $channels): Entities\ModbusChannel {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($channels))) {
					$answer = array_values($channels)[$answer];
				}

				$identifier = array_search($answer, $channels, true);

				if ($identifier !== false) {
					$findChannelQuery = new Queries\Entities\FindChannels();
					$findChannelQuery->byIdentifier($identifier);
					$findChannelQuery->forDevice($device);

					$channel = $this->channelsRepository->findOneBy(
						$findChannelQuery,
						Entities\ModbusChannel::class,
					);

					if ($channel !== null) {
						return $channel;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$channel = $io->askQuestion($question);
		assert($channel instanceof Entities\ModbusChannel);

		return $channel;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	private function askConnectorAction(
		Style\SymfonyStyle $io,
		Entities\ModbusConnector $connector,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//modbus-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//modbus-connector.cmd.devices.actions.create.device'),
				1 => $this->translator->translate('//modbus-connector.cmd.devices.actions.update.device'),
				2 => $this->translator->translate('//modbus-connector.cmd.devices.actions.remove.device'),
				3 => $this->translator->translate('//modbus-connector.cmd.devices.actions.list.devices'),
				4 => $this->translator->translate('//modbus-connector.cmd.devices.actions.nothing'),
			],
			4,
		);

		$question->setErrorMessage(
			$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//modbus-connector.cmd.devices.actions.create.device',
			)
			|| $whatToDo === '0'
		) {
			$this->createDevice($io, $connector);

			$this->askConnectorAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//modbus-connector.cmd.devices.actions.update.device',
			)
			|| $whatToDo === '1'
		) {
			$this->editDevice($io, $connector);

			$this->askConnectorAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//modbus-connector.cmd.devices.actions.remove.device',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteDevice($io, $connector);

			$this->askConnectorAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//modbus-connector.cmd.devices.actions.list.devices',
			)
			|| $whatToDo === '3'
		) {
			$this->listDevices($io, $connector);

			$this->askConnectorAction($io, $connector);
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 */
	private function askDeviceAction(
		Style\SymfonyStyle $io,
		Entities\ModbusDevice $device,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//modbus-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//modbus-connector.cmd.devices.actions.create.register'),
				1 => $this->translator->translate('//modbus-connector.cmd.devices.actions.update.register'),
				2 => $this->translator->translate('//modbus-connector.cmd.devices.actions.remove.register'),
				3 => $this->translator->translate('//modbus-connector.cmd.devices.actions.list.registers'),
				4 => $this->translator->translate('//modbus-connector.cmd.devices.actions.nothing'),
			],
			4,
		);

		$question->setErrorMessage(
			$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//modbus-connector.cmd.devices.actions.create.register',
			)
			|| $whatToDo === '0'
		) {
			$this->createRegister($io, $device);

			$this->askDeviceAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//modbus-connector.cmd.devices.actions.update.register',
			)
			|| $whatToDo === '1'
		) {
			$this->editRegister($io, $device);

			$this->askDeviceAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//modbus-connector.cmd.devices.actions.remove.register',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteRegister($io, $device);

			$this->askDeviceAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//modbus-connector.cmd.devices.actions.list.registers',
			)
			|| $whatToDo === '3'
		) {
			$this->listRegisters($io, $device);

			$this->askDeviceAction($io, $device);
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
