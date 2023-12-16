<?php declare(strict_types = 1);

/**
 * Install.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           13.12.23
 */

namespace FastyBird\Connector\Tuya\Commands;

use Doctrine\DBAL;
use Doctrine\Persistence;
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Commands as DevicesCommands;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette\Localization;
use Nette\Utils;
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
use function strval;
use function usort;

/**
 * Connector install command
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Install extends Console\Command\Command
{

	public const NAME = 'fb:tuya-connector:install';

	private Input\InputInterface|null $input = null;

	private Output\OutputInterface|null $output = null;

	public function __construct(
		private readonly Tuya\Logger $logger,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Connectors\ConnectorsManager $connectorsManager,
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
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
			->setDescription('Tuya connector installer');
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
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
		$this->input = $input;
		$this->output = $output;

		$io = new Style\SymfonyStyle($this->input, $this->output);

		$io->title($this->translator->translate('//tuya-connector.cmd.install.title'));

		$io->note($this->translator->translate('//tuya-connector.cmd.install.subtitle'));

		$this->askInstallAction($io);

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function createConnector(Style\SymfonyStyle $io): void
	{
		$mode = $this->askConnectorMode($io);

		$question = new Console\Question\Question(
			$this->translator->translate('//tuya-connector.cmd.install.questions.provide.connector.identifier'),
		);

		$question->setValidator(function ($answer) {
			if ($answer !== null) {
				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($answer);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\TuyaConnector::class,
				);

				if ($connector !== null) {
					throw new Exceptions\Runtime(
						$this->translator->translate('//tuya-connector.cmd.install.messages.identifier.connector.used'),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'tuya-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\TuyaConnector::class,
				);

				if ($connector === null) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error(
				$this->translator->translate('//tuya-connector.cmd.install.messages.identifier.connector.missing'),
			);

			return;
		}

		$name = $this->askConnectorName($io);

		$accessId = $this->askConnectorAccessId($io);

		$accessSecret = $this->askConnectorAccessSecret($io);

		$dataCentre = $this->askConnectorOpenApiEndpoint($io);

		$uid = null;

		if ($mode->equalsValue(Types\ClientMode::CLOUD)) {
			$uid = $this->askConnectorUid($io);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\TuyaConnector::class,
				'identifier' => $identifier,
				'name' => $name === '' ? null : $name,
			]));
			assert($connector instanceof Entities\TuyaConnector);

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::CLIENT_MODE,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'value' => $mode->getValue(),
				'format' => [Types\ClientMode::LOCAL, Types\ClientMode::CLOUD],
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::ACCESS_ID,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $accessId,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::ACCESS_SECRET,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $accessSecret,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::OPENAPI_ENDPOINT,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'value' => $dataCentre->getValue(),
				'format' => [
					Types\OpenApiEndpoint::EUROPE,
					Types\OpenApiEndpoint::EUROPE_MS,
					Types\OpenApiEndpoint::AMERICA,
					Types\OpenApiEndpoint::AMERICA_AZURE,
					Types\OpenApiEndpoint::CHINA,
					Types\OpenApiEndpoint::INDIA,
				],
				'connector' => $connector,
			]));

			if ($mode->equalsValue(Types\ClientMode::CLOUD)) {
				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::UID,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $uid,
					'connector' => $connector,
				]));
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$this->databaseHelper->clear();

			$io->success(
				$this->translator->translate(
					'//tuya-connector.cmd.install.messages.create.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'install-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//tuya-connector.cmd.install.messages.create.connector.error'));

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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function editConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->warning($this->translator->translate('//tuya-connector.cmd.base.messages.noConnectors'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//tuya-connector.cmd.install.questions.create.connector'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createConnector($io);
			}

			return;
		}

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::CLIENT_MODE);

		$modeProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($modeProperty === null) {
			$changeMode = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//tuya-connector.cmd.install.questions.change.mode'),
				false,
			);

			$changeMode = (bool) $io->askQuestion($question);
		}

		$mode = null;

		if ($changeMode) {
			$mode = $this->askConnectorMode($io);
		}

		$name = $this->askConnectorName($io, $connector);

		$enabled = $connector->isEnabled();

		if ($connector->isEnabled()) {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//tuya-connector.cmd.install.questions.disable.connector'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = false;
			}
		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//tuya-connector.cmd.install.questions.enable.connector'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = true;
			}
		}

		$accessId = $accessSecret = null;

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::ACCESS_ID);

		$accessIdProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($accessIdProperty === null) {
			$changeAccessId = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//tuya-connector.cmd.install.questions.change.accessId'),
				false,
			);

			$changeAccessId = (bool) $io->askQuestion($question);
		}

		if ($changeAccessId) {
			$accessId = $this->askConnectorAccessId($io, $connector);
		}

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::ACCESS_SECRET);

		$accessSecretProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($accessSecretProperty === null) {
			$changeAccessSecret = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//tuya-connector.cmd.install.questions.change.accessSecret'),
				false,
			);

			$changeAccessSecret = (bool) $io->askQuestion($question);
		}

		if ($changeAccessSecret) {
			$accessSecret = $this->askConnectorAccessSecret($io, $connector);
		}

		$uid = null;
		$uidProperty = null;

		if (
			(
				$modeProperty !== null
				&& $modeProperty->getValue() === Types\ClientMode::CLOUD
			) || (
				$mode !== null
				&& $mode->equalsValue(Types\ClientMode::CLOUD)
			)
		) {
			$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
			$findConnectorPropertyQuery->forConnector($connector);
			$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::UID);

			$uidProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

			if ($uidProperty === null) {
				$changeUid = true;

			} else {
				$question = new Console\Question\ConfirmationQuestion(
					$this->translator->translate('//tuya-connector.cmd.install.questions.change.user'),
					false,
				);

				$changeUid = (bool) $io->askQuestion($question);
			}

			if ($changeUid) {
				$uid = $this->askConnectorUid($io, $connector);
			}
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->update($connector, Utils\ArrayHash::from([
				'name' => $name === '' ? null : $name,
				'enabled' => $enabled,
			]));
			assert($connector instanceof Entities\TuyaConnector);

			if ($modeProperty === null) {
				if ($mode === null) {
					$mode = $this->askConnectorMode($io);
				}

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::CLIENT_MODE,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
					'value' => $mode->getValue(),
					'format' => [Types\ClientMode::LOCAL, Types\ClientMode::CLOUD],
					'connector' => $connector,
				]));
			} elseif ($mode !== null) {
				$this->propertiesManager->update($modeProperty, Utils\ArrayHash::from([
					'value' => $mode->getValue(),
				]));
			}

			if ($accessIdProperty === null) {
				if ($accessId === null) {
					$accessId = $this->askConnectorAccessId($io, $connector);
				}

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::ACCESS_ID,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $accessId,
					'connector' => $connector,
				]));
			} elseif ($accessId !== null) {
				$this->propertiesManager->update($accessIdProperty, Utils\ArrayHash::from([
					'value' => $accessId,
				]));
			}

			if ($accessSecretProperty === null) {
				if ($accessSecret === null) {
					$accessSecret = $this->askConnectorAccessSecret($io, $connector);
				}

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::ACCESS_SECRET,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $accessSecret,
					'connector' => $connector,
				]));
			} elseif ($accessSecret !== null) {
				$this->propertiesManager->update($accessSecretProperty, Utils\ArrayHash::from([
					'value' => $accessSecret,
				]));
			}

			if (
				(
					$modeProperty !== null
					&& $modeProperty->getValue() === Types\ClientMode::CLOUD
				) || (
					$mode !== null
					&& $mode->equalsValue(Types\ClientMode::CLOUD)
				)
			) {
				if ($uidProperty === null) {
					if ($uid === null) {
						$uid = $this->askConnectorUid($io, $connector);
					}

					$this->propertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Connectors\Properties\Variable::class,
						'identifier' => Types\ConnectorPropertyIdentifier::UID,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
						'value' => $uid,
						'connector' => $connector,
					]));
				} elseif ($uid !== null) {
					$this->propertiesManager->update($uidProperty, Utils\ArrayHash::from([
						'value' => $uid,
					]));
				}
			} else {
				if ($uidProperty !== null) {
					$this->propertiesManager->delete($uidProperty);
				}
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$this->databaseHelper->clear();

			$io->success(
				$this->translator->translate(
					'//tuya-connector.cmd.install.messages.update.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'install-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//tuya-connector.cmd.install.messages.update.connector.error'));

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
	private function deleteConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info($this->translator->translate('//tuya-connector.cmd.base.messages.noConnectors'));

			return;
		}

		$io->warning(
			$this->translator->translate(
				'//tuya-connector.cmd.install.messages.remove.connector.confirm',
				['name' => $connector->getName() ?? $connector->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//tuya-connector.cmd.base.questions.continue'),
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

			$this->databaseHelper->clear();

			$io->success(
				$this->translator->translate(
					'//tuya-connector.cmd.install.messages.remove.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'install-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//tuya-connector.cmd.install.messages.remove.connector.error'));
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function manageConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info($this->translator->translate('//tuya-connector.cmd.base.messages.noConnectors'));

			return;
		}

		$this->askManageConnectorAction($io, $connector);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function listConnectors(Style\SymfonyStyle $io): void
	{
		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		$connectors = $this->connectorsRepository->findAllBy($findConnectorsQuery, Entities\TuyaConnector::class);
		usort(
			$connectors,
			static fn (Entities\TuyaConnector $a, Entities\TuyaConnector $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//tuya-connector.cmd.install.data.name'),
			$this->translator->translate('//tuya-connector.cmd.install.data.mode'),
			$this->translator->translate('//tuya-connector.cmd.install.data.devicesCnt'),
		]);

		foreach ($connectors as $index => $connector) {
			$findDevicesQuery = new Queries\Entities\FindDevices();
			$findDevicesQuery->forConnector($connector);

			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\TuyaDevice::class);

			$table->addRow([
				$index + 1,
				$connector->getName() ?? $connector->getIdentifier(),
				$this->translator->translate(
					'//tuya-connector.cmd.base.mode.' . $connector->getClientMode()->getValue(),
				),
				count($devices),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function editDevice(Style\SymfonyStyle $io, Entities\TuyaConnector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->info($this->translator->translate('//tuya-connector.cmd.install.messages.noDevices'));

			return;
		}

		$name = $this->askDeviceName($io, $device);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->update($device, Utils\ArrayHash::from([
				'name' => $name,
			]));
			assert($device instanceof Entities\TuyaDevice);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$this->databaseHelper->clear();

			$io->success(
				$this->translator->translate(
					'//tuya-connector.cmd.install.messages.update.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'install-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//tuya-connector.cmd.install.messages.update.device.error'));

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
	private function deleteDevice(Style\SymfonyStyle $io, Entities\TuyaConnector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->info($this->translator->translate('//tuya-connector.cmd.install.messages.noDevices'));

			return;
		}

		$io->warning(
			$this->translator->translate(
				'//tuya-connector.cmd.install.messages.remove.device.confirm',
				['name' => $device->getName() ?? $device->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//tuya-connector.cmd.base.questions.continue'),
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

			$this->databaseHelper->clear();

			$io->success(
				$this->translator->translate(
					'//tuya-connector.cmd.install.messages.remove.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'install-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//tuya-connector.cmd.install.messages.remove.device.error'));
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
	private function listDevices(Style\SymfonyStyle $io, Entities\TuyaConnector $connector): void
	{
		$findDevicesQuery = new Queries\Entities\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\TuyaDevice::class);
		usort(
			$devices,
			static fn (Entities\TuyaDevice $a, Entities\TuyaDevice $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//tuya-connector.cmd.install.data.name'),
			$this->translator->translate('//tuya-connector.cmd.install.data.model'),
			$this->translator->translate('//tuya-connector.cmd.install.data.ipAddress'),
		]);

		foreach ($devices as $index => $device) {
			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
				$device->getModel(),
				$device->getIpAddress() ?? 'N/A',
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function discoverDevices(Style\SymfonyStyle $io, Entities\TuyaConnector $connector): void
	{
		if ($this->output === null) {
			throw new Exceptions\InvalidState('Something went wrong, console output is not configured');
		}

		$executedTime = $this->dateTimeFactory->getNow();

		$symfonyApp = $this->getApplication();

		if ($symfonyApp === null) {
			throw new Exceptions\InvalidState('Something went wrong, console app is not configured');
		}

		$serviceCmd = $symfonyApp->find(DevicesCommands\Connector::NAME);

		$result = $serviceCmd->run(new Input\ArrayInput([
			'--connector' => $connector->getId()->toString(),
			'--mode' => DevicesCommands\Connector::MODE_DISCOVER,
			'--no-interaction' => true,
			'--quiet' => true,
		]), $this->output);

		$this->databaseHelper->clear();

		if ($result !== Console\Command\Command::SUCCESS) {
			$io->error($this->translator->translate('//tuya-connector.cmd.install.messages.discover.error'));

			return;
		}

		$io->newLine();

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//tuya-connector.cmd.install.data.id'),
			$this->translator->translate('//tuya-connector.cmd.install.data.name'),
			$this->translator->translate('//tuya-connector.cmd.install.data.model'),
			$this->translator->translate('//tuya-connector.cmd.install.data.ipAddress'),
		]);

		$foundDevices = 0;

		$findDevicesQuery = new Queries\Entities\FindDevices();
		$findDevicesQuery->byConnectorId($connector->getId());

		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\TuyaDevice::class);

		foreach ($devices as $device) {
			$createdAt = $device->getCreatedAt();

			if (
				$createdAt !== null
				&& $createdAt->getTimestamp() > $executedTime->getTimestamp()
			) {
				$foundDevices++;

				$table->addRow([
					$foundDevices,
					$device->getId()->toString(),
					$device->getName() ?? $device->getIdentifier(),
					$device->getModel(),
					$device->getIpAddress() ?? 'N/A',
				]);
			}
		}

		if ($foundDevices > 0) {
			$io->newLine();

			$io->info(sprintf(
				$this->translator->translate('//tuya-connector.cmd.install.messages.foundDevices'),
				$foundDevices,
			));

			$table->render();

			$io->newLine();

		} else {
			$io->info($this->translator->translate('//tuya-connector.cmd.install.messages.noDevicesFound'));
		}

		$io->success($this->translator->translate('//tuya-connector.cmd.install.messages.discover.success'));
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askInstallAction(Style\SymfonyStyle $io): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//tuya-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//tuya-connector.cmd.install.actions.create.connector'),
				1 => $this->translator->translate('//tuya-connector.cmd.install.actions.update.connector'),
				2 => $this->translator->translate('//tuya-connector.cmd.install.actions.remove.connector'),
				3 => $this->translator->translate('//tuya-connector.cmd.install.actions.manage.connector'),
				4 => $this->translator->translate('//tuya-connector.cmd.install.actions.list.connectors'),
				5 => $this->translator->translate('//tuya-connector.cmd.install.actions.nothing'),
			],
			5,
		);

		$question->setErrorMessage(
			$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//tuya-connector.cmd.install.actions.create.connector',
			)
			|| $whatToDo === '0'
		) {
			$this->createConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//tuya-connector.cmd.install.actions.update.connector',
			)
			|| $whatToDo === '1'
		) {
			$this->editConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//tuya-connector.cmd.install.actions.remove.connector',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//tuya-connector.cmd.install.actions.manage.connector',
			)
			|| $whatToDo === '3'
		) {
			$this->manageConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//tuya-connector.cmd.install.actions.list.connectors',
			)
			|| $whatToDo === '4'
		) {
			$this->listConnectors($io);

			$this->askInstallAction($io);
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askManageConnectorAction(
		Style\SymfonyStyle $io,
		Entities\TuyaConnector $connector,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//tuya-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//tuya-connector.cmd.install.actions.update.device'),
				1 => $this->translator->translate('//tuya-connector.cmd.install.actions.remove.device'),
				2 => $this->translator->translate('//tuya-connector.cmd.install.actions.list.devices'),
				3 => $this->translator->translate('//tuya-connector.cmd.install.actions.discover.devices'),
				4 => $this->translator->translate('//tuya-connector.cmd.install.actions.nothing'),
			],
			4,
		);

		$question->setErrorMessage(
			$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//tuya-connector.cmd.install.actions.update.device',
			)
			|| $whatToDo === '0'
		) {
			$this->editDevice($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//tuya-connector.cmd.install.actions.remove.device',
			)
			|| $whatToDo === '1'
		) {
			$this->deleteDevice($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//tuya-connector.cmd.install.actions.list.devices',
			)
			|| $whatToDo === '2'
		) {
			$this->listDevices($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//tuya-connector.cmd.install.actions.discover.devices',
			)
			|| $whatToDo === '3'
		) {
			$this->discoverDevices($io, $connector);

			$this->askManageConnectorAction($io, $connector);
		}
	}

	private function askConnectorMode(Style\SymfonyStyle $io): Types\ClientMode
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//tuya-connector.cmd.install.questions.select.connector.mode'),
			[
				0 => $this->translator->translate('//tuya-connector.cmd.install.answers.mode.local'),
				1 => $this->translator->translate('//tuya-connector.cmd.install.answers.mode.cloud'),
			],
			1,
		);

		$question->setErrorMessage(
			$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer): Types\ClientMode {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (
				$answer === $this->translator->translate(
					'//tuya-connector.cmd.install.answers.mode.local',
				)
				|| $answer === '0'
			) {
				return Types\ClientMode::get(Types\ClientMode::LOCAL);
			}

			if (
				$answer === $this->translator->translate(
					'//tuya-connector.cmd.install.answers.mode.cloud',
				)
				|| $answer === '1'
			) {
				return Types\ClientMode::get(Types\ClientMode::CLOUD);
			}

			throw new Exceptions\Runtime(
				sprintf($this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'), $answer),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\ClientMode);

		return $answer;
	}

	private function askConnectorName(
		Style\SymfonyStyle $io,
		Entities\TuyaConnector|null $connector = null,
	): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//tuya-connector.cmd.install.questions.provide.connector.name'),
			$connector?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askConnectorAccessId(Style\SymfonyStyle $io, Entities\TuyaConnector|null $connector = null): string
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//tuya-connector.cmd.install.questions.provide.connector.accessId'),
			$connector?->getAccessId(),
		);
		$question->setValidator(function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			return $answer;
		});

		return strval($io->askQuestion($question));
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askConnectorAccessSecret(
		Style\SymfonyStyle $io,
		Entities\TuyaConnector|null $connector = null,
	): string
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//tuya-connector.cmd.install.questions.provide.connector.accessSecret'),
			$connector?->getAccessSecret(),
		);
		$question->setValidator(function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			return $answer;
		});

		return strval($io->askQuestion($question));
	}

	private function askConnectorOpenApiEndpoint(Style\SymfonyStyle $io): Types\OpenApiEndpoint
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//tuya-connector.cmd.install.questions.select.connector.dataCentre'),
			[
				0 => $this->translator->translate('//tuya-connector.cmd.install.answers.dataCentre.centralEurope'),
				1 => $this->translator->translate('//tuya-connector.cmd.install.answers.dataCentre.westernEurope'),
				2 => $this->translator->translate('//tuya-connector.cmd.install.answers.dataCentre.westernAmerica'),
				3 => $this->translator->translate('//tuya-connector.cmd.install.answers.dataCentre.easternAmerica'),
				4 => $this->translator->translate('//tuya-connector.cmd.install.answers.dataCentre.china'),
				5 => $this->translator->translate('//tuya-connector.cmd.install.answers.dataCentre.india'),
			],
			0,
		);
		$question->setErrorMessage(
			$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer): Types\OpenApiEndpoint {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (
				$answer === $this->translator->translate(
					'//tuya-connector.cmd.install.answers.dataCentre.centralEurope',
				)
				|| $answer === '0'
			) {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::EUROPE);
			}

			if (
				$answer === $this->translator->translate(
					'//tuya-connector.cmd.install.answers.dataCentre.westernEurope',
				)
				|| $answer === '1'
			) {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::EUROPE_MS);
			}

			if (
				$answer === $this->translator->translate(
					'//tuya-connector.cmd.install.answers.dataCentre.westernAmerica',
				)
				|| $answer === '2'
			) {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::AMERICA);
			}

			if (
				$answer === $this->translator->translate(
					'//tuya-connector.cmd.install.answers.dataCentre.easternAmerica',
				)
				|| $answer === '3'
			) {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::AMERICA_AZURE);
			}

			if (
				$answer === $this->translator->translate('//tuya-connector.cmd.install.answers.dataCentre.china')
				|| $answer === '4'
			) {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::CHINA);
			}

			if (
				$answer === $this->translator->translate('//tuya-connector.cmd.install.answers.dataCentre.india')
				|| $answer === '5'
			) {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::INDIA);
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\OpenApiEndpoint);

		return $answer;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askConnectorUid(Style\SymfonyStyle $io, Entities\TuyaConnector|null $connector = null): string
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//tuya-connector.cmd.install.questions.provide.connector.uid'),
			$connector?->getUid(),
		);
		$question->setValidator(function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			return $answer;
		});

		return strval($io->askQuestion($question));
	}

	private function askDeviceName(Style\SymfonyStyle $io, Entities\TuyaDevice|null $device = null): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//tuya-connector.cmd.install.questions.provide.device.name'),
			$device?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\TuyaConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\TuyaConnector::class,
		);
		usort(
			$systemConnectors,
			static fn (Entities\TuyaConnector $a, Entities\TuyaConnector $b): int => (
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
			$this->translator->translate('//tuya-connector.cmd.install.questions.select.item.connector'),
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer) use ($connectors): Entities\TuyaConnector {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
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
					Entities\TuyaConnector::class,
				);

				if ($connector !== null) {
					return $connector;
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$connector = $io->askQuestion($question);
		assert($connector instanceof Entities\TuyaConnector);

		return $connector;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichDevice(
		Style\SymfonyStyle $io,
		Entities\TuyaConnector $connector,
	): Entities\TuyaDevice|null
	{
		$devices = [];

		$findDevicesQuery = new Queries\Entities\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$connectorDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\TuyaDevice::class,
		);
		usort(
			$connectorDevices,
			static fn (Entities\TuyaDevice $a, Entities\TuyaDevice $b): int => (
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
			$this->translator->translate('//tuya-connector.cmd.install.questions.select.item.device'),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connector, $devices): Entities\TuyaDevice {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
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
						Entities\TuyaDevice::class,
					);

					if ($device !== null) {
						return $device;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\TuyaDevice);

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

		throw new Exceptions\Runtime('Database connection could not be established');
	}

}
