<?php declare(strict_types = 1);

/**
 * Install.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           15.12.23
 */

namespace FastyBird\Connector\HomeKit\Commands;

use Brick\Math;
use DateTimeInterface;
use Doctrine\DBAL;
use Doctrine\Persistence;
use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Helpers;
use FastyBird\Connector\HomeKit\Queries;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Nette\Localization;
use Nette\Utils;
use Ramsey\Uuid;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function array_combine;
use function array_diff;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_search;
use function array_values;
use function asort;
use function assert;
use function boolval;
use function count;
use function floatval;
use function implode;
use function in_array;
use function intval;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_object;
use function is_string;
use function preg_replace;
use function sprintf;
use function str_replace;
use function strtolower;
use function strval;
use function ucwords;
use function usort;

/**
 * Connector install command
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Install extends Console\Command\Command
{

	public const NAME = 'fb:homekit-connector:install';

	public function __construct(
		private readonly Helpers\Loader $loader,
		private readonly HomeKit\Logger $logger,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Connectors\ConnectorsManager $connectorsManager,
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesRepository $connectorsPropertiesRepository,
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesManager $connectorsPropertiesManager,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly Persistence\ManagerRegistry $managerRegistry,
		private readonly BootstrapHelpers\Database $databaseHelper,
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
			->setDescription('HomeKit connector installer');
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title($this->translator->translate('//homekit-connector.cmd.install.title'));

		$io->note($this->translator->translate('//homekit-connector.cmd.install.subtitle'));

		$this->askInstallAction($io);

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function createConnector(Style\SymfonyStyle $io): void
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//homekit-connector.cmd.install.questions.provide.connector.identifier'),
		);

		$question->setValidator(function ($answer) {
			if ($answer !== null) {
				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($answer);

				if ($this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\HomeKitConnector::class,
				) !== null) {
					throw new Exceptions\Runtime(
						$this->translator->translate(
							'//homekit-connector.cmd.install.messages.identifier.connector.used',
						),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'homekit-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				if ($this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\HomeKitConnector::class,
				) === null) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error(
				$this->translator->translate('//homekit-connector.cmd.install.messages.identifier.connector.missing'),
			);

			return;
		}

		$name = $this->askConnectorName($io);

		$port = $this->askConnectorPort($io);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\HomeKitConnector::class,
				'identifier' => $identifier,
				'name' => $name === '' ? null : $name,
			]));
			assert($connector instanceof Entities\HomeKitConnector);

			$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::PORT,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
				'value' => $port,
				'connector' => $connector,
			]));

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//homekit-connector.cmd.install.messages.create.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_HOMEKIT,
					'type' => 'install-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//homekit-connector.cmd.install.messages.create.connector.error'));

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//homekit-connector.cmd.install.questions.create.devices'),
			true,
		);

		$createDevices = (bool) $io->askQuestion($question);

		if ($createDevices) {
			$this->createDevice($io, $connector);
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function editConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->warning($this->translator->translate('//homekit-connector.cmd.base.messages.noConnectors'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//homekit-connector.cmd.install.questions.create.connector'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createConnector($io);
			}

			return;
		}

		$name = $this->askConnectorName($io, $connector);

		$enabled = $connector->isEnabled();

		if ($connector->isEnabled()) {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//homekit-connector.cmd.install.questions.disable.connector'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = false;
			}
		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//homekit-connector.cmd.install.questions.enable.connector'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = true;
			}
		}

		$port = $this->askConnectorPort($io, $connector);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::PORT);

		$portProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->update($connector, Utils\ArrayHash::from([
				'name' => $name === '' ? null : $name,
				'enabled' => $enabled,
			]));
			assert($connector instanceof Entities\HomeKitConnector);

			if ($portProperty === null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::PORT,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
					'value' => $port,
					'connector' => $connector,
				]));
			} else {
				$this->connectorsPropertiesManager->update($portProperty, Utils\ArrayHash::from([
					'value' => $port,
				]));
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//homekit-connector.cmd.install.messages.update.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_HOMEKIT,
					'type' => 'install-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//homekit-connector.cmd.install.messages.update.connector.error'));

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//homekit-connector.cmd.install.questions.manage.devices'),
			false,
		);

		$manage = (bool) $io->askQuestion($question);

		if (!$manage) {
			return;
		}

		$this->askManageConnectorAction($io, $connector);
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function deleteConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info($this->translator->translate('//homekit-connector.cmd.base.messages.noConnectors'));

			return;
		}

		$io->warning(
			$this->translator->translate(
				'//homekit-connector.cmd.install.messages.remove.connector.confirm',
				['name' => $connector->getName() ?? $connector->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//homekit-connector.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->connectorsManager->delete($connector);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//homekit-connector.cmd.install.messages.remove.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_HOMEKIT,
					'type' => 'install-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//homekit-connector.cmd.install.messages.remove.connector.error'));
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function manageConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info($this->translator->translate('//homekit-connector.cmd.base.messages.noConnectors'));

			return;
		}

		$this->askManageConnectorAction($io, $connector);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function listConnectors(Style\SymfonyStyle $io): void
	{
		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		$connectors = $this->connectorsRepository->findAllBy($findConnectorsQuery, Entities\HomeKitConnector::class);
		usort(
			$connectors,
			static fn (Entities\HomeKitConnector $a, Entities\HomeKitConnector $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//homekit-connector.cmd.install.data.name'),
			$this->translator->translate('//homekit-connector.cmd.install.data.devicesCnt'),
		]);

		foreach ($connectors as $index => $connector) {
			$findDevicesQuery = new Queries\Entities\FindDevices();
			$findDevicesQuery->forConnector($connector);

			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\HomeKitDevice::class);

			$table->addRow([
				$index + 1,
				$connector->getName() ?? $connector->getIdentifier(),
				count($devices),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function createDevice(Style\SymfonyStyle $io, Entities\HomeKitConnector $connector): void
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//homekit-connector.cmd.install.questions.provide.device.identifier'),
		);

		$question->setValidator(function (string|null $answer) {
			if ($answer !== '' && $answer !== null) {
				$findDeviceQuery = new Queries\Entities\FindDevices();
				$findDeviceQuery->byIdentifier($answer);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\HomeKitDevice::class) !== null
				) {
					throw new Exceptions\Runtime(
						$this->translator->translate('//homekit-connector.cmd.install.messages.identifier.device.used'),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'homekit-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findDeviceQuery = new Queries\Entities\FindDevices();
				$findDeviceQuery->byIdentifier($identifier);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\HomeKitDevice::class) === null
				) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error(
				$this->translator->translate('//homekit-connector.cmd.install.messages.identifier.device.missing'),
			);

			return;
		}

		$name = $this->askDeviceName($io);

		$category = $this->askDeviceCategory($io);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->create(Utils\ArrayHash::from([
				'entity' => Entities\HomeKitDevice::class,
				'connector' => $connector,
				'identifier' => $identifier,
				'name' => $name,
			]));
			assert($device instanceof Entities\HomeKitDevice);

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::CATEGORY,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
				'value' => $category->getValue(),
				'device' => $device,
			]));

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//homekit-connector.cmd.install.messages.create.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_HOMEKIT,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//homekit-connector.cmd.install.messages.create.device.error'));

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}

		$this->createService($io, $device);
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function editDevice(Style\SymfonyStyle $io, Entities\HomeKitConnector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->warning($this->translator->translate('//homekit-connector.cmd.install.messages.noDevices'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//homekit-connector.cmd.install.questions.create.device'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createDevice($io, $connector);
			}

			return;
		}

		$name = $this->askDeviceName($io, $device);

		$findDevicePropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::CATEGORY);

		$categoryProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		$category = $this->askDeviceCategory($io, $device);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->update($device, Utils\ArrayHash::from([
				'name' => $name,
			]));
			assert($device instanceof Entities\HomeKitDevice);

			if ($categoryProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::CATEGORY,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
					'value' => $category->getValue(),
					'device' => $device,
				]));
			} elseif ($categoryProperty instanceof DevicesEntities\Devices\Properties\Variable) {
				$this->devicesPropertiesManager->update($categoryProperty, Utils\ArrayHash::from([
					'value' => $category->getValue(),
				]));
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//homekit-connector.cmd.install.messages.update.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_HOMEKIT,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//homekit-connector.cmd.install.messages.update.device.error'));

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//homekit-connector.cmd.install.questions.manage.services'),
			false,
		);

		$manage = (bool) $io->askQuestion($question);

		if (!$manage) {
			return;
		}

		$this->askManageDeviceAction($io, $device);
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function deleteDevice(Style\SymfonyStyle $io, Entities\HomeKitConnector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->info($this->translator->translate('//homekit-connector.cmd.install.messages.noDevices'));

			return;
		}

		$io->warning(
			$this->translator->translate(
				'//homekit-connector.cmd.install.messages.remove.device.confirm',
				['name' => $device->getName() ?? $device->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//homekit-connector.cmd.base.questions.continue'),
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
					'//homekit-connector.cmd.install.messages.remove.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_HOMEKIT,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//homekit-connector.cmd.install.messages.remove.device.error'));
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function manageDevice(Style\SymfonyStyle $io, Entities\HomeKitConnector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->info($this->translator->translate('//homekit-connector.cmd.install.messages.noDevices'));

			return;
		}

		$this->askManageDeviceAction($io, $device);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function listDevices(Style\SymfonyStyle $io, Entities\HomeKitConnector $connector): void
	{
		$findDevicesQuery = new Queries\Entities\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\HomeKitDevice::class);
		usort(
			$devices,
			static fn (Entities\HomeKitDevice $a, Entities\HomeKitDevice $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//homekit-connector.cmd.install.data.name'),
			$this->translator->translate('//homekit-connector.cmd.install.data.category'),
		]);

		foreach ($devices as $index => $device) {
			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
				$this->translator->translate(
					'//homekit-connector.cmd.base.category.' . $device->getAccessoryCategory()->getValue(),
				),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function createService(Style\SymfonyStyle $io, Entities\HomeKitDevice $device): void
	{
		$type = $this->askServiceType($io, $device);

		$identifier = strtolower(strval(preg_replace('/(?<!^)[A-Z]/', '_$0', $type)));

		$identifierPattern = $identifier . '_%d';

		for ($i = 1; $i <= 100; $i++) {
			$identifier = sprintf($identifierPattern, $i);

			$findChannelQuery = new Queries\Entities\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier($identifier);

			$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\HomeKitChannel::class);

			if ($channel === null) {
				break;
			}
		}

		$metadata = $this->loader->loadServices();

		if (!$metadata->offsetExists($type)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for service: %s was not found',
				$type,
			));
		}

		$serviceMetadata = $metadata->offsetGet($type);

		if (
			!$serviceMetadata instanceof Utils\ArrayHash
			|| !$serviceMetadata->offsetExists('UUID')
			|| !is_string($serviceMetadata->offsetGet('UUID'))
			|| !$serviceMetadata->offsetExists('RequiredCharacteristics')
			|| !$serviceMetadata->offsetGet('RequiredCharacteristics') instanceof Utils\ArrayHash
		) {
			throw new Exceptions\InvalidState('Service definition is missing required attributes');
		}

		$requiredCharacteristics = (array) $serviceMetadata->offsetGet('RequiredCharacteristics');
		$optionalCharacteristics = $virtualCharacteristics = [];

		if (
			$serviceMetadata->offsetExists('OptionalCharacteristics')
			&& $serviceMetadata->offsetGet('OptionalCharacteristics') instanceof Utils\ArrayHash
		) {
			$optionalCharacteristics = (array) $serviceMetadata->offsetGet('OptionalCharacteristics');
		}

		if (
			$serviceMetadata->offsetExists('VirtualCharacteristics')
			&& $serviceMetadata->offsetGet('VirtualCharacteristics') instanceof Utils\ArrayHash
		) {
			$virtualCharacteristics = (array) $serviceMetadata->offsetGet('VirtualCharacteristics');
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$channel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\HomeKitChannel::class,
				'identifier' => $identifier,
				'device' => $device,
			]));
			assert($channel instanceof Entities\HomeKitChannel);

			$this->createCharacteristics($io, $channel, $requiredCharacteristics, true);

			$this->createCharacteristics(
				$io,
				$channel,
				array_merge($optionalCharacteristics, $virtualCharacteristics),
				false,
			);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//homekit-connector.cmd.install.messages.create.service.success',
					['name' => $channel->getName() ?? $channel->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_HOMEKIT,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//homekit-connector.cmd.install.messages.create.service.error'));

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//homekit-connector.cmd.install.questions.manage.characteristics'),
			false,
		);

		$manage = (bool) $io->askQuestion($question);

		if (!$manage) {
			return;
		}

		$this->askManageServiceAction($io, $channel);
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function editService(Style\SymfonyStyle $io, Entities\HomeKitDevice $device): void
	{
		$channels = $this->getServicesList($device);

		if (count($channels) === 0) {
			$io->warning($this->translator->translate('//homekit-connector.cmd.install.messages.noServices'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//homekit-connector.cmd.install.questions.create.service'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createService($io, $device);
			}

			return;
		}

		$channel = $this->askWhichService($io, $device, $channels);

		if ($channel === null) {
			return;
		}

		$type = $channel->getServiceType();

		$metadata = $this->loader->loadServices();

		if (!$metadata->offsetExists($type->getValue())) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for service: %s was not found',
				$type->getValue(),
			));
		}

		$serviceMetadata = $metadata->offsetGet($type->getValue());

		if (
			!$serviceMetadata instanceof Utils\ArrayHash
			|| !$serviceMetadata->offsetExists('UUID')
			|| !is_string($serviceMetadata->offsetGet('UUID'))
			|| !$serviceMetadata->offsetExists('RequiredCharacteristics')
			|| !$serviceMetadata->offsetGet('RequiredCharacteristics') instanceof Utils\ArrayHash
		) {
			throw new Exceptions\InvalidState('Service definition is missing required attributes');
		}

		$requiredCharacteristics = (array) $serviceMetadata->offsetGet('RequiredCharacteristics');
		$optionalCharacteristics = $virtualCharacteristics = [];

		if (
			$serviceMetadata->offsetExists('OptionalCharacteristics')
			&& $serviceMetadata->offsetGet('OptionalCharacteristics') instanceof Utils\ArrayHash
		) {
			$optionalCharacteristics = (array) $serviceMetadata->offsetGet('OptionalCharacteristics');
		}

		if (
			$serviceMetadata->offsetExists('VirtualCharacteristics')
			&& $serviceMetadata->offsetGet('VirtualCharacteristics') instanceof Utils\ArrayHash
		) {
			$virtualCharacteristics = (array) $serviceMetadata->offsetGet('VirtualCharacteristics');
		}

		$missingRequired = [];

		foreach ($requiredCharacteristics as $requiredCharacteristic) {
			$findPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findPropertyQuery->forChannel($channel);
			$findPropertyQuery->byIdentifier(
				strtolower(strval(preg_replace('/(?<!^)[A-Z]/', '_$0', $requiredCharacteristic))),
			);

			$property = $this->channelsPropertiesRepository->findOneBy($findPropertyQuery);

			if ($property === null) {
				$missingRequired[] = $requiredCharacteristic;
			}
		}

		$missingOptional = [];

		foreach (array_merge($optionalCharacteristics, $virtualCharacteristics) as $optionalCharacteristic) {
			$findPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findPropertyQuery->forChannel($channel);
			$findPropertyQuery->byIdentifier(
				strtolower(strval(preg_replace('/(?<!^)[A-Z]/', '_$0', $optionalCharacteristic))),
			);

			$property = $this->channelsPropertiesRepository->findOneBy($findPropertyQuery);

			if ($property === null) {
				$missingOptional[] = $optionalCharacteristic;
			}
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			if (count($missingRequired) > 0) {
				$this->createCharacteristics($io, $channel, $missingRequired, true);
			}

			if (count($missingOptional) > 0) {
				$question = new Console\Question\ConfirmationQuestion(
					$this->translator->translate('//homekit-connector.cmd.install.questions.addCharacteristics'),
					false,
				);

				$add = (bool) $io->askQuestion($question);

				if ($add) {
					$this->createCharacteristics($io, $channel, $missingOptional, false);
				}
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//homekit-connector.cmd.install.messages.update.service.success',
					['name' => $channel->getName() ?? $channel->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_HOMEKIT,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->success($this->translator->translate('//homekit-connector.cmd.install.messages.update.service.error'));

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//homekit-connector.cmd.install.questions.manage.characteristics'),
			false,
		);

		$manage = (bool) $io->askQuestion($question);

		if (!$manage) {
			return;
		}

		$this->askManageServiceAction($io, $channel);
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function deleteService(Style\SymfonyStyle $io, Entities\HomeKitDevice $device): void
	{
		$channels = $this->getServicesList($device);

		if (count($channels) === 0) {
			$io->warning($this->translator->translate('//homekit-connector.cmd.install.messages.noServices'));

			return;
		}

		$channel = $this->askWhichService($io, $device, $channels);

		if ($channel === null) {
			return;
		}

		$io->warning(
			$this->translator->translate(
				'//homekit-connector.cmd.install.messages.remove.service.confirm',
				['name' => $channel->getName() ?? $channel->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//homekit-connector.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
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
					'//homekit-connector.cmd.install.messages.remove.service.success',
					['name' => $channel->getName() ?? $channel->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_HOMEKIT,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->success($this->translator->translate('//homekit-connector.cmd.install.messages.remove.service.error'));
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function manageService(Style\SymfonyStyle $io, Entities\HomeKitDevice $device): void
	{
		$channels = $this->getServicesList($device);

		if (count($channels) === 0) {
			$io->warning($this->translator->translate('//homekit-connector.cmd.install.messages.noServices'));

			return;
		}

		$channel = $this->askWhichService($io, $device, $channels);

		if ($channel === null) {
			$io->info($this->translator->translate('//homekit-connector.cmd.install.messages.noServices'));

			return;
		}

		$this->askManageServiceAction($io, $channel);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	private function listServices(Style\SymfonyStyle $io, Entities\HomeKitDevice $device): void
	{
		$findChannelsQuery = new Queries\Entities\FindChannels();
		$findChannelsQuery->forDevice($device);

		$deviceChannels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\HomeKitChannel::class);
		usort(
			$deviceChannels,
			static fn (DevicesEntities\Channels\Channel $a, DevicesEntities\Channels\Channel $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			'Name',
			'Type',
			'Characteristics',
		]);

		foreach ($deviceChannels as $index => $channel) {
			$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertiesQuery->forChannel($channel);

			$table->addRow([
				$index + 1,
				$channel->getName() ?? $channel->getIdentifier(),
				$channel->getServiceType()->getValue(),
				implode(
					', ',
					array_map(
						static fn (DevicesEntities\Channels\Properties\Property $property): string => str_replace(
							' ',
							'',
							ucwords(str_replace('_', ' ', $property->getIdentifier())),
						),
						$this->channelsPropertiesRepository->findAllBy($findChannelPropertiesQuery),
					),
				),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @param array<string> $characteristics
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws Nette\IOException
	 */
	private function createCharacteristics(
		Style\SymfonyStyle $io,
		Entities\HomeKitChannel $channel,
		array $characteristics,
		bool $required,
	): void
	{
		$metadata = $this->loader->loadCharacteristics();

		$createdCharacteristics = [];

		while (count(array_diff($characteristics, $createdCharacteristics)) > 0) {
			$characteristic = $this->askCharacteristic(
				$io,
				$channel->getServiceType(),
				$required,
				$characteristics,
				$createdCharacteristics,
			);

			if ($characteristic === null) {
				break;
			}

			$characteristicMetadata = $metadata->offsetGet($characteristic);

			if (
				!$characteristicMetadata instanceof Utils\ArrayHash
				|| !$characteristicMetadata->offsetExists('Format')
				|| !is_string($characteristicMetadata->offsetGet('Format'))
				|| !$characteristicMetadata->offsetExists('DataType')
				|| !is_string($characteristicMetadata->offsetGet('DataType'))
			) {
				throw new Exceptions\InvalidState('Characteristic definition is missing required attributes');
			}

			$dataType = MetadataTypes\DataType::get($characteristicMetadata->offsetGet('DataType'));

			$format = $this->askFormat($io, $characteristic);

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//homekit-connector.cmd.install.questions.connectCharacteristic'),
				true,
			);

			$connect = (bool) $io->askQuestion($question);

			if ($connect) {
				$connectProperty = $this->askProperty($io);

				$format = $this->askFormat($io, $characteristic, $connectProperty);

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Mapped::class,
					'parent' => $connectProperty,
					'identifier' => strtolower(strval(preg_replace('/(?<!^)[A-Z]/', '_$0', $characteristic))),
					'channel' => $channel,
					'dataType' => $dataType,
					'format' => $format,
				]));
			} else {
				$value = $this->provideCharacteristicValue($io, $characteristic);

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => strtolower(strval(preg_replace('/(?<!^)[A-Z]/', '_$0', $characteristic))),
					'channel' => $channel,
					'dataType' => $dataType,
					'format' => $format,
					'value' => $value,
				]));
			}

			$createdCharacteristics[] = $characteristic;

			if (!$required && count(array_diff($characteristics, $createdCharacteristics)) > 0) {
				$question = new Console\Question\ConfirmationQuestion(
					$this->translator->translate('//homekit-connector.cmd.base.questions.continue'),
					false,
				);

				$continue = (bool) $io->askQuestion($question);

				if (!$continue) {
					break;
				}
			}
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws Nette\IOException
	 */
	private function editCharacteristic(Style\SymfonyStyle $io, Entities\HomeKitChannel $channel): void
	{
		$properties = $this->getCharacteristicsList($channel);

		if (count($properties) === 0) {
			$io->warning($this->translator->translate('//homekit-connector.cmd.install.messages.noCharacteristics'));

			return;
		}

		$property = $this->askWhichCharacteristic($io, $channel, $properties);

		if ($property === null) {
			return;
		}

		$type = str_replace(' ', '', ucwords(str_replace('_', ' ', $property->getIdentifier())));

		$metadata = $this->loader->loadCharacteristics();

		if (!$metadata->offsetExists($type)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for characteristic: %s was not found',
				$type,
			));
		}

		$characteristicMetadata = $metadata->offsetGet($type);

		if (
			!$characteristicMetadata instanceof Utils\ArrayHash
			|| !$characteristicMetadata->offsetExists('UUID')
			|| !is_string($characteristicMetadata->offsetGet('UUID'))
			|| !$characteristicMetadata->offsetExists('Format')
			|| !$characteristicMetadata->offsetExists('DataType')
		) {
			throw new Exceptions\InvalidState('Characteristic definition is missing required attributes');
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$dataType = MetadataTypes\DataType::get($characteristicMetadata->offsetGet('DataType'));

			$format = $this->askFormat($io, $type);

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//homekit-connector.cmd.install.questions.connectCharacteristic'),
				$property instanceof DevicesEntities\Channels\Properties\Mapped,
			);

			$connect = (bool) $io->askQuestion($question);

			if ($connect) {
				$connectProperty = $this->askProperty(
					$io,
					(
					$property instanceof DevicesEntities\Channels\Properties\Mapped
					&& $property->getParent() instanceof DevicesEntities\Channels\Properties\Dynamic
						? $property->getParent()
						: null
					),
				);

				$format = $this->askFormat($io, $type, $connectProperty);

				if (
					$property instanceof DevicesEntities\Channels\Properties\Mapped
					&& $connectProperty instanceof DevicesEntities\Channels\Properties\Dynamic
				) {
					$this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
						'parent' => $connectProperty,
						'format' => $format,
					]));
				} else {
					$this->channelsPropertiesManager->delete($property);

					$property = $this->channelsPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Mapped::class,
						'parent' => $connectProperty,
						'identifier' => $property->getIdentifier(),
						'channel' => $channel,
						'dataType' => $dataType,
						'format' => $format,
					]));
				}
			} else {
				$value = $this->provideCharacteristicValue(
					$io,
					$type,
					$property instanceof DevicesEntities\Channels\Properties\Variable ? $property->getValue() : null,
				);

				if ($property instanceof DevicesEntities\Channels\Properties\Variable) {
					$this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
						'value' => $value,
						'format' => $format,
					]));
				} else {
					$this->channelsPropertiesManager->delete($property);

					$property = $this->channelsPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => $property->getIdentifier(),
						'channel' => $channel,
						'dataType' => $dataType,
						'format' => $format,
						'value' => $value,
					]));
				}
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//homekit-connector.cmd.install.messages.update.characteristic.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_HOMEKIT,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->success(
				$this->translator->translate('//homekit-connector.cmd.install.messages.update.characteristic.error'),
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
	 */
	private function deleteCharacteristic(Style\SymfonyStyle $io, Entities\HomeKitChannel $channel): void
	{
		$properties = $this->getCharacteristicsList($channel);

		if (count($properties) === 0) {
			$io->warning($this->translator->translate('//homekit-connector.cmd.install.messages.noCharacteristics'));

			return;
		}

		$property = $this->askWhichCharacteristic($io, $channel, $properties);

		if ($property === null) {
			return;
		}

		$io->warning(
			$this->translator->translate(
				'//homekit-connector.cmd.install.messages.remove.characteristic.confirm',
				['name' => $property->getName() ?? $property->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//homekit-connector.cmd.base.questions.continue'),
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
					'//homekit-connector.cmd.install.messages.remove.characteristic.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_HOMEKIT,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->success(
				$this->translator->translate('//homekit-connector.cmd.install.messages.remove.characteristic.error'),
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function listCharacteristics(Style\SymfonyStyle $io, Entities\HomeKitChannel $channel): void
	{
		$findPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findPropertiesQuery->forChannel($channel);

		$channelProperties = $this->channelsPropertiesRepository->findAllBy($findPropertiesQuery);
		usort(
			$channelProperties,
			static fn (DevicesEntities\Channels\Properties\Property $a, DevicesEntities\Channels\Properties\Property $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			'Name',
			'Type',
			'Value',
		]);

		$metadata = $this->loader->loadCharacteristics();

		foreach ($channelProperties as $index => $property) {
			$type = str_replace(' ', '', ucwords(str_replace('_', ' ', $property->getIdentifier())));

			$value = $property instanceof DevicesEntities\Channels\Properties\Variable ? $property->getValue() : 'N/A';

			if (
				$property->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)
				&& $metadata->offsetExists($type)
				&& $metadata->offsetGet($type) instanceof Utils\ArrayHash
				&& $metadata->offsetGet($type)->offsetExists('ValidValues')
				&& $metadata->offsetGet($type)->offsetGet('ValidValues') instanceof Utils\ArrayHash
			) {
				$enumValue = array_search(
					intval(MetadataUtilities\ValueHelper::flattenValue($value)),
					(array) $metadata->offsetGet($type)->offsetGet('ValidValues'),
					true,
				);

				if ($enumValue !== false) {
					$value = $enumValue;
				}
			}

			$table->addRow([
				$index + 1,
				$property->getName() ?? $property->getIdentifier(),
				str_replace(' ', '', ucwords(str_replace('_', ' ', $property->getIdentifier()))),
				$value,
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function askInstallAction(Style\SymfonyStyle $io): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//homekit-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//homekit-connector.cmd.install.actions.create.connector'),
				1 => $this->translator->translate('//homekit-connector.cmd.install.actions.update.connector'),
				2 => $this->translator->translate('//homekit-connector.cmd.install.actions.remove.connector'),
				3 => $this->translator->translate('//homekit-connector.cmd.install.actions.manage.connector'),
				4 => $this->translator->translate('//homekit-connector.cmd.install.actions.list.connectors'),
				5 => $this->translator->translate('//homekit-connector.cmd.install.actions.nothing'),
			],
			5,
		);

		$question->setErrorMessage(
			$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.create.connector',
			)
			|| $whatToDo === '0'
		) {
			$this->createConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.update.connector',
			)
			|| $whatToDo === '1'
		) {
			$this->editConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.remove.connector',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.manage.connector',
			)
			|| $whatToDo === '3'
		) {
			$this->manageConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.list.connectors',
			)
			|| $whatToDo === '4'
		) {
			$this->listConnectors($io);

			$this->askInstallAction($io);
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function askManageConnectorAction(
		Style\SymfonyStyle $io,
		Entities\HomeKitConnector $connector,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//homekit-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//homekit-connector.cmd.install.actions.create.device'),
				1 => $this->translator->translate('//homekit-connector.cmd.install.actions.update.device'),
				2 => $this->translator->translate('//homekit-connector.cmd.install.actions.remove.device'),
				3 => $this->translator->translate('//homekit-connector.cmd.install.actions.manage.device'),
				4 => $this->translator->translate('//homekit-connector.cmd.install.actions.list.devices'),
				5 => $this->translator->translate('//homekit-connector.cmd.install.actions.nothing'),
			],
			5,
		);

		$question->setErrorMessage(
			$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.create.device',
			)
			|| $whatToDo === '0'
		) {
			$this->createDevice($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.update.device',
			)
			|| $whatToDo === '1'
		) {
			$this->editDevice($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.remove.device',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteDevice($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.manage.device',
			)
			|| $whatToDo === '3'
		) {
			$this->manageDevice($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.list.devices',
			)
			|| $whatToDo === '4'
		) {
			$this->listDevices($io, $connector);

			$this->askManageConnectorAction($io, $connector);
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function askManageDeviceAction(
		Style\SymfonyStyle $io,
		Entities\HomeKitDevice $device,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//homekit-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//homekit-connector.cmd.install.actions.create.service'),
				1 => $this->translator->translate('//homekit-connector.cmd.install.actions.update.service'),
				2 => $this->translator->translate('//homekit-connector.cmd.install.actions.remove.service'),
				3 => $this->translator->translate('//homekit-connector.cmd.install.actions.manage.service'),
				4 => $this->translator->translate('//homekit-connector.cmd.install.actions.list.services'),
				5 => $this->translator->translate('//homekit-connector.cmd.install.actions.nothing'),
			],
			5,
		);

		$question->setErrorMessage(
			$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.create.service',
			)
			|| $whatToDo === '0'
		) {
			$this->createService($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.update.service',
			)
			|| $whatToDo === '1'
		) {
			$this->editService($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.remove.service',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteService($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.manage.service',
			)
			|| $whatToDo === '3'
		) {
			$this->manageService($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.list.services',
			)
			|| $whatToDo === '4'
		) {
			$this->listServices($io, $device);

			$this->askManageDeviceAction($io, $device);
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function askManageServiceAction(
		Style\SymfonyStyle $io,
		Entities\HomeKitChannel $channel,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//homekit-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//homekit-connector.cmd.install.actions.update.characteristic'),
				1 => $this->translator->translate('//homekit-connector.cmd.install.actions.remove.characteristic'),
				2 => $this->translator->translate('//homekit-connector.cmd.install.actions.list.characteristics'),
				3 => $this->translator->translate('//homekit-connector.cmd.install.actions.nothing'),
			],
			3,
		);

		$question->setErrorMessage(
			$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.update.characteristic',
			)
			|| $whatToDo === '0'
		) {
			$this->editCharacteristic($io, $channel);

			$this->askManageServiceAction($io, $channel);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.remove.characteristic',
			)
			|| $whatToDo === '1'
		) {
			$this->deleteCharacteristic($io, $channel);

			$this->askManageServiceAction($io, $channel);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//homekit-connector.cmd.install.actions.list.characteristics',
			)
			|| $whatToDo === '2'
		) {
			$this->listCharacteristics($io, $channel);

			$this->askManageServiceAction($io, $channel);
		}
	}

	private function askConnectorName(
		Style\SymfonyStyle $io,
		Entities\HomeKitConnector|null $connector = null,
	): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//homekit-connector.cmd.install.questions.provide.connector.name'),
			$connector?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askConnectorPort(Style\SymfonyStyle $io, Entities\HomeKitConnector|null $connector = null): int
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//homekit-connector.cmd.install.questions.provide.connector.port'),
			$connector?->getPort() ?? HomeKit\Constants::DEFAULT_PORT,
		);
		$question->setValidator(function (string|null $answer) use ($connector): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			$findConnectorPropertiesQuery = new DevicesQueries\Entities\FindConnectorProperties();
			$findConnectorPropertiesQuery->byIdentifier(Types\ConnectorPropertyIdentifier::PORT);

			$properties = $this->connectorsPropertiesRepository->findAllBy(
				$findConnectorPropertiesQuery,
				DevicesEntities\Connectors\Properties\Variable::class,
			);

			foreach ($properties as $property) {
				if (
					$property->getConnector() instanceof Entities\HomeKitConnector
					&& $property->getValue() === intval($answer)
					&& (
						$connector === null || !$property->getConnector()->getId()->equals($connector->getId())
					)
				) {
					throw new Exceptions\Runtime(
						$this->translator->translate(
							'//homekit-connector.cmd.install.messages.portUsed',
							['connector' => $property->getConnector()->getIdentifier()],
						),
					);
				}
			}

			return $answer;
		});

		return intval($io->askQuestion($question));
	}

	private function askDeviceName(Style\SymfonyStyle $io, Entities\HomeKitDevice|null $device = null): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//homekit-connector.cmd.install.questions.provide.device.name'),
			$device?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askDeviceCategory(
		Style\SymfonyStyle $io,
		Entities\HomeKitDevice|null $device = null,
	): Types\AccessoryCategory
	{
		$categories = array_combine(
			array_values(Types\AccessoryCategory::getValues()),
			array_map(
				fn (Types\AccessoryCategory $category): string => $this->translator->translate(
					'//homekit-connector.cmd.base.category.' . $category->getValue(),
				),
				(array) Types\AccessoryCategory::getAvailableEnums(),
			),
		);
		$categories = array_filter(
			$categories,
			fn (string $category): bool => $category !== $this->translator->translate(
				'//homekit-connector.cmd.base.category.' . Types\AccessoryCategory::BRIDGE,
			)
		);
		asort($categories);

		$default = $device !== null ? array_search(
			$this->translator->translate(
				'//homekit-connector.cmd.base.category.' . $device->getAccessoryCategory()->getValue(),
			),
			array_values($categories),
			true,
		) : array_search(
			$this->translator->translate(
				'//homekit-connector.cmd.base.category.' . Types\AccessoryCategory::OTHER,
			),
			array_values($categories),
			true,
		);

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//homekit-connector.cmd.install.questions.select.device.category'),
			array_values($categories),
			$default,
		);
		$question->setErrorMessage(
			$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer) use ($categories): Types\AccessoryCategory {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($categories))) {
				$answer = array_values($categories)[$answer];
			}

			$category = array_search($answer, $categories, true);

			if ($category !== false && Types\AccessoryCategory::isValidValue($category)) {
				return Types\AccessoryCategory::get(intval($category));
			}

			throw new Exceptions\Runtime(
				sprintf($this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'), $answer),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\AccessoryCategory);

		return $answer;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function askServiceType(
		Style\SymfonyStyle $io,
		Entities\HomeKitDevice $device,
	): string
	{
		$findPropertyQuery = new DevicesQueries\Entities\FindDeviceProperties();
		$findPropertyQuery->forDevice($device);
		$findPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::CATEGORY);

		$category = $this->devicesPropertiesRepository->findOneBy(
			$findPropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		if (!$category instanceof DevicesEntities\Devices\Properties\Variable) {
			throw new Exceptions\InvalidState('Device category is not configured');
		}

		if ($category->getValue() === Types\AccessoryCategory::OTHER) {
			$metadata = $this->loader->loadServices();

			$services = array_values(array_keys((array) $metadata));
		} else {
			$metadata = $this->loader->loadAccessories();

			if (!$metadata->offsetExists(strval(MetadataUtilities\ValueHelper::flattenValue($category->getValue())))) {
				throw new Exceptions\InvalidArgument(sprintf(
					'Definition for accessory category: %s was not found',
					strval(MetadataUtilities\ValueHelper::flattenValue($category->getValue())),
				));
			}

			$accessoryMetadata = $metadata->offsetGet(
				strval(MetadataUtilities\ValueHelper::flattenValue($category->getValue())),
			);

			if (
				!$accessoryMetadata instanceof Utils\ArrayHash
				|| !$accessoryMetadata->offsetExists('name')
				|| !is_string($accessoryMetadata->offsetGet('name'))
				|| !$accessoryMetadata->offsetExists('services')
				|| !$accessoryMetadata->offsetGet('services') instanceof Utils\ArrayHash
			) {
				throw new Exceptions\InvalidState('Accessory definition is missing required attributes');
			}

			$services = array_values((array) $accessoryMetadata->offsetGet('services'));
		}

		$question = new Console\Question\ChoiceQuestion(
			'What type of device service you would like to add?',
			$services,
			0,
		);

		$question->setErrorMessage(
			$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($services): string {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($services))) {
				$answer = array_values($services)[$answer];
			}

			return strval($answer);
		});

		return strval($io->askQuestion($question));
	}

	/**
	 * @param array<string> $characteristics
	 * @param array<string> $ignore
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function askCharacteristic(
		Style\SymfonyStyle $io,
		Types\ServiceType $service,
		bool $required = true,
		array $characteristics = [],
		array $ignore = [],
	): string|null
	{
		$metadata = $this->loader->loadServices();

		if (!$metadata->offsetExists($service->getValue())) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for service: %s was not found',
				$service->getValue(),
			));
		}

		$characteristics = array_values(array_diff($characteristics, $ignore));

		if (!$required) {
			$characteristics[] = $this->translator->translate('//homekit-connector.cmd.install.answers.none');
		}

		$question = $required ? new Console\Question\ChoiceQuestion(
			$this->translator->translate(
				'//homekit-connector.cmd.install.questions.select.device.requiredCharacteristic',
			),
			$characteristics,
			0,
		) : new Console\Question\ChoiceQuestion(
			$this->translator->translate(
				'//homekit-connector.cmd.install.questions.select.device.optionalCharacteristic',
			),
			$characteristics,
			count($characteristics) - 1,
		);

		$question->setErrorMessage(
			$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($required, $characteristics): string|null {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($characteristics))) {
				$answer = array_values($characteristics)[$answer];
			}

			if (
				!$required
				&& (
					$answer === $this->translator->translate('//homekit-connector.cmd.install.answers.none')
					|| $answer === strval(count($characteristics) - 1)
				)
			) {
				return null;
			}

			return strval($answer);
		});

		$characteristic = $io->askQuestion($question);

		return $characteristic === null ? null : strval($characteristic);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askProperty(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Properties\Dynamic|null $connectedProperty = null,
	): DevicesEntities\Channels\Properties\Dynamic|null
	{
		$devices = [];

		$connectedChannel = $connectedProperty?->getChannel();
		$connectedDevice = $connectedProperty?->getChannel()->getDevice();

		$findDevicesQuery = new DevicesQueries\Entities\FindDevices();

		$systemDevices = $this->devicesRepository->findAllBy($findDevicesQuery);
		$systemDevices = array_filter($systemDevices, function (DevicesEntities\Devices\Device $device): bool {
			$findChannelsQuery = new DevicesQueries\Entities\FindChannels();
			$findChannelsQuery->forDevice($device);
			$findChannelsQuery->withProperties();

			return $this->channelsRepository->getResultSet($findChannelsQuery)->count() > 0;
		});
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
			if ($device instanceof Entities\HomeKitDevice) {
				continue;
			}

			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			$devices[$device->getId()->toString()] = '[' . ($device->getConnector()->getName() ?? $device->getConnector()->getIdentifier()) . '] '
				. ($device->getName() ?? $device->getIdentifier());
		}

		if (count($devices) === 0) {
			$io->warning($this->translator->translate('//homekit-connector.cmd.install.messages.noHardwareDevices'));

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
			$this->translator->translate('//homekit-connector.cmd.install.questions.select.device.mappedDevice'),
			array_values($devices),
			$default,
		);
		$question->setErrorMessage(
			$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($devices): DevicesEntities\Devices\Device {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($devices))) {
				$answer = array_values($devices)[$answer];
			}

			$identifier = array_search($answer, $devices, true);

			if ($identifier !== false) {
				$findDeviceQuery = new DevicesQueries\Entities\FindDevices();
				$findDeviceQuery->byId(Uuid\Uuid::fromString($identifier));

				$device = $this->devicesRepository->findOneBy($findDeviceQuery);

				if ($device !== null) {
					return $device;
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
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
			$channels[$channel->getIdentifier()] = $channel->getName() ?? $channel->getIdentifier();
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
				'//homekit-connector.cmd.install.questions.select.device.mappedDeviceChannel',
			),
			array_values($channels),
			$default,
		);
		$question->setErrorMessage(
			$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|null $answer) use ($device, $channels): DevicesEntities\Channels\Channel {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($channels))) {
					$answer = array_values($channels)[$answer];
				}

				$identifier = array_search($answer, $channels, true);

				if ($identifier !== false) {
					$findChannelQuery = new DevicesQueries\Entities\FindChannels();
					$findChannelQuery->byIdentifier($identifier);
					$findChannelQuery->forDevice($device);

					$channel = $this->channelsRepository->findOneBy($findChannelQuery);

					if ($channel !== null) {
						return $channel;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$channel = $io->askQuestion($question);
		assert($channel instanceof DevicesEntities\Channels\Channel);

		$properties = [];

		$findDevicePropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findDevicePropertiesQuery->forChannel($channel);

		$channelProperties = $this->channelsPropertiesRepository->findAllBy(
			$findDevicePropertiesQuery,
			DevicesEntities\Channels\Properties\Dynamic::class,
		);
		usort(
			$channelProperties,
			static fn (DevicesEntities\Channels\Properties\Property $a, DevicesEntities\Channels\Properties\Property $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($channelProperties as $property) {
			if (!$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
				continue;
			}

			$properties[$property->getIdentifier()] = $property->getName() ?? $property->getIdentifier();
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
				'//homekit-connector.cmd.install.questions.select.device.mappedChannelProperty',
			),
			array_values($properties),
			$default,
		);
		$question->setErrorMessage(
			$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|null $answer) use ($channel, $properties): DevicesEntities\Channels\Properties\Dynamic {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($properties))) {
					$answer = array_values($properties)[$answer];
				}

				$identifier = array_search($answer, $properties, true);

				if ($identifier !== false) {
					$findPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
					$findPropertyQuery->byIdentifier($identifier);
					$findPropertyQuery->forChannel($channel);

					$property = $this->channelsPropertiesRepository->findOneBy(
						$findPropertyQuery,
						DevicesEntities\Channels\Properties\Dynamic::class,
					);

					if ($property !== null) {
						assert($property instanceof DevicesEntities\Channels\Properties\Dynamic);

						return $property;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$property = $io->askQuestion($question);
		assert($property instanceof DevicesEntities\Channels\Properties\Dynamic);

		return $property;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws Nette\IOException
	 */
	private function askFormat(
		Style\SymfonyStyle $io,
		string $characteristic,
		DevicesEntities\Channels\Properties\Dynamic|null $connectProperty = null,
	): MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\StringEnumFormat|MetadataValueObjects\CombinedEnumFormat|null
	{
		$metadata = $this->loader->loadCharacteristics();

		if (!$metadata->offsetExists($characteristic)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for characteristic: %s was not found',
				$characteristic,
			));
		}

		$characteristicMetadata = $metadata->offsetGet($characteristic);

		if (
			!$characteristicMetadata instanceof Utils\ArrayHash
			|| !$characteristicMetadata->offsetExists('Format')
			|| !is_string($characteristicMetadata->offsetGet('Format'))
			|| !$characteristicMetadata->offsetExists('DataType')
			|| !is_string($characteristicMetadata->offsetGet('DataType'))
		) {
			throw new Exceptions\InvalidState('Characteristic definition is missing required attributes');
		}

		$dataType = MetadataTypes\DataType::get($characteristicMetadata->offsetGet('DataType'));

		$format = null;

		if (
			$characteristicMetadata->offsetExists('MinValue')
			|| $characteristicMetadata->offsetExists('MaxValue')
		) {
			$format = new MetadataValueObjects\NumberRangeFormat([
				$characteristicMetadata->offsetExists('MinValue') ? floatval(
					$characteristicMetadata->offsetGet('MinValue'),
				) : null,
				$characteristicMetadata->offsetExists('MaxValue') ? floatval(
					$characteristicMetadata->offsetGet('MaxValue'),
				) : null,
			]);
		}

		if (
			(
				$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)
				|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
				|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
			)
			&& $characteristicMetadata->offsetExists('ValidValues')
			&& $characteristicMetadata->offsetGet('ValidValues') instanceof Utils\ArrayHash
		) {
			$format = new MetadataValueObjects\StringEnumFormat(
				array_values((array) $characteristicMetadata->offsetGet('ValidValues')),
			);

			if (
				$connectProperty !== null
				&& (
					$connectProperty->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)
					|| $connectProperty->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
					|| $connectProperty->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
				) && (
					$connectProperty->getFormat() instanceof MetadataValueObjects\StringEnumFormat
					|| $connectProperty->getFormat() instanceof MetadataValueObjects\CombinedEnumFormat
				)
			) {
				$mappedFormat = [];

				foreach ($characteristicMetadata->offsetGet('ValidValues') as $name => $item) {
					$options = $connectProperty->getFormat() instanceof MetadataValueObjects\StringEnumFormat
						? $connectProperty->getFormat()->toArray()
						: array_map(
							static function (array $items): array|null {
								if ($items[0] === null) {
									return null;
								}

								return [
									$items[0]->getDataType(),
									strval($items[0]->getValue()),
								];
							},
							$connectProperty->getFormat()->getItems(),
						);

					$question = new Console\Question\ChoiceQuestion(
						$this->translator->translate(
							'//homekit-connector.cmd.install.questions.select.device.valueMapping',
							['value' => $name],
						),
						array_map(
							static fn ($item): string|null => is_array($item) ? $item[1] : $item,
							$options,
						),
					);
					$question->setErrorMessage(
						$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
					);
					$question->setValidator(function (string|null $answer) use ($options): string|array {
						if ($answer === null) {
							throw new Exceptions\Runtime(
								sprintf(
									$this->translator->translate(
										'//homekit-connector.cmd.base.messages.answerNotValid',
									),
									$answer,
								),
							);
						}

						$remappedOptions = array_map(
							static fn ($item): string|null => is_array($item) ? $item[1] : $item,
							$options,
						);

						if (array_key_exists($answer, array_values($remappedOptions))) {
							$answer = array_values($remappedOptions)[$answer];
						}

						if (in_array($answer, $remappedOptions, true) && $answer !== null) {
							$options = array_values(array_filter(
								$options,
								static fn ($item): bool => is_array($item) ? $item[1] === $answer : $item === $answer
							));

							if (count($options) === 1 && $options[0] !== null) {
								return $options[0];
							}
						}

						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
								strval($answer),
							),
						);
					});

					$value = $io->askQuestion($question);
					assert(is_string($value) || is_int($value) || is_array($value));

					$valueDataType = is_array($value) ? strval($value[0]) : null;
					$value = is_array($value) ? $value[1] : $value;

					if (MetadataTypes\SwitchPayload::isValidValue($value)) {
						$valueDataType = MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH;

					} elseif (MetadataTypes\ButtonPayload::isValidValue($value)) {
						$valueDataType = MetadataTypes\DataTypeShort::DATA_TYPE_BUTTON;

					} elseif (MetadataTypes\CoverPayload::isValidValue($value)) {
						$valueDataType = MetadataTypes\DataTypeShort::DATA_TYPE_COVER;
					}

					$mappedFormat[] = [
						[$valueDataType, strval($value)],
						[MetadataTypes\DataTypeShort::DATA_TYPE_UCHAR, strval($item)],
						[MetadataTypes\DataTypeShort::DATA_TYPE_UCHAR, strval($item)],
					];
				}

				$format = new MetadataValueObjects\CombinedEnumFormat($mappedFormat);
			}
		}

		return $format;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function provideCharacteristicValue(
		Style\SymfonyStyle $io,
		string $characteristic,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value = null,
	): string|int|bool|float
	{
		$metadata = $this->loader->loadCharacteristics();

		if (!$metadata->offsetExists($characteristic)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for characteristic: %s was not found',
				$characteristic,
			));
		}

		$characteristicMetadata = $metadata->offsetGet($characteristic);

		if (
			!$characteristicMetadata instanceof Utils\ArrayHash
			|| !$characteristicMetadata->offsetExists('DataType')
			|| !MetadataTypes\DataType::isValidValue($characteristicMetadata->offsetGet('DataType'))
		) {
			throw new Exceptions\InvalidState('Characteristic definition is missing required attributes');
		}

		$dataType = MetadataTypes\DataType::get($characteristicMetadata->offsetGet('DataType'));

		if (
			$characteristicMetadata->offsetExists('ValidValues')
			&& $characteristicMetadata->offsetGet('ValidValues') instanceof Utils\ArrayHash
		) {
			$options = array_combine(
				array_values((array) $characteristicMetadata->offsetGet('ValidValues')),
				array_keys((array) $characteristicMetadata->offsetGet('ValidValues')),
			);

			$question = new Console\Question\ChoiceQuestion(
				$this->translator->translate('//homekit-connector.cmd.install.questions.select.device.value'),
				$options,
				$value !== null ? array_key_exists(
					strval(MetadataUtilities\ValueHelper::flattenValue($value)),
					$options,
				) : null,
			);
			$question->setErrorMessage(
				$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
			);
			$question->setValidator(function (string|int|null $answer) use ($options): string|int {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($options))) {
					$answer = array_values($options)[$answer];
				}

				$value = array_search($answer, $options, true);

				if ($value !== false) {
					return $value;
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			});

			$value = $io->askQuestion($question);
			assert(is_string($value) || is_numeric($value));

			return $value;
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			$question = new Console\Question\ChoiceQuestion(
				$this->translator->translate('//homekit-connector.cmd.install.questions.select.device.value'),
				[
					$this->translator->translate('//homekit-connector.cmd.install.answers.false'),
					$this->translator->translate('//homekit-connector.cmd.install.answers.true'),
				],
				is_bool($value) ? ($value ? 0 : 1) : null,
			);
			$question->setErrorMessage(
				$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
			);
			$question->setValidator(function (string|int|null $answer): bool {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				return boolval($answer);
			});

			$value = $io->askQuestion($question);
			assert(is_bool($value));

			return $value;
		}

		$minValue = $characteristicMetadata->offsetExists('MinValue')
			? floatval(
				$characteristicMetadata->offsetGet('MinValue'),
			)
			: null;
		$maxValue = $characteristicMetadata->offsetExists('MaxValue')
			? floatval(
				$characteristicMetadata->offsetGet('MaxValue'),
			)
			: null;
		$step = $characteristicMetadata->offsetExists('MinStep')
			? floatval(
				$characteristicMetadata->offsetGet('MinStep'),
			)
			: null;

		$question = new Console\Question\Question(
			$this->translator->translate('//homekit-connector.cmd.install.questions.provide.value'),
			is_object($value) ? strval($value) : $value,
		);
		$question->setValidator(
			function (string|int|null $answer) use ($dataType, $minValue, $maxValue, $step): string|int|float {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_STRING)) {
					return strval($answer);
				}

				if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)) {
					if ($minValue !== null && floatval($answer) < $minValue) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
								$answer,
							),
						);
					}

					if ($maxValue !== null && floatval($answer) > $maxValue) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
								$answer,
							),
						);
					}

					if (
						$step !== null
						&& Math\BigDecimal::of($answer)->remainder(
							Math\BigDecimal::of(strval($step)),
						)->toFloat() !== 0.0
					) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
								$answer,
							),
						);
					}

					return floatval($answer);
				}

				if (
					$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_CHAR)
					|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UCHAR)
					|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SHORT)
					|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_USHORT)
					|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_INT)
					|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UINT)
				) {
					if ($minValue !== null && intval($answer) < $minValue) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
								$answer,
							),
						);
					}

					if ($maxValue !== null && intval($answer) > $maxValue) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
								$answer,
							),
						);
					}

					if ($step !== null && intval($answer) % $step !== 0) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
								$answer,
							),
						);
					}

					return intval($answer);
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$value = $io->askQuestion($question);
		assert(is_string($value) || is_int($value) || is_float($value));

		return $value;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\HomeKitConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\HomeKitConnector::class,
		);
		usort(
			$systemConnectors,
			static fn (Entities\HomeKitConnector $a, Entities\HomeKitConnector $b): int => (
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
			$this->translator->translate('//homekit-connector.cmd.install.questions.select.item.connector'),
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer) use ($connectors): Entities\HomeKitConnector {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
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
					Entities\HomeKitConnector::class,
				);

				if ($connector !== null) {
					return $connector;
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$connector = $io->askQuestion($question);
		assert($connector instanceof Entities\HomeKitConnector);

		return $connector;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichDevice(
		Style\SymfonyStyle $io,
		Entities\HomeKitConnector $connector,
	): Entities\HomeKitDevice|null
	{
		$devices = [];

		$findDevicesQuery = new Queries\Entities\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$connectorDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\HomeKitDevice::class,
		);
		usort(
			$connectorDevices,
			static fn (Entities\HomeKitDevice $a, Entities\HomeKitDevice $b): int => (
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
			$this->translator->translate('//homekit-connector.cmd.install.questions.select.item.device'),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connector, $devices): Entities\HomeKitDevice {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
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
						Entities\HomeKitDevice::class,
					);

					if ($device !== null) {
						return $device;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\HomeKitDevice);

		return $device;
	}

	/**
	 * @param array<string, string> $channels
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichService(
		Style\SymfonyStyle $io,
		Entities\HomeKitDevice $device,
		array $channels,
	): Entities\HomeKitChannel|null
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//homekit-connector.cmd.install.questions.select.item.service'),
			array_values($channels),
			count($channels) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
		);

		$serviceIdentifier = array_search($io->askQuestion($question), $channels, true);

		if ($serviceIdentifier === false) {
			$io->error($this->translator->translate('//homekit-connector.cmd.install.messages.serviceNotFound'));

			$this->logger->alert(
				'Could not read service identifier from console answer',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_HOMEKIT,
					'type' => 'devices-cmd',
				],
			);

			return null;
		}

		$findChannelQuery = new Queries\Entities\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier($serviceIdentifier);

		$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\HomeKitChannel::class);

		if ($channel === null) {
			$io->error($this->translator->translate('//homekit-connector.cmd.install.messages.serviceNotFound'));

			$this->logger->alert(
				'Channel was not found',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_HOMEKIT,
					'type' => 'devices-cmd',
				],
			);

			return null;
		}

		return $channel;
	}

	/**
	 * @param array<string, string> $properties
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichCharacteristic(
		Style\SymfonyStyle $io,
		Entities\HomeKitChannel $channel,
		array $properties,
	): DevicesEntities\Channels\Properties\Variable|DevicesEntities\Channels\Properties\Mapped|null
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//homekit-connector.cmd.install.questions.select.item.characteristic'),
			array_values($properties),
		);
		$question->setErrorMessage(
			$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
		);

		$characteristicIdentifier = array_search($io->askQuestion($question), $properties, true);

		if ($characteristicIdentifier === false) {
			$io->error($this->translator->translate('//homekit-connector.cmd.install.messages.characteristicNotFound'));

			$this->logger->alert(
				'Could not read characteristic identifier from console answer',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_HOMEKIT,
					'type' => 'devices-cmd',
				],
			);

			return null;
		}

		$findPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier($characteristicIdentifier);

		$property = $this->channelsPropertiesRepository->findOneBy($findPropertyQuery);

		if ($property === null) {
			$io->error($this->translator->translate('//homekit-connector.cmd.install.messages.characteristicNotFound'));

			$this->logger->alert(
				'Property was not found',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_HOMEKIT,
					'type' => 'devices-cmd',
				],
			);

			return null;
		}

		assert(
			$property instanceof DevicesEntities\Channels\Properties\Variable || $property instanceof DevicesEntities\Channels\Properties\Mapped,
		);

		return $property;
	}

	/**
	 * @return array<string, string>
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	private function getServicesList(Entities\HomeKitDevice $device): array
	{
		$channels = [];

		$findChannelsQuery = new Queries\Entities\FindChannels();
		$findChannelsQuery->forDevice($device);

		$deviceChannels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\HomeKitChannel::class);
		usort(
			$deviceChannels,
			static fn (DevicesEntities\Channels\Channel $a, DevicesEntities\Channels\Channel $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($deviceChannels as $channel) {
			$channels[$channel->getIdentifier()] = $channel->getName() ?? $channel->getIdentifier();
		}

		return $channels;
	}

	/**
	 * @return array<string, string>
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	private function getCharacteristicsList(Entities\HomeKitChannel $channel): array
	{
		$properties = [];

		$findPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findPropertiesQuery->forChannel($channel);

		$channelProperties = $this->channelsPropertiesRepository->findAllBy($findPropertiesQuery);
		usort(
			$channelProperties,
			static fn (DevicesEntities\Channels\Properties\Property $a, DevicesEntities\Channels\Properties\Property $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($channelProperties as $property) {
			$properties[$property->getIdentifier()] = $property->getName() ?? $property->getIdentifier();
		}

		return $properties;
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
