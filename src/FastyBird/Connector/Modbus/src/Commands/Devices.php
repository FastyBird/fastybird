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
use FastyBird\Connector\Modbus\Entities;
use FastyBird\Connector\Modbus\Exceptions;
use FastyBird\Connector\Modbus\Helpers;
use FastyBird\Connector\Modbus\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette\Utils;
use Psr\Log;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function array_key_exists;
use function array_key_first;
use function array_search;
use function array_values;
use function assert;
use function count;
use function filter_var;
use function intval;
use function is_array;
use function is_int;
use function preg_match;
use function range;
use function sprintf;
use function strval;
use const FILTER_FLAG_IPV4;

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

	private const CHOICE_QUESTION_CREATE_DEVICE = 'Create new connector device';

	private const CHOICE_QUESTION_EDIT_DEVICE = 'Edit existing connector device';

	private const CHOICE_QUESTION_DELETE_DEVICE = 'Delete existing connector device';

	private const CHOICE_QUESTION_CHANNEL_DISCRETE_INPUT = 'Discrete Input';

	private const CHOICE_QUESTION_CHANNEL_COIL = 'Coil';

	private const CHOICE_QUESTION_CHANNEL_INPUT_REGISTER = 'Input Register';

	private const CHOICE_QUESTION_CHANNEL_HOLDING_REGISTER = 'Holding Register';
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private const MATCH_IP_ADDRESS_PORT = '/^(?P<address>((?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])[.]){3}(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])):(?P<port>[0-9]{1,5})$/';

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly Helpers\Channel $channelHelper,
		private readonly Persistence\ManagerRegistry $managerRegistry,
		Log\LoggerInterface|null $logger = null,
		string|null $name = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();

		parent::__construct($name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		$this
			->setName(self::NAME)
			->setDescription('Modbus devices management')
			->setDefinition(
				new Input\InputDefinition([
					new Input\InputOption(
						'no-confirm',
						null,
						Input\InputOption::VALUE_NONE,
						'Do not ask for any confirmation',
					),
				]),
			);
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
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title('Modbus connector - devices management');

		$io->note('This action will create|update|delete connector device.');

		if ($input->getOption('no-confirm') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				'Would you like to continue?',
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				return Console\Command\Command::SUCCESS;
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			'What would you like to do?',
			[
				0 => self::CHOICE_QUESTION_CREATE_DEVICE,
				1 => self::CHOICE_QUESTION_EDIT_DEVICE,
				2 => self::CHOICE_QUESTION_DELETE_DEVICE,
			],
		);

		$question->setErrorMessage('Selected answer: "%s" is not valid.');

		$whatToDo = $io->askQuestion($question);

		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			return Console\Command\Command::SUCCESS;
		}

		if ($whatToDo === self::CHOICE_QUESTION_CREATE_DEVICE) {
			$this->createNewDevice($io, $connector);

		} elseif ($whatToDo === self::CHOICE_QUESTION_EDIT_DEVICE) {
			$this->editExistingDevice($io, $connector);

		} elseif ($whatToDo === self::CHOICE_QUESTION_DELETE_DEVICE) {
			$this->deleteExistingDevice($io, $connector);
		}

		return Console\Command\Command::SUCCESS;
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
	private function createNewDevice(Style\SymfonyStyle $io, Entities\ModbusConnector $connector): void
	{
		$question = new Console\Question\Question('Provide device identifier');

		$question->setValidator(function ($answer) {
			if ($answer !== null) {
				$findDeviceQuery = new DevicesQueries\FindDevices();
				$findDeviceQuery->byIdentifier($answer);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\ModbusDevice::class) !== null
				) {
					throw new Exceptions\Runtime('This identifier is already used');
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'modbus-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findDeviceQuery = new DevicesQueries\FindDevices();
				$findDeviceQuery->byIdentifier($identifier);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\ModbusDevice::class) === null
				) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error('Device identifier have to provided');

			return;
		}

		$question = new Console\Question\Question('Provide device name');

		$name = $io->askQuestion($question);

		$address = $ipAddress = $port = $unitId = null;

		if ($connector->getClientMode()->equalsValue(Types\ClientMode::MODE_RTU)) {
			$question = new Console\Question\Question('Provide device hardware address');
			$question->setValidator(static function ($answer) use ($connector) {
				if (strval(intval($answer)) !== strval($answer)) {
					throw new Exceptions\Runtime('Device hardware address have to be numeric');
				}

				foreach ($connector->getDevices() as $device) {
					assert($device instanceof Entities\ModbusDevice);

					if ($device->getAddress() === intval($answer)) {
						throw new Exceptions\InvalidArgument('Device hardware address already taken');
					}
				}

				return $answer;
			});

			$address = intval($io->askQuestion($question));
		}

		if ($connector->getClientMode()->equalsValue(Types\ClientMode::MODE_TCP)) {
			$question = new Console\Question\Question('Provide device IP address');
			$question->setValidator(static function ($answer) use (&$port) {
				if (!filter_var(strval($answer), FILTER_FLAG_IPV4)) {
					if (
						preg_match(self::MATCH_IP_ADDRESS_PORT, strval($answer), $matches) === 1
						&& array_key_exists('address', $matches)
						&& array_key_exists('port', $matches)
					) {
						$port = intval($matches['port']);

						return $matches['address'];
					}

					throw new Exceptions\Runtime('Provided device IP address is not valid');
				}

				return $answer;
			});

			$ipAddress = strval($io->askQuestion($question));

			if ($port === null) {
				$question = new Console\Question\Question('Provide device IP address port', 502);
				$question->setValidator(static function ($answer) {
					if (strval(intval($answer)) !== strval($answer)) {
						throw new Exceptions\Runtime('Provided device IP address port is not valid');
					}

					return $answer;
				});

				$port = intval($io->askQuestion($question));
			}

			$question = new Console\Question\Question('Provide device unit identifier', 0);
			$question->setValidator(static function ($answer) use ($connector) {
				if (strval(intval($answer)) !== strval($answer)) {
					throw new Exceptions\Runtime('Device unit identifier have to be numeric');
				}

				foreach ($connector->getDevices() as $device) {
					assert($device instanceof Entities\ModbusDevice);

					if ($device->getUnitId() === intval($answer)) {
						throw new Exceptions\InvalidArgument('Device unit identifier already taken');
					}
				}

				return $answer;
			});

			$unitId = intval($io->askQuestion($question));
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->create(Utils\ArrayHash::from([
				'entity' => Entities\ModbusDevice::class,
				'connector' => $connector,
				'identifier' => $identifier,
				'name' => $name === '' ? null : $name,
			]));
			assert($device instanceof Entities\ModbusDevice);

			if ($connector->getClientMode()->equalsValue(Types\ClientMode::MODE_RTU)) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_ADDRESS,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
					'value' => $address,
					'device' => $device,
				]));
			}

			if ($connector->getClientMode()->equalsValue(Types\ClientMode::MODE_TCP)) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $ipAddress,
					'device' => $device,
				]));

				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS_PORT,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
					'value' => $port,
					'device' => $device,
				]));

				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_UNIT_ID,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
					'value' => $unitId,
					'device' => $device,
				]));
			}
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'group' => 'cmd',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
				],
			);

			$io->error('Something went wrong, device could not be created. Error was logged.');

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		$question = new Console\Question\ConfirmationQuestion(
			'Would you like to configure device registers?',
			false,
		);

		$createRegisters = (bool) $io->askQuestion($question);

		if (!$createRegisters) {
			return;
		}

		$this->createRegister($io, $device);
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
	private function editExistingDevice(Style\SymfonyStyle $io, Entities\ModbusConnector $connector): void
	{
		$io->newLine();

		$devices = [];

		$findDevicesQuery = new DevicesQueries\FindDevices();
		$findDevicesQuery->forConnector($connector);

		foreach ($this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\ModbusDevice::class,
		) as $device) {
			assert($device instanceof Entities\ModbusDevice);

			$devices[$device->getIdentifier()] = $device->getIdentifier()
				. ($device->getName() !== null ? ' [' . $device->getName() . ']' : '');
		}

		if (count($devices) === 0) {
			$io->warning('No devices registered in Modbus connector');

			$question = new Console\Question\ConfirmationQuestion(
				'Would you like to create new device in connector?',
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createNewDevice($io, $connector);
			}

			return;
		}

		$question = new Console\Question\ChoiceQuestion(
			'Please select device to edit',
			array_values($devices),
		);

		$question->setErrorMessage('Selected device: "%s" is not valid.');

		$deviceIdentifier = array_search($io->askQuestion($question), $devices, true);

		if ($deviceIdentifier === false) {
			$io->error('Something went wrong, device could not be loaded');

			$this->logger->alert(
				'Could not read device identifier from console answer',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'group' => 'cmd',
				],
			);

			return;
		}

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byIdentifier($deviceIdentifier);

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\ModbusDevice::class);

		if ($device === null) {
			$io->error('Something went wrong, device could not be loaded');

			$this->logger->alert(
				'Device was not found',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'group' => 'cmd',
				],
			);

			return;
		}

		assert($device instanceof Entities\ModbusDevice);

		$question = new Console\Question\Question('Provide device name', $device->getName());

		$name = $io->askQuestion($question);

		$address = $ipAddress = $port = $unitId = null;

		$addressProperty = $device->findProperty(Types\DevicePropertyIdentifier::IDENTIFIER_ADDRESS);
		$ipAddressProperty = $device->findProperty(Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS);
		$portProperty = $device->findProperty(Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS_PORT);
		$unitIdProperty = $device->findProperty(Types\DevicePropertyIdentifier::IDENTIFIER_UNIT_ID);

		if ($connector->getClientMode()->equalsValue(Types\ClientMode::MODE_RTU)) {
			$question = new Console\Question\Question('Provide device hardware address', $device->getAddress());
			$question->setValidator(static function ($answer) use ($connector, $device) {
				if (strval(intval($answer)) !== strval($answer)) {
					throw new Exceptions\Runtime('Device hardware address have to be numeric');
				}

				foreach ($connector->getDevices() as $connectorDevice) {
					assert($connectorDevice instanceof Entities\ModbusDevice);

					if (
						$connectorDevice->getAddress() === intval($answer)
						&& !$connectorDevice->getId()->equals($device->getId())
					) {
						throw new Exceptions\InvalidArgument('Device hardware address already taken');
					}
				}

				return $answer;
			});

			$address = intval($io->askQuestion($question));
		}

		if ($connector->getClientMode()->equalsValue(Types\ClientMode::MODE_TCP)) {
			$question = new Console\Question\Question('Provide device IP address', $device->getIpAddress());
			$question->setValidator(static function ($answer) use (&$port) {
				if (!filter_var(strval($answer), FILTER_FLAG_IPV4)) {
					if (
						preg_match(self::MATCH_IP_ADDRESS_PORT, strval($answer), $matches) === 1
						&& array_key_exists('address', $matches)
						&& array_key_exists('port', $matches)
					) {
						$port = intval($matches['port']);

						return $matches['address'];
					}

					throw new Exceptions\Runtime('Provided device IP address is not valid');
				}

				return $answer;
			});

			$ipAddress = strval($io->askQuestion($question));

			if ($port === null) {
				$question = new Console\Question\Question('Provide device IP address port', $device->getPort());
				$question->setValidator(static function ($answer) {
					if (strval(intval($answer)) !== strval($answer)) {
						throw new Exceptions\Runtime('Provided device IP address port is not valid');
					}

					return $answer;
				});

				$port = intval($io->askQuestion($question));
			}

			$question = new Console\Question\Question('Provide device unit identifier', $device->getUnitId());
			$question->setValidator(static function ($answer) use ($connector, $device) {
				if (strval(intval($answer)) !== strval($answer)) {
					throw new Exceptions\Runtime('Device unit identifier have to be numeric');
				}

				foreach ($connector->getDevices() as $connectorDevice) {
					assert($connectorDevice instanceof Entities\ModbusDevice);

					if (
						$connectorDevice->getUnitId() === intval($answer)
						&& !$connectorDevice->getId()->equals($device->getId())
					) {
						throw new Exceptions\InvalidArgument('Device unit identifier already taken');
					}
				}

				return $answer;
			});

			$unitId = intval($io->askQuestion($question));
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->update($device, Utils\ArrayHash::from([
				'name' => $name === '' ? null : $name,
			]));

			if ($connector->getClientMode()->equalsValue(Types\ClientMode::MODE_RTU)) {
				if ($addressProperty === null) {
					$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Devices\Properties\Variable::class,
						'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_ADDRESS,
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

			if ($connector->getClientMode()->equalsValue(Types\ClientMode::MODE_TCP)) {
				if ($ipAddressProperty === null) {
					$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Devices\Properties\Variable::class,
						'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
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
						'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
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
						'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_UNIT_ID,
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

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(sprintf(
				'Device "%s" was successfully updated',
				$device->getName() ?? $device->getIdentifier(),
			));
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'group' => 'cmd',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
				],
			);

			$io->error('Something went wrong, device could not be updated. Error was logged.');
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		assert($device instanceof Entities\ModbusDevice);

		if (count($device->getChannels()) > 0) {
			$question = new Console\Question\ConfirmationQuestion(
				'Would you like to edit device registers?',
				false,
			);

			$edit = (bool) $io->askQuestion($question);

			if ($edit) {
				$this->editRegister($io, $device);

				return;
			}
		}

		$question = new Console\Question\ConfirmationQuestion(
			'Would you like to configure new register to device?',
			false,
		);

		$create = (bool) $io->askQuestion($question);

		if ($create) {
			$this->createRegister($io, $device, true);
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function deleteExistingDevice(Style\SymfonyStyle $io, Entities\ModbusConnector $connector): void
	{
		$io->newLine();

		$devices = [];

		$findDevicesQuery = new DevicesQueries\FindDevices();
		$findDevicesQuery->forConnector($connector);

		foreach ($this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\ModbusDevice::class,
		) as $device) {
			assert($device instanceof Entities\ModbusDevice);

			$devices[$device->getIdentifier()] = $device->getIdentifier()
				. ($device->getName() !== null ? ' [' . $device->getName() . ']' : '');
		}

		if (count($devices) === 0) {
			$io->info('No Modbus devices registered in selected connector');

			return;
		}

		$question = new Console\Question\ChoiceQuestion(
			'Please select device to remove',
			array_values($devices),
		);

		$question->setErrorMessage('Selected device: "%s" is not valid.');

		$deviceIdentifier = array_search($io->askQuestion($question), $devices, true);

		if ($deviceIdentifier === false) {
			$io->error('Something went wrong, device could not be loaded');

			$this->logger->alert(
				'Device identifier was not able to get from answer',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'group' => 'cmd',
				],
			);

			return;
		}

		$findDeviceQuery = new DevicesQueries\FindDevices();
		$findDeviceQuery->byIdentifier($deviceIdentifier);

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\ModbusDevice::class);

		if ($device === null) {
			$io->error('Something went wrong, device could not be loaded');

			$this->logger->alert(
				'Device was not found',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'group' => 'cmd',
				],
			);

			return;
		}

		$question = new Console\Question\ConfirmationQuestion(
			'Would you like to continue?',
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

			$io->success(sprintf(
				'Device "%s" was successfully removed',
				$device->getName() ?? $device->getIdentifier(),
			));
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'group' => 'cmd',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
				],
			);

			$io->error('Something went wrong, device could not be removed. Error was logged.');
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function createRegister(Style\SymfonyStyle $io, Entities\ModbusDevice $device, bool $editMode = false): void
	{
		$type = $this->askRegisterType($io);

		$addresses = $this->askRegisterAddress($io, $type, $device);

		if (is_int($addresses)) {
			$addresses = [$addresses, $addresses];
		}

		$name = $this->askRegisterName($io);

		$dataType = $this->askRegisterDataType($io, $type);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			foreach (range($addresses[0], $addresses[1], 1) as $address) {
				$channel = $this->channelsManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Channel::class,
					'identifier' => $type . '_' . $address,
					'name' => $name,
					'device' => $device,
				]));

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Types\ChannelPropertyIdentifier::IDENTIFIER_ADDRESS,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
					'value' => $address,
					'channel' => $channel,
				]));

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Types\ChannelPropertyIdentifier::IDENTIFIER_TYPE,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $type->getValue(),
					'channel' => $channel,
				]));

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::IDENTIFIER_VALUE,
					'dataType' => $dataType,
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
				$io->success('Device register was successfully created');
			} else {
				$io->success('Device registers were successfully created');
			}
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'group' => 'cmd',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
				],
			);

			$io->error('Something went wrong, device register could not be created. Error was logged.');

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		$question = new Console\Question\ConfirmationQuestion(
			'Would you like to add another register(s)?',
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		if ($editMode && count($device->getChannels()) > 1) {
			$question = new Console\Question\ConfirmationQuestion(
				'Would you like to edit device registers?',
				false,
			);

			$edit = (bool) $io->askQuestion($question);

			if ($edit) {
				$this->editRegister($io, $device);

				return;
			}
		}

		$question = new Console\Question\ConfirmationQuestion(
			'Would you like to configure another register to device?',
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
		$io->newLine();

		$channels = [];

		$findChannelsQuery = new DevicesQueries\FindChannels();
		$findChannelsQuery->forDevice($device);

		foreach ($this->channelsRepository->findAllBy($findChannelsQuery) as $channel) {
			$type = Types\ChannelType::get($this->channelHelper->getConfiguration(
				$channel,
				Types\ChannelPropertyIdentifier::get(Types\ChannelPropertyIdentifier::IDENTIFIER_TYPE),
			));

			$addresses = $this->channelHelper->getConfiguration(
				$channel,
				Types\ChannelPropertyIdentifier::get(Types\ChannelPropertyIdentifier::IDENTIFIER_ADDRESS),
			);

			$channels[$channel->getIdentifier()] = sprintf(
				'%s %s, Type: %s, Address: %d',
				$channel->getIdentifier(),
				($channel->getName() !== null ? ' [' . $channel->getName() . ']' : ''),
				strval($type->getValue()),
				intval($addresses),
			);
		}

		if (count($channels) === 0) {
			$io->warning('This device has not configured any register');

			$question = new Console\Question\ConfirmationQuestion(
				'Would you like to configure new register to device?',
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createRegister($io, $device, true);
			}

			return;
		}

		$question = new Console\Question\ChoiceQuestion(
			'Please select register to configure',
			array_values($channels),
		);

		$question->setErrorMessage('Selected register: "%s" is not valid.');

		$registerIdentifier = array_search($io->askQuestion($question), $channels, true);

		if ($registerIdentifier === false) {
			$io->error('Something went wrong, register could not be loaded');

			$this->logger->alert(
				'Could not read register identifier from console answer',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'group' => 'cmd',
				],
			);

			return;
		}

		$findRegisterQuery = new DevicesQueries\FindChannels();
		$findRegisterQuery->forDevice($device);

		$channel = $this->channelsRepository->findOneBy($findChannelsQuery);

		if ($channel === null) {
			$io->error('Something went wrong, channel could not be loaded');

			$this->logger->alert(
				'Channel was not found',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'group' => 'cmd',
				],
			);

			return;
		}

		$type = $this->askRegisterType($io, $channel);

		$address = $this->askRegisterAddress($io, $type, $device, $channel);

		if (is_array($address)) {
			$address = $address[0];
		}

		$name = $this->askRegisterName($io, $channel);

		$dataType = $this->askRegisterDataType($io, $type, $channel);

		$addressProperty = $channel->findProperty(Types\ChannelPropertyIdentifier::IDENTIFIER_ADDRESS);

		$typeProperty = $channel->findProperty(Types\ChannelPropertyIdentifier::IDENTIFIER_TYPE);

		$valueProperty = $channel->findProperty(Types\ChannelPropertyIdentifier::IDENTIFIER_VALUE);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$channel = $this->channelsManager->update($channel, Utils\ArrayHash::from([
				'name' => $name === '' ? null : $name,
			]));

			if ($addressProperty === null) {
				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Types\ChannelPropertyIdentifier::IDENTIFIER_ADDRESS,
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
					'identifier' => Types\ChannelPropertyIdentifier::IDENTIFIER_TYPE,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $type->getValue(),
					'channel' => $channel,
				]));
			} else {
				$this->channelsPropertiesManager->update($typeProperty, Utils\ArrayHash::from([
					'value' => $type->getValue(),
				]));
			}

			if ($valueProperty === null) {
				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Types\ChannelPropertyIdentifier::IDENTIFIER_VALUE,
					'dataType' => $dataType,
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
					'settable' => (
						$type->equalsValue(Types\ChannelType::COIL)
						|| $type->equalsValue(Types\ChannelType::HOLDING_REGISTER)
					),
				]));
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(sprintf(
				'Register "%s" was successfully updated',
				$channel->getName() ?? $channel->getIdentifier(),
			));
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'initialize-cmd',
					'group' => 'cmd',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
				],
			);

			$io->error('Something went wrong, register could not be updated. Error was logged.');
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		if (count($device->getChannels()) > 1) {
			$question = new Console\Question\ConfirmationQuestion(
				'Would you like to edit device registers?',
				false,
			);

			$edit = (bool) $io->askQuestion($question);

			if ($edit) {
				$this->editRegister($io, $device);

				return;
			}
		}

		$question = new Console\Question\ConfirmationQuestion(
			'Would you like to configure new register to device?',
			false,
		);

		$create = (bool) $io->askQuestion($question);

		if ($create) {
			$this->createRegister($io, $device, true);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askRegisterType(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Channel|null $channel = null,
	): Types\ChannelType
	{
		if ($channel !== null) {
			$type = Types\ChannelType::get($this->channelHelper->getConfiguration(
				$channel,
				Types\ChannelPropertyIdentifier::get(Types\ChannelPropertyIdentifier::IDENTIFIER_TYPE),
			));

			$default = 0;

			if ($type->equalsValue(Types\ChannelType::COIL)) {
				$default = 1;
			} elseif ($type->equalsValue(Types\ChannelType::INPUT_REGISTER)) {
				$default = 2;
			} elseif ($type->equalsValue(Types\ChannelType::HOLDING_REGISTER)) {
				$default = 3;
			}

			$question = new Console\Question\ChoiceQuestion(
				'Change register type?',
				[
					self::CHOICE_QUESTION_CHANNEL_DISCRETE_INPUT,
					self::CHOICE_QUESTION_CHANNEL_COIL,
					self::CHOICE_QUESTION_CHANNEL_INPUT_REGISTER,
					self::CHOICE_QUESTION_CHANNEL_HOLDING_REGISTER,
				],
				$default,
			);
		} else {
			$question = new Console\Question\ChoiceQuestion(
				'What type of device register you would like to add?',
				[
					self::CHOICE_QUESTION_CHANNEL_DISCRETE_INPUT,
					self::CHOICE_QUESTION_CHANNEL_COIL,
					self::CHOICE_QUESTION_CHANNEL_INPUT_REGISTER,
					self::CHOICE_QUESTION_CHANNEL_HOLDING_REGISTER,
				],
				0,
			);
		}

		$question->setErrorMessage('Selected answer: "%s" is not valid.');

		$mode = $io->askQuestion($question);

		if ($mode === self::CHOICE_QUESTION_CHANNEL_DISCRETE_INPUT) {
			return Types\ChannelType::get(Types\ChannelType::DISCRETE_INPUT);
		}

		if ($mode === self::CHOICE_QUESTION_CHANNEL_COIL) {
			return Types\ChannelType::get(Types\ChannelType::COIL);
		}

		if ($mode === self::CHOICE_QUESTION_CHANNEL_INPUT_REGISTER) {
			return Types\ChannelType::get(Types\ChannelType::INPUT_REGISTER);
		}

		if ($mode === self::CHOICE_QUESTION_CHANNEL_HOLDING_REGISTER) {
			return Types\ChannelType::get(Types\ChannelType::HOLDING_REGISTER);
		}

		throw new Exceptions\InvalidState('Unknown channel type selected');
	}

	/**
	 * @return int|array<int>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askRegisterAddress(
		Style\SymfonyStyle $io,
		Types\ChannelType $type,
		Entities\ModbusDevice $device,
		DevicesEntities\Channels\Channel|null $channel = null,
	): int|array
	{
		$address = null;

		if ($channel !== null) {
			$address = intval($this->channelHelper->getConfiguration(
				$channel,
				Types\ChannelPropertyIdentifier::get(Types\ChannelPropertyIdentifier::IDENTIFIER_ADDRESS),
			));
		}

		$question = new Console\Question\Question(
			(
				$channel !== null ?
				'Provide register address. It have to be number'
				: 'Provide register address. It could be single number or range like 1-2'
			),
			$address,
		);
		$question->setValidator(function ($answer) use ($type, $device, $channel) {
			if (strval(intval($answer)) === strval($answer)) {
				foreach ($device->getChannels() as $deviceChannel) {
					if (Utils\Strings::startsWith($deviceChannel->getIdentifier(), strval($type->getValue()))) {
						$address = $this->channelHelper->getConfiguration(
							$deviceChannel,
							Types\ChannelPropertyIdentifier::get(
								Types\ChannelPropertyIdentifier::IDENTIFIER_ADDRESS,
							),
						);

						if (
							intval($address) === intval($answer)
							&& (
								$channel === null
								|| !$channel->getId()->equals($deviceChannel->getId())
							)
						) {
							throw new Exceptions\Runtime('Provided register address is already taken');
						}
					}
				}

				return intval($answer);
			}

			if ($channel === null) {
				if (
					preg_match('/^[0-9]-[0-9]$/', strval($answer), $matches) === 1
					&& count($matches) === 2
				) {
					$start = intval($matches[0]);
					$end = intval($matches[1]);

					if ($start < $end) {
						foreach ($device->getChannels() as $deviceChannel) {
							$address = $this->channelHelper->getConfiguration(
								$deviceChannel,
								Types\ChannelPropertyIdentifier::get(
									Types\ChannelPropertyIdentifier::IDENTIFIER_ADDRESS,
								),
							);

							if (intval($address) >= $start && intval($address) <= $end) {
								throw new Exceptions\Runtime(sprintf(
									'Provided register address %d from provided range is already taken',
									intval($address),
								));
							}
						}

						return [$start, $end];
					}
				}

				throw new Exceptions\Runtime('Channel address have to be numeric or interval definition');
			}

			throw new Exceptions\Runtime('Channel address have to be numeric value');
		});

		/** @var int|array<int> $address */
		$address = $io->askQuestion($question);

		return $address;
	}

	private function askRegisterName(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Channel|null $channel = null,
	): string|null
	{
		$question = new Console\Question\Question('Provide channel name (optional)', $channel?->getName());

		$name = strval($io->askQuestion($question));

		return $name === '' ? null : $name;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askRegisterDataType(
		Style\SymfonyStyle $io,
		Types\ChannelType $type,
		DevicesEntities\Channels\Channel|null $channel = null,
	): MetadataTypes\DataType
	{
		$default = $existingType = null;

		if ($channel !== null) {
			$existingType = Types\ChannelType::get($this->channelHelper->getConfiguration(
				$channel,
				Types\ChannelPropertyIdentifier::get(Types\ChannelPropertyIdentifier::IDENTIFIER_TYPE),
			));
		}

		if ($type->equalsValue(Types\ChannelType::DISCRETE_INPUT)) {
			$dataTypes = [
				MetadataTypes\DataType::DATA_TYPE_BOOLEAN,
				MetadataTypes\DataType::DATA_TYPE_BUTTON,
			];

			if (
				$existingType !== null
				&& $existingType->equalsValue(Types\ChannelType::DISCRETE_INPUT)
			) {
				switch ($channel->findProperty(
					Types\ChannelPropertyIdentifier::IDENTIFIER_VALUE,
				)?->getDataType()->getValue()) {
					case MetadataTypes\DataType::DATA_TYPE_BOOLEAN:
						$default = 0;

						break;
					case MetadataTypes\DataType::DATA_TYPE_BUTTON:
						$default = 1;

						break;
				}
			}
		} elseif ($type->equalsValue(Types\ChannelType::COIL)) {
			$dataTypes = [
				MetadataTypes\DataType::DATA_TYPE_BOOLEAN,
				MetadataTypes\DataType::DATA_TYPE_SWITCH,
			];

			if (
				$existingType !== null
				&& $existingType->equalsValue(Types\ChannelType::COIL)
			) {
				switch ($channel->findProperty(
					Types\ChannelPropertyIdentifier::IDENTIFIER_VALUE,
				)?->getDataType()->getValue()) {
					case MetadataTypes\DataType::DATA_TYPE_BOOLEAN:
						$default = 0;

						break;
					case MetadataTypes\DataType::DATA_TYPE_SWITCH:
						$default = 1;

						break;
				}
			}
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

			if (
				$existingType !== null
				&& (
					$existingType->equalsValue(Types\ChannelType::HOLDING_REGISTER)
					|| $existingType->equalsValue(Types\ChannelType::INPUT_REGISTER)
				)
			) {
				switch ($channel->findProperty(
					Types\ChannelPropertyIdentifier::IDENTIFIER_VALUE,
				)?->getDataType()->getValue()) {
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
				}
			}
		} else {
			throw new Exceptions\InvalidArgument('Unknown channel type');
		}

		$question = new Console\Question\ChoiceQuestion(
			'What type of data type this register has',
			$dataTypes,
			$default ?? $dataTypes[0],
		);
		$question->setValidator(static function ($answer) {
			if (MetadataTypes\DataType::isValidValue($answer)) {
				return $answer;
			}

			throw new Exceptions\Runtime('Selected data type is not valid');
		});

		$question->setErrorMessage('Selected answer: "%s" is not valid.');

		$dataType = $io->askQuestion($question);

		return MetadataTypes\DataType::get($dataType);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\ModbusConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new DevicesQueries\FindConnectors();

		foreach ($this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\ModbusConnector::class,
		) as $connector) {
			assert($connector instanceof Entities\ModbusConnector);

			$connectors[$connector->getIdentifier()] = $connector->getIdentifier()
				. ($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
		}

		if (count($connectors) === 0) {
			$io->warning('No connectors registered in system');

			return null;
		}

		if (count($connectors) === 1) {
			$connectorIdentifier = array_key_first($connectors);

			$findConnectorQuery = new DevicesQueries\FindConnectors();
			$findConnectorQuery->byIdentifier($connectorIdentifier);

			$connector = $this->connectorsRepository->findOneBy(
				$findConnectorQuery,
				Entities\ModbusConnector::class,
			);

			if ($connector === null) {
				$io->warning('Connector was not found in system');

				return null;
			}

			assert($connector instanceof Entities\ModbusConnector);

			$question = new Console\Question\ConfirmationQuestion(
				sprintf(
					'Would you like to manage device for: %s connector ?',
					$connector->getIdentifier() . ($connector->getName() !== null ? ' [' . $connector->getName() . ']' : ''),
				),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				return null;
			}

			return $connector;
		}

		$question = new Console\Question\ChoiceQuestion(
			'Please select connector under which you want to manage devices',
			array_values($connectors),
		);

		$question->setErrorMessage('Selected connector: %s is not valid.');

		$connectorIdentifierKey = array_search($io->askQuestion($question), $connectors, true);

		if ($connectorIdentifierKey === false) {
			$io->error('Something went wrong, connector could not be loaded');

			$this->logger->alert(
				'Could not read connector identifier from console answer',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'devices-cmd',
					'group' => 'cmd',
				],
			);

			return null;
		}

		$findConnectorQuery = new DevicesQueries\FindConnectors();
		$findConnectorQuery->byIdentifier($connectorIdentifierKey);

		$connector = $this->connectorsRepository->findOneBy(
			$findConnectorQuery,
			Entities\ModbusConnector::class,
		);

		assert($connector instanceof Entities\ModbusConnector || $connector === null);

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

		throw new Exceptions\Runtime('Entity manager could not be loaded');
	}

}
