<?php declare(strict_types = 1);

/**
 * Install.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           11.12.23
 */

namespace FastyBird\Connector\FbMqtt\Commands;

use Doctrine\DBAL;
use Doctrine\Persistence;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Queries;
use FastyBird\Connector\FbMqtt\Types;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
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
use function intval;
use function sprintf;
use function strval;
use function usort;

/**
 * Connector install command
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Install extends Console\Command\Command
{

	public const NAME = 'fb:fb-mqtt-connector:install';

	public function __construct(
		private readonly FbMqtt\Logger $logger,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Connectors\ConnectorsManager $connectorsManager,
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesRepository $connectorsPropertiesRepository,
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesManager $connectorsPropertiesManager,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Entities\Devices\DevicesManager $devicesManager,
		private readonly BootstrapHelpers\Database $databaseHelper,
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
			->setDescription('FB MQTT connector installer');
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
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title($this->translator->translate('//fb-mqtt-connector.cmd.install.title'));

		$io->note($this->translator->translate('//fb-mqtt-connector.cmd.install.subtitle'));

		$this->askInstallAction($io);

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function createConnector(Style\SymfonyStyle $io): void
	{
		$protocol = $this->askConnectorProtocol($io);

		$question = new Console\Question\Question(
			$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.provide.connector.identifier'),
		);

		$question->setValidator(function ($answer) {
			if ($answer !== null) {
				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($answer);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\FbMqttConnector::class,
				);

				if ($connector !== null) {
					throw new Exceptions\Runtime(
						$this->translator->translate(
							'//fb-mqtt-connector.cmd.install.messages.identifier.connector.used',
						),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'fb-mqtt-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\FbMqttConnector::class,
				);

				if ($connector === null) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error(
				$this->translator->translate('//fb-mqtt-connector.cmd.install.messages.identifier.connector.missing'),
			);

			return;
		}

		$name = $this->askConnectorName($io);

		$serverAddress = $this->askConnectorServerAddress($io);
		$serverPort = $this->askConnectorServerPort($io);
		$serverSecuredPort = $this->askConnectorServerSecuredPort($io);
		$username = $this->askConnectorUsername($io);
		$password = $this->askConnectorPassword($io);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\FbMqttConnector::class,
				'identifier' => $identifier,
				'name' => $name,
			]));
			assert($connector instanceof Entities\FbMqttConnector);

			$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::PROTOCOL_VERSION,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
				'value' => $protocol->getValue(),
				'connector' => $connector,
			]));

			$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::SERVER,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
				'value' => $serverAddress,
				'connector' => $connector,
			]));

			$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::PORT,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::UINT),
				'value' => $serverPort,
				'connector' => $connector,
			]));

			$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::SECURED_PORT,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::UINT),
				'value' => $serverSecuredPort,
				'connector' => $connector,
			]));

			if ($username !== null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::USERNAME,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
					'value' => $username,
					'connector' => $connector,
				]));
			}

			if ($password !== null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::PASSWORD,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
					'value' => $password,
					'connector' => $connector,
				]));
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//fb-mqtt-connector.cmd.install.messages.create.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_FB_MQTT,
					'type' => 'install-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//fb-mqtt-connector.cmd.install.messages.create.connector.error'));

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.create.devices'),
			true,
		);

		$createDevices = (bool) $io->askQuestion($question);

		if ($createDevices) {
			$connector = $this->connectorsRepository->find($connector->getId(), Entities\FbMqttConnector::class);
			assert($connector instanceof Entities\FbMqttConnector);

			$this->createDevice($io, $connector);
		}
	}

	/**
	 * @throws BootstrapExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function editConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info($this->translator->translate('//fb-mqtt-connector.cmd.base.messages.noConnectors'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.create.connector'),
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
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::PROTOCOL_VERSION);

		$protocolProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($protocolProperty === null) {
			$changeProtocol = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.change.protocol'),
				false,
			);

			$changeProtocol = (bool) $io->askQuestion($question);
		}

		$protocol = null;

		if ($changeProtocol) {
			$protocol = $this->askConnectorProtocol($io);
		}

		$name = $this->askConnectorName($io, $connector);

		$enabled = $connector->isEnabled();

		if ($connector->isEnabled()) {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.disable.connector'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = false;
			}
		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.enable.connector'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = true;
			}
		}

		$serverAddress = $this->askConnectorServerAddress($io, $connector);
		$serverPort = $this->askConnectorServerPort($io, $connector);
		$serverSecuredPort = $this->askConnectorServerSecuredPort($io, $connector);
		$username = $this->askConnectorUsername($io, $connector);
		$password = $this->askConnectorPassword($io, $connector);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::SERVER);

		$serverAddressProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::PORT);

		$serverPortProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::SECURED_PORT);

		$serverSecuredProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::USERNAME);

		$usernameProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::PASSWORD);

		$passwordProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->update($connector, Utils\ArrayHash::from([
				'name' => $name === '' ? null : $name,
				'enabled' => $enabled,
			]));
			assert($connector instanceof Entities\FbMqttConnector);

			if ($protocolProperty === null) {
				if ($protocol === null) {
					$protocol = $this->askConnectorProtocol($io);
				}

				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::PROTOCOL_VERSION,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
					'value' => $protocol->getValue(),
					'connector' => $connector,
				]));
			} elseif ($protocol !== null) {
				$this->connectorsPropertiesManager->update($protocolProperty, Utils\ArrayHash::from([
					'value' => $protocol->getValue(),
				]));
			}

			if ($serverAddressProperty === null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::SERVER,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
					'value' => $serverAddress,
					'connector' => $connector,
				]));
			} elseif ($serverAddressProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
				$this->connectorsPropertiesManager->update($serverAddressProperty, Utils\ArrayHash::from([
					'value' => $serverAddress,
				]));
			}

			if ($serverPortProperty === null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::PORT,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::UINT),
					'value' => $serverPort,
					'connector' => $connector,
				]));
			} elseif ($serverPortProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
				$this->connectorsPropertiesManager->update($serverPortProperty, Utils\ArrayHash::from([
					'value' => $serverPort,
				]));
			}

			if ($serverSecuredProperty === null) {
				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::SECURED_PORT,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::UINT),
					'value' => $serverSecuredPort,
					'connector' => $connector,
				]));
			} elseif ($serverSecuredProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
				$this->connectorsPropertiesManager->update($serverSecuredProperty, Utils\ArrayHash::from([
					'value' => $serverSecuredPort,
				]));
			}

			if ($username !== null) {
				if ($usernameProperty === null) {
					$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Connectors\Properties\Variable::class,
						'identifier' => Types\ConnectorPropertyIdentifier::USERNAME,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
						'value' => $username,
						'connector' => $connector,
					]));
				} elseif ($usernameProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
					$this->connectorsPropertiesManager->update($usernameProperty, Utils\ArrayHash::from([
						'value' => $username,
					]));
				}
			} elseif ($usernameProperty !== null) {
				$this->connectorsPropertiesManager->delete($usernameProperty);
			}

			if ($password !== null) {
				if ($passwordProperty === null) {
					$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Connectors\Properties\Variable::class,
						'identifier' => Types\ConnectorPropertyIdentifier::PASSWORD,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::STRING),
						'value' => $password,
						'connector' => $connector,
					]));
				} elseif ($passwordProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
					$this->connectorsPropertiesManager->update($passwordProperty, Utils\ArrayHash::from([
						'value' => $password,
					]));
				}
			} elseif ($passwordProperty !== null) {
				$this->connectorsPropertiesManager->delete($passwordProperty);
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//fb-mqtt-connector.cmd.install.messages.update.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_FB_MQTT,
					'type' => 'install-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//fb-mqtt-connector.cmd.install.messages.update.connector.error'));

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}

			$this->databaseHelper->clear();
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.manage.devices'),
			false,
		);

		$manage = (bool) $io->askQuestion($question);

		if (!$manage) {
			return;
		}

		$connector = $this->connectorsRepository->find($connector->getId(), Entities\FbMqttConnector::class);
		assert($connector instanceof Entities\FbMqttConnector);

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
			$io->info($this->translator->translate('//fb-mqtt-connector.cmd.base.messages.noConnectors'));

			return;
		}

		$io->warning(
			$this->translator->translate(
				'//fb-mqtt-connector.cmd.install.messages.remove.connector.confirm',
				['name' => $connector->getName() ?? $connector->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//fb-mqtt-connector.cmd.base.questions.continue'),
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
					'//fb-mqtt-connector.cmd.install.messages.remove.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_FB_MQTT,
					'type' => 'install-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//fb-mqtt-connector.cmd.install.messages.remove.connector.error'));
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
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function manageConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info($this->translator->translate('//fb-mqtt-connector.cmd.base.messages.noConnectors'));

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

		$connectors = $this->connectorsRepository->findAllBy($findConnectorsQuery, Entities\FbMqttConnector::class);
		usort(
			$connectors,
			static fn (Entities\FbMqttConnector $a, Entities\FbMqttConnector $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//fb-mqtt-connector.cmd.install.data.name'),
			$this->translator->translate('//fb-mqtt-connector.cmd.install.data.protocol'),
			$this->translator->translate('//fb-mqtt-connector.cmd.install.data.devicesCnt'),
		]);

		foreach ($connectors as $index => $connector) {
			$findDevicesQuery = new Queries\Entities\FindDevices();
			$findDevicesQuery->forConnector($connector);

			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\FbMqttDevice::class);

			$table->addRow([
				$index + 1,
				$connector->getName() ?? $connector->getIdentifier(),
				$this->translator->translate(
					'//fb-mqtt-connector.cmd.base.protocol.' . $connector->getProtocolVersion()->getValue(),
				),
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
	 * @throws Exceptions\Runtime
	 */
	private function createDevice(Style\SymfonyStyle $io, Entities\FbMqttConnector $connector): void
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.provide.device.identifier'),
		);

		$question->setValidator(function (string|null $answer) {
			if ($answer !== '' && $answer !== null) {
				$findDeviceQuery = new Queries\Entities\FindDevices();
				$findDeviceQuery->byIdentifier($answer);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\FbMqttDevice::class) !== null
				) {
					throw new Exceptions\Runtime(
						$this->translator->translate(
							'//fb-mqtt-connector.cmd.install.messages.identifier.device.used',
						),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'fb-mqtt-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findDeviceQuery = new Queries\Entities\FindDevices();
				$findDeviceQuery->byIdentifier($identifier);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\FbMqttDevice::class) === null
				) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error(
				$this->translator->translate('//fb-mqtt-connector.cmd.install.messages.identifier.device.missing'),
			);

			return;
		}

		$name = $this->askDeviceName($io);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->create(Utils\ArrayHash::from([
				'entity' => Entities\FbMqttDevice::class,
				'connector' => $connector,
				'identifier' => $identifier,
				'name' => $name,
			]));
			assert($device instanceof Entities\FbMqttDevice);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//fb-mqtt-connector.cmd.install.messages.create.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_FB_MQTT,
					'type' => 'install-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//fb-mqtt-connector.cmd.install.messages.create.device.error'));
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
	private function editDevice(Style\SymfonyStyle $io, Entities\FbMqttConnector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->info($this->translator->translate('//fb-mqtt-connector.cmd.install.messages.noDevices'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.create.device'),
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
					'//fb-mqtt-connector.cmd.install.messages.update.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_FB_MQTT,
					'type' => 'install-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//fb-mqtt-connector.cmd.install.messages.update.device.error'));
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
	private function deleteDevice(Style\SymfonyStyle $io, Entities\FbMqttConnector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->info($this->translator->translate('//fb-mqtt-connector.cmd.install.messages.noDevices'));

			return;
		}

		$io->warning(
			$this->translator->translate(
				'//fb-mqtt-connector.cmd.install.messages.remove.device.confirm',
				['name' => $device->getName() ?? $device->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//fb-mqtt-connector.cmd.base.questions.continue'),
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
					'//fb-mqtt-connector.cmd.install.messages.remove.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::CONNECTOR_FB_MQTT,
					'type' => 'install-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//fb-mqtt-connector.cmd.install.messages.remove.device.error'));
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
	 */
	private function listDevices(Style\SymfonyStyle $io, Entities\FbMqttConnector $connector): void
	{
		$findDevicesQuery = new Queries\Entities\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\FbMqttDevice::class);
		usort(
			$devices,
			static fn (Entities\FbMqttDevice $a, Entities\FbMqttDevice $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//fb-mqtt-connector.cmd.install.data.name'),
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
	 * @throws BootstrapExceptions\InvalidState
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
			$this->translator->translate('//fb-mqtt-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//fb-mqtt-connector.cmd.install.actions.create.connector'),
				1 => $this->translator->translate('//fb-mqtt-connector.cmd.install.actions.update.connector'),
				2 => $this->translator->translate('//fb-mqtt-connector.cmd.install.actions.remove.connector'),
				3 => $this->translator->translate('//fb-mqtt-connector.cmd.install.actions.manage.connector'),
				4 => $this->translator->translate('//fb-mqtt-connector.cmd.install.actions.list.connectors'),
				5 => $this->translator->translate('//fb-mqtt-connector.cmd.install.actions.nothing'),
			],
			5,
		);

		$question->setErrorMessage(
			$this->translator->translate('//fb-mqtt-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//fb-mqtt-connector.cmd.install.actions.create.connector',
			)
			|| $whatToDo === '0'
		) {
			$this->createConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//fb-mqtt-connector.cmd.install.actions.update.connector',
			)
			|| $whatToDo === '1'
		) {
			$this->editConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//fb-mqtt-connector.cmd.install.actions.remove.connector',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//fb-mqtt-connector.cmd.install.actions.manage.connector',
			)
			|| $whatToDo === '3'
		) {
			$this->manageConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//fb-mqtt-connector.cmd.install.actions.list.connectors',
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
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askManageConnectorAction(
		Style\SymfonyStyle $io,
		Entities\FbMqttConnector $connector,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//fb-mqtt-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//fb-mqtt-connector.cmd.install.actions.create.device'),
				1 => $this->translator->translate('//fb-mqtt-connector.cmd.install.actions.update.device'),
				2 => $this->translator->translate('//fb-mqtt-connector.cmd.install.actions.remove.device'),
				3 => $this->translator->translate('//fb-mqtt-connector.cmd.install.actions.list.devices'),
				4 => $this->translator->translate('//fb-mqtt-connector.cmd.install.actions.nothing'),
			],
			4,
		);

		$question->setErrorMessage(
			$this->translator->translate('//fb-mqtt-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//fb-mqtt-connector.cmd.install.actions.create.device',
			)
			|| $whatToDo === '0'
		) {
			$this->createDevice($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//fb-mqtt-connector.cmd.install.actions.update.device',
			)
			|| $whatToDo === '1'
		) {
			$this->editDevice($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//fb-mqtt-connector.cmd.install.actions.remove.device',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteDevice($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//fb-mqtt-connector.cmd.install.actions.list.devices',
			)
			|| $whatToDo === '3'
		) {
			$this->listDevices($io, $connector);

			$this->askManageConnectorAction($io, $connector);
		}
	}

	private function askConnectorProtocol(Style\SymfonyStyle $io): Types\ProtocolVersion
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.select.connector.protocol'),
			[
				0 => $this->translator->translate('//fb-mqtt-connector.cmd.install.answers.protocol.v1'),
			],
			0,
		);
		$question->setErrorMessage(
			$this->translator->translate('//fb-mqtt-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer): Types\ProtocolVersion {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//fb-mqtt-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (
				$answer === $this->translator->translate(
					'//fb-mqtt-connector.cmd.install.answers.protocol.v1',
				)
				|| $answer === '0'
			) {
				return Types\ProtocolVersion::get(Types\ProtocolVersion::VERSION_1);
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//fb-mqtt-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\ProtocolVersion);

		return $answer;
	}

	private function askConnectorName(
		Style\SymfonyStyle $io,
		Entities\FbMqttConnector|null $connector = null,
	): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.provide.connector.name'),
			$connector?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askConnectorServerAddress(
		Style\SymfonyStyle $io,
		Entities\FbMqttConnector|null $connector = null,
	): string
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.provide.connector.address'),
			$connector?->getServerAddress() ?? Entities\FbMqttConnector::DEFAULT_SERVER_ADDRESS,
		);
		$question->setValidator(function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//fb-mqtt-connector.cmd.base.messages.answerNotValid'),
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
	private function askConnectorServerPort(
		Style\SymfonyStyle $io,
		Entities\FbMqttConnector|null $connector = null,
	): int
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.provide.connector.port'),
			$connector?->getServerPort() ?? Entities\FbMqttConnector::DEFAULT_SERVER_PORT,
		);
		$question->setValidator(function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//fb-mqtt-connector.cmd.base.messages.answerNotValid'),
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
	private function askConnectorServerSecuredPort(
		Style\SymfonyStyle $io,
		Entities\FbMqttConnector|null $connector = null,
	): int
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.provide.connector.securedPort'),
			$connector?->getServerSecuredPort() ?? Entities\FbMqttConnector::DEFAULT_SERVER_SECURED_PORT,
		);
		$question->setValidator(function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//fb-mqtt-connector.cmd.base.messages.answerNotValid'),
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
	private function askConnectorUsername(
		Style\SymfonyStyle $io,
		Entities\FbMqttConnector|null $connector = null,
	): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.provide.connector.username'),
			$connector?->getUsername(),
		);

		$username = $io->askQuestion($question);

		return strval($username) === '' ? null : strval($username);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askConnectorPassword(
		Style\SymfonyStyle $io,
		Entities\FbMqttConnector|null $connector = null,
	): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.provide.connector.password'),
			$connector?->getPassword(),
		);

		$password = $io->askQuestion($question);

		return strval($password) === '' ? null : strval($password);
	}

	private function askDeviceName(Style\SymfonyStyle $io, Entities\FbMqttDevice|null $device = null): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.provide.device.name'),
			$device?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\FbMqttConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\FbMqttConnector::class,
		);
		usort(
			$systemConnectors,
			static fn (Entities\FbMqttConnector $a, Entities\FbMqttConnector $b): int => (
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
			$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.select.item.connector'),
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			$this->translator->translate('//fb-mqtt-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer) use ($connectors): Entities\FbMqttConnector {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//fb-mqtt-connector.cmd.base.messages.answerNotValid'),
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
					Entities\FbMqttConnector::class,
				);

				if ($connector !== null) {
					return $connector;
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//fb-mqtt-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$connector = $io->askQuestion($question);
		assert($connector instanceof Entities\FbMqttConnector);

		return $connector;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichDevice(
		Style\SymfonyStyle $io,
		Entities\FbMqttConnector $connector,
	): Entities\FbMqttDevice|null
	{
		$devices = [];

		$findDevicesQuery = new Queries\Entities\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$connectorDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\FbMqttDevice::class,
		);
		usort(
			$connectorDevices,
			static fn (Entities\FbMqttDevice $a, Entities\FbMqttDevice $b): int => (
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
			$this->translator->translate('//fb-mqtt-connector.cmd.install.questions.select.item.device'),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			$this->translator->translate('//fb-mqtt-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connector, $devices): Entities\FbMqttDevice {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//fb-mqtt-connector.cmd.base.messages.answerNotValid'),
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
						Entities\FbMqttDevice::class,
					);

					if ($device !== null) {
						return $device;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//fb-mqtt-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\FbMqttDevice);

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
