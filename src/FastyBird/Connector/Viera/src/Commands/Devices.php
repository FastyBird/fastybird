<?php declare(strict_types = 1);

/**
 * Devices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           18.06.23
 */

namespace FastyBird\Connector\Viera\Commands;

use Doctrine\DBAL;
use Doctrine\Persistence;
use FastyBird\Connector\Viera;
use FastyBird\Connector\Viera\API;
use FastyBird\Connector\Viera\Entities;
use FastyBird\Connector\Viera\Exceptions;
use FastyBird\Connector\Viera\Helpers;
use FastyBird\Connector\Viera\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use InvalidArgumentException;
use Nette\Utils;
use Psr\Log;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function array_key_exists;
use function array_map;
use function array_search;
use function array_values;
use function assert;
use function count;
use function preg_match;
use function React\Async\await;
use function sprintf;
use function strval;
use function usort;

/**
 * Connector devices management command
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Devices extends Console\Command\Command
{

	public const NAME = 'fb:viera-connector:devices';
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private const MATCH_IP_ADDRESS = '/^((?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])[.]){3}(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/';

	private const CHOICE_QUESTION_CREATE_DEVICE = 'Create new connector device';

	private const CHOICE_QUESTION_EDIT_DEVICE = 'Edit existing connector device';

	private const CHOICE_QUESTION_DELETE_DEVICE = 'Delete existing connector device';

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly API\TelevisionApiFactory $televisionApiFactory,
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $devicePropertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $devicePropertiesManager,
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
			->setDescription('Viera devices management');
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentException
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title('Viera connector - devices management');

		$io->note('This action will create|update|delete connector device.');

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

		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->warning('No Viera connectors registered in system');

			return Console\Command\Command::SUCCESS;
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
	 * @throws Exceptions\Runtime
	 */
	private function createNewDevice(Style\SymfonyStyle $io, Entities\VieraConnector $connector): void
	{
		$question = new Console\Question\Question('Provide device identifier');

		$question->setValidator(function (string|null $answer) {
			if ($answer !== '' && $answer !== null) {
				$findDeviceQuery = new DevicesQueries\FindDevices();
				$findDeviceQuery->byIdentifier($answer);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\VieraDevice::class) !== null
				) {
					throw new Exceptions\Runtime('This identifier is already used');
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'viera-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findDeviceQuery = new DevicesQueries\FindDevices();
				$findDeviceQuery->byIdentifier($identifier);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\VieraDevice::class) === null
				) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error('Device identifier have to provided');

			return;
		}

		$identifier = strval($identifier);

		try {
			$ipAddress = $this->askIpAddress($io);

			$televisionApi = $this->televisionApiFactory->create(
				$identifier,
				$ipAddress,
				Viera\Constants::DEFAULT_PORT,
			);
			$televisionApi->connect();

			try {
				$isOnline = await($televisionApi->livenessProbe());
			} catch (Throwable $ex) {
				$this->logger->error(
					'Checking TV status failed',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
						'type' => 'devices-cmd',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
					],
				);

				$io->error('Something went wrong, device could not be created. Error was logged.');

				return;
			}

			if ($isOnline === false) {
				$io->error(sprintf('The provided IP: %s address is unreachable.', $ipAddress));

				return;
			}

			$specs = $televisionApi->getSpecs(false);

			$authorization = null;

			if ($specs->isRequiresEncryption()) {
				try {
					$isTurnedOn = await($televisionApi->isTurnedOn());
				} catch (Throwable $ex) {
					$this->logger->error(
						'Checking screen status failed',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
							'type' => 'devices-cmd',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
						],
					);

					$io->error('Something went wrong, device could not be created. Error was logged.');

					return;
				}

				if ($isTurnedOn === false) {
					$io->warning(
						'It looks like your TV is not turned on. It is possible that the pairing could not be finished.',
					);

					$question = new Console\Question\ConfirmationQuestion(
						'Would you like to continue?',
						false,
					);

					$continue = (bool) $io->askQuestion($question);

					if (!$continue) {
						return;
					}
				}

				$challengeKey = $televisionApi->requestPinCode(
					$connector->getName() ?? $connector->getIdentifier(),
					false,
				);

				$pinCode = $this->askPinCode($io);

				$authorization = $televisionApi->authorizePinCode($pinCode, $challengeKey, false);
			}

			$televisionApi = $this->televisionApiFactory->create(
				$identifier,
				$ipAddress,
				Viera\Constants::DEFAULT_PORT,
				$authorization?->getAppId(),
				$authorization?->getEncryptionKey(),
			);
			$televisionApi->connect();

			$apps = $televisionApi->getApps(false);

		} catch (Exceptions\TelevisionApiCall | Exceptions\Encrypt | Exceptions\Decrypt $ex) {
			$this->logger->error(
				'Calling television api failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error('Something went wrong, device could not be created. Error was logged.');

			return;

		} catch (Throwable $ex) {
			$this->logger->error(
				'Unhandled error occur',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error('Something went wrong, device could not be created. Error was logged.');

			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->create(Utils\ArrayHash::from([
				'entity' => Entities\VieraDevice::class,
				'connector' => $connector,
				'identifier' => $identifier,
				'name' => $specs->getFriendlyName() ?? $specs->getModelName(),
			]));
			assert($device instanceof Entities\VieraDevice);

			$this->devicePropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $ipAddress,
				'device' => $device,
			]));

			$this->devicePropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MODEL),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $specs->getModelNumber(),
				'device' => $device,
			]));

			$this->devicePropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MANUFACTURER,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_HARDWARE_MANUFACTURER),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $specs->getManufacturer(),
				'device' => $device,
			]));

			$this->devicePropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_ENCRYPTED,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_ENCRYPTED),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
				'value' => $specs->isRequiresEncryption(),
				'device' => $device,
			]));

			$this->devicePropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_SERIAL_NUMBER,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_SERIAL_NUMBER),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $specs->isRequiresEncryption(),
				'device' => $device,
			]));

			$this->devicePropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_APP_ID,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_APP_ID),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $authorization?->getAppId(),
				'device' => $device,
			]));

			$this->devicePropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_ENCRYPTION_KEY,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_ENCRYPTION_KEY),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $authorization?->getEncryptionKey(),
				'device' => $device,
			]));

			$this->devicePropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_APPLICATIONS,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_APPLICATIONS),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'enum' => array_map(static fn (Entities\API\Application $application): array => [
					$application->getName(),
					$application->getId(),
					$application->getId(),
				], $apps->getApps()),
				'device' => $device,
			]));

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(sprintf(
				'Device "%s" was successfully created',
				$device->getName() ?? $device->getIdentifier(),
			));
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
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
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function editExistingDevice(Style\SymfonyStyle $io, Entities\VieraConnector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->warning('No devices registered in Viera connector');

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

		$authorization = null;

		$name = $this->askDeviceName($io, $device);

		try {
			$ipAddress = $device->getIpAddress();

			if ($ipAddress === null) {
				$ipAddress = $this->askIpAddress($io, $device);
			}

			$televisionApi = $this->televisionApiFactory->create(
				$device->getIdentifier(),
				$ipAddress,
				$device->getPort(),
				$device->getAppId(),
				$device->getEncryptionKey(),
			);
			$televisionApi->connect();

			try {
				$isOnline = await($televisionApi->livenessProbe());
			} catch (Throwable $ex) {
				$this->logger->error(
					'Checking TV status failed',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
						'type' => 'devices-cmd',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
					],
				);

				$io->error('Something went wrong, device could not be edited. Error was logged.');

				return;
			}

			if ($isOnline === false) {
				$io->warning(sprintf('Television with IP: %s address is unreachable.', $ipAddress));

				return;
			}

			$specs = $televisionApi->getSpecs(false);

			if (!$device->isEncrypted() && $specs->isRequiresEncryption()) {
				try {
					$isTurnedOn = await($televisionApi->isTurnedOn());
				} catch (Throwable $ex) {
					$this->logger->error(
						'Checking screen status failed',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
							'type' => 'devices-cmd',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
						],
					);

					$io->error('Something went wrong, device could not be edited. Error was logged.');

					return;
				}

				if ($isTurnedOn === false) {
					$io->warning(
						'It looks like your TV is not turned on. It is possible that the pairing could not be finished.',
					);

					$question = new Console\Question\ConfirmationQuestion(
						'Would you like to continue?',
						false,
					);

					$continue = (bool) $io->askQuestion($question);

					if (!$continue) {
						return;
					}
				}

				$challengeKey = $televisionApi->requestPinCode(
					$connector->getName() ?? $connector->getIdentifier(),
					false,
				);

				$pinCode = $this->askPinCode($io);

				$authorization = $televisionApi->authorizePinCode($pinCode, $challengeKey, false);
			}

		} catch (Exceptions\TelevisionApiCall | Exceptions\Encrypt | Exceptions\Decrypt $ex) {
			$this->logger->error(
				'Calling television api failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error('Something went wrong, device could not be edited. Error was logged.');

			return;

		} catch (Throwable $ex) {
			$this->logger->error(
				'Unhandled error occur',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error('Something went wrong, device could not be edited. Error was logged.');

			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->update($device, Utils\ArrayHash::from([
				'name' => $name,
			]));

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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error('Something went wrong, device could not be updated. Error was logged.');
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
	 */
	private function deleteExistingDevice(Style\SymfonyStyle $io, Entities\VieraConnector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->info('No Viera devices registered in selected connector');

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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
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

	private function askDeviceName(Style\SymfonyStyle $io, Entities\VieraDevice|null $device = null): string|null
	{
		$question = new Console\Question\Question('Provide device name', $device?->getName());

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askIpAddress(Style\SymfonyStyle $io, Entities\VieraDevice|null $device = null): string
	{
		$question = new Console\Question\Question('Provide device IP address', $device?->getIpAddress());
		$question->setValidator(static function (string|null $answer): string {
			if ($answer !== null && preg_match(self::MATCH_IP_ADDRESS, $answer) === 1) {
				return $answer;
			}

			throw new Exceptions\Runtime('Provided IP address is not valid');
		});

		$ipAddress = $io->askQuestion($question);

		return strval($ipAddress);
	}

	private function askPinCode(Style\SymfonyStyle $io): string
	{
		$question = new Console\Question\Question('Provide device PIN code displayed on you TV');
		$question->setValidator(static function (string|null $answer): string {
			if ($answer !== null && $answer !== '') {
				return $answer;
			}

			throw new Exceptions\Runtime('Provided PIN code is not valid');
		});

		$pinCode = $io->askQuestion($question);

		return strval($pinCode);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\VieraConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new DevicesQueries\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\VieraConnector::class,
		);
		usort(
			$systemConnectors,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			static fn (DevicesEntities\Connectors\Connector $a, DevicesEntities\Connectors\Connector $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($systemConnectors as $connector) {
			assert($connector instanceof Entities\VieraConnector);

			$connectors[$connector->getIdentifier()] = $connector->getIdentifier()
				. ($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
		}

		if (count($connectors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			'Please select connector under which you want to manage devices',
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);
		$question->setErrorMessage('Selected connector: "%s" is not valid.');
		$question->setValidator(function (string|null $answer) use ($connectors): Entities\VieraConnector {
			if ($answer === null) {
				throw new Exceptions\InvalidState('Selected answer is not valid');
			}

			if (array_key_exists($answer, array_values($connectors))) {
				$answer = array_values($connectors)[$answer];
			}

			$identifier = array_search($answer, $connectors, true);

			if ($identifier !== false) {
				$findConnectorQuery = new DevicesQueries\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\VieraConnector::class,
				);
				assert($connector instanceof Entities\VieraConnector || $connector === null);

				if ($connector !== null) {
					return $connector;
				}
			}

			throw new Exceptions\InvalidState('Selected answer is not valid');
		});

		$connector = $io->askQuestion($question);
		assert($connector instanceof Entities\VieraConnector);

		return $connector;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichDevice(
		Style\SymfonyStyle $io,
		Entities\VieraConnector $connector,
	): Entities\VieraDevice|null
	{
		$devices = [];

		$findDevicesQuery = new DevicesQueries\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$connectorDevices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\VieraDevice::class);
		usort(
			$connectorDevices,
			static fn (DevicesEntities\Devices\Device $a, DevicesEntities\Devices\Device $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($connectorDevices as $device) {
			assert($device instanceof Entities\VieraDevice);

			$devices[$device->getIdentifier()] = $device->getIdentifier()
				. ($device->getName() !== null ? ' [' . $device->getName() . ']' : '');
		}

		if (count($devices) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			'Please select device to manage',
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);
		$question->setErrorMessage('Selected device: "%s" is not valid.');
		$question->setValidator(function (string|null $answer) use ($connector, $devices): Entities\VieraDevice {
			if ($answer === null) {
				throw new Exceptions\Runtime('You have to select device from list');
			}

			if (array_key_exists($answer, array_values($devices))) {
				$answer = array_values($devices)[$answer];
			}

			$identifier = array_search($answer, $devices, true);

			if ($identifier !== false) {
				$findDeviceQuery = new DevicesQueries\FindDevices();
				$findDeviceQuery->byIdentifier($identifier);
				$findDeviceQuery->forConnector($connector);

				$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\VieraDevice::class);
				assert($device instanceof Entities\VieraDevice || $device === null);

				if ($device !== null) {
					return $device;
				}
			}

			throw new Exceptions\Runtime('You have to select device from list');
		});

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\VieraDevice);

		return $device;
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

		throw new Exceptions\Runtime('Transformer manager could not be loaded');
	}

}
