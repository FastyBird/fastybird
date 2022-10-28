<?php declare(strict_types = 1);

/**
 * Initialize.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Commands
 * @since          0.37.0
 *
 * @date           30.07.22
 */

namespace FastyBird\Connector\Shelly\Commands;

use Doctrine\DBAL;
use Doctrine\Persistence;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Types;
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
use function array_search;
use function array_values;
use function count;
use function sprintf;

/**
 * Connector initialize command
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Initialize extends Console\Command\Command
{

	public const NAME = 'fb:shelly-connector:initialize';

	private const CHOICE_QUESTION_CREATE_CONNECTOR = 'Create new connector configuration';

	private const CHOICE_QUESTION_EDIT_CONNECTOR = 'Edit existing connector configuration';

	private const CHOICE_QUESTION_DELETE_CONNECTOR = 'Delete existing connector configuration';

	private const CHOICE_QUESTION_GEN_1_CONNECTOR = 'Original generation 1 devices (based on ESP8266)';

	private const CHOICE_QUESTION_GEN_2_CONNECTOR = 'New generation 2 devices (based on ESP32)';

	private const CHOICE_QUESTION_CLOUD_CONTROL_API_CONNECTOR = 'Cloud control API';

	private const CHOICE_QUESTION_INTEGRATOR_API_CONNECTOR = 'Integrator API';

	private const CHOICE_QUESTION_GEN_1_MODE_CLASSIC = 'Classic client HTTP/CoAP mode';

	private const CHOICE_QUESTION_GEN_1_MODE_MQTT = 'MQTT client mode';

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Connectors\ConnectorsManager $connectorsManager,
		private readonly DevicesModels\Connectors\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Connectors\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModels\Connectors\Controls\ControlsManager $controlsManager,
		private readonly DevicesModels\DataStorage\ConnectorsRepository $connectorsDataStorageRepository,
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
			->setDescription('Shelly connector initialization')
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
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title('Shelly connector - initialization');

		$io->note('This action will create|update connector configuration.');

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
				0 => self::CHOICE_QUESTION_CREATE_CONNECTOR,
				1 => self::CHOICE_QUESTION_EDIT_CONNECTOR,
				2 => self::CHOICE_QUESTION_DELETE_CONNECTOR,
			],
		);

		$question->setErrorMessage('Selected answer: "%s" is not valid.');

		$whatToDo = $io->askQuestion($question);

		if ($whatToDo === self::CHOICE_QUESTION_CREATE_CONNECTOR) {
			$this->createNewConfiguration($io);

		} elseif ($whatToDo === self::CHOICE_QUESTION_EDIT_CONNECTOR) {
			$this->editExistingConfiguration($io);

		} elseif ($whatToDo === self::CHOICE_QUESTION_DELETE_CONNECTOR) {
			$this->deleteExistingConfiguration($io);
		}

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function createNewConfiguration(Style\SymfonyStyle $io): void
	{
		$generation = $this->askGeneration($io);

		$question = new Console\Question\Question('Provide connector identifier');

		$question->setValidator(function ($answer) {
			if ($answer !== null && $this->connectorsDataStorageRepository->findByIdentifier($answer) !== null) {
				throw new Exceptions\Runtime('This identifier is already used');
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'shelly-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				if ($this->connectorsDataStorageRepository->findByIdentifier($identifier) === null) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error('Connector identifier have to provided');

			return;
		}

		$question = new Console\Question\Question('Provide connector name');

		$name = $io->askQuestion($question);

		$clientMode = null;

		$cloudAuthKey = null;
		$cloudServer = null;

		if ($generation->getValue() === Types\ClientVersion::TYPE_GEN_1) {
			$question = new Console\Question\ChoiceQuestion(
				'In what communication mode should this connector communicate?',
				[
					self::CHOICE_QUESTION_GEN_1_MODE_CLASSIC,
					self::CHOICE_QUESTION_GEN_1_MODE_MQTT,
				],
				0,
			);

			$question->setErrorMessage('Selected answer: "%s" is not valid.');

			$clientModeAnswer = $io->askQuestion($question);

			if ($clientModeAnswer === self::CHOICE_QUESTION_GEN_1_MODE_CLASSIC) {
				$clientMode = Types\ClientMode::get(Types\ClientMode::TYPE_GEN_1_CLASSIC);
			}

			if ($clientModeAnswer === self::CHOICE_QUESTION_GEN_1_MODE_MQTT) {
				$clientMode = Types\ClientMode::get(Types\ClientMode::TYPE_GEN_1_MQTT);
			}
		} elseif ($generation->getValue() === Types\ClientVersion::TYPE_CLOUD) {
			$question = new Console\Question\Question('Provide cloud authentication key');

			$cloudAuthKey = $io->askQuestion($question);

			$question = new Console\Question\Question('Provide cloud server address');

			$cloudServer = $io->askQuestion($question);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\ShellyConnector::class,
				'identifier' => $identifier,
				'name' => $name === '' ? null : $name,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_VERSION,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $generation->getValue(),
				'connector' => $connector,
			]));

			if ($generation->getValue() === Types\ClientVersion::TYPE_GEN_1) {
				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $clientMode?->getValue(),
					'connector' => $connector,
				]));
			} elseif ($generation->getValue() === Types\ClientVersion::TYPE_CLOUD) {
				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_CLOUD_AUTH_KEY,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $cloudAuthKey,
					'connector' => $connector,
				]));

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_CLOUD_SERVER,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $cloudServer,
					'connector' => $connector,
				]));
			}

			$this->controlsManager->create(Utils\ArrayHash::from([
				'name' => Types\ConnectorControlName::NAME_REBOOT,
				'connector' => $connector,
			]));

			$this->controlsManager->create(Utils\ArrayHash::from([
				'name' => Types\ConnectorControlName::NAME_DISCOVER,
				'connector' => $connector,
			]));

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(sprintf(
				'New connector "%s" was successfully created',
				$connector->getName() ?? $connector->getIdentifier(),
			));
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'initialize-cmd',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
				],
			);

			$io->error('Something went wrong, connector could not be created. Error was logged.');
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
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function editExistingConfiguration(Style\SymfonyStyle $io): void
	{
		$io->newLine();

		$connectors = [];

		foreach ($this->connectorsDataStorageRepository as $connector) {
			if ($connector->getType() !== Entities\ShellyConnector::CONNECTOR_TYPE) {
				continue;
			}

			$connectors[$connector->getIdentifier()] = $connector->getIdentifier()
				. ($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
		}

		if (count($connectors) === 0) {
			$io->warning('No Shelly connectors registered in system');

			$question = new Console\Question\ConfirmationQuestion(
				'Would you like to create new Shelly connector configuration?',
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createNewConfiguration($io);
			}

			return;
		}

		$question = new Console\Question\ChoiceQuestion(
			'Please select connector to configure',
			array_values($connectors),
		);

		$question->setErrorMessage('Selected connector: "%s" is not valid.');

		$connectorIdentifier = array_search($io->askQuestion($question), $connectors, true);

		if ($connectorIdentifier === false) {
			$io->error('Something went wrong, connector could not be loaded');

			$this->logger->alert(
				'Connector identifier was not able to get from answer',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'initialize-cmd',
				],
			);

			return;
		}

		$findConnectorQuery = new DevicesQueries\FindConnectors();
		$findConnectorQuery->byIdentifier($connectorIdentifier);

		$connector = $this->connectorsRepository->findOneBy($findConnectorQuery);

		if ($connector === null) {
			$io->error('Something went wrong, connector could not be loaded');

			$this->logger->alert(
				'Connector was not found',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'initialize-cmd',
				],
			);

			return;
		}

		$findPropertyQuery = new DevicesQueries\FindConnectorProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_VERSION);

		$versionProperty = $this->propertiesRepository->findOneBy($findPropertyQuery);

		if ($versionProperty === null) {
			$changeGeneration = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				'Do you want to change connector devices support?',
				false,
			);

			$changeGeneration = (bool) $io->askQuestion($question);
		}

		$generation = null;

		if ($changeGeneration) {
			$generation = $this->askGeneration($io);
		}

		$question = new Console\Question\Question('Provide connector name', $connector->getName());

		$name = $io->askQuestion($question);

		$clientMode = null;
		$clientModeProperty = null;

		$cloudAuthKey = null;
		$cloudAuthKeyProperty = null;
		$cloudServer = null;
		$cloudServerProperty = null;

		if (
			$versionProperty?->getValue() === Types\ClientVersion::TYPE_GEN_1
			|| $generation?->getValue() === Types\ClientVersion::TYPE_GEN_1
		) {
			$findPropertyQuery = new DevicesQueries\FindConnectorProperties();
			$findPropertyQuery->forConnector($connector);
			$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE);

			$clientModeProperty = $this->propertiesRepository->findOneBy($findPropertyQuery);

			if ($clientModeProperty !== null) {
				$question = new Console\Question\ConfirmationQuestion(
					'Do you want to change connector client mode?',
					false,
				);

				$changeClientMode = (bool) $io->askQuestion($question);
			}

			if ($clientModeProperty === null || $changeClientMode) {
				$question = new Console\Question\ChoiceQuestion(
					'In what communication mode should this connector communicate?',
					[
						self::CHOICE_QUESTION_GEN_1_MODE_CLASSIC,
						self::CHOICE_QUESTION_GEN_1_MODE_MQTT,
					],
					0,
				);

				$question->setErrorMessage('Selected answer: "%s" is not valid.');

				$clientModeAnswer = $io->askQuestion($question);

				if ($clientModeAnswer === self::CHOICE_QUESTION_GEN_1_MODE_CLASSIC) {
					$clientMode = Types\ClientMode::get(Types\ClientMode::TYPE_GEN_1_CLASSIC);
				}

				if ($clientModeAnswer === self::CHOICE_QUESTION_GEN_1_MODE_MQTT) {
					$clientMode = Types\ClientMode::get(Types\ClientMode::TYPE_GEN_1_MQTT);
				}
			}
		} elseif (
			$versionProperty?->getValue() === Types\ClientVersion::TYPE_CLOUD
			|| $generation->getValue() === Types\ClientVersion::TYPE_CLOUD
		) {
			$findPropertyQuery = new DevicesQueries\FindConnectorProperties();
			$findPropertyQuery->forConnector($connector);
			$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_CLOUD_AUTH_KEY);

			$cloudAuthKeyProperty = $this->propertiesRepository->findOneBy($findPropertyQuery);

			$changeCloudAuthKey = false;

			if ($cloudAuthKeyProperty !== null) {
				$question = new Console\Question\ConfirmationQuestion(
					'Do you want to change connector cloud authentication key?',
					false,
				);

				$changeCloudAuthKey = (bool) $io->askQuestion($question);
			}

			if ($cloudAuthKeyProperty === null || $changeCloudAuthKey) {
				$question = new Console\Question\Question('Provide cloud authentication key');

				$cloudAuthKey = $io->askQuestion($question);
			}

			$findPropertyQuery = new DevicesQueries\FindConnectorProperties();
			$findPropertyQuery->forConnector($connector);
			$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_CLOUD_SERVER);

			$cloudServerProperty = $this->propertiesRepository->findOneBy($findPropertyQuery);

			$changeCloudServer = false;

			if ($cloudServerProperty !== null) {
				$question = new Console\Question\ConfirmationQuestion(
					'Do you want to change connector clout server address?',
					false,
				);

				$changeCloudServer = (bool) $io->askQuestion($question);
			}

			if ($cloudServerProperty === null || $changeCloudServer) {
				$question = new Console\Question\Question('Provide cloud server address');

				$cloudServer = $io->askQuestion($question);
			}
		}

		$enabled = $connector->isEnabled();

		if ($connector->isEnabled()) {
			$question = new Console\Question\ConfirmationQuestion(
				'Do you want to disable connector?',
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = false;
			}
		} else {
			$question = new Console\Question\ConfirmationQuestion(
				'Do you want to enable connector?',
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = true;
			}
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->update($connector, Utils\ArrayHash::from([
				'name' => $name === '' ? null : $name,
				'enabled' => $enabled,
			]));

			if ($versionProperty === null) {
				if ($generation === null) {
					$generation = $this->askGeneration($io);
				}

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_VERSION,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $generation->getValue(),
					'connector' => $connector,
				]));
			} elseif ($generation !== null) {
				$this->propertiesManager->update($versionProperty, Utils\ArrayHash::from([
					'value' => $generation->getValue(),
				]));
			}

			if (
				$versionProperty?->getValue() === Types\ClientVersion::TYPE_GEN_1
				|| $generation?->getValue() === Types\ClientVersion::TYPE_GEN_1
			) {
				if ($clientMode !== null) {
					if ($clientModeProperty !== null) {
						$this->propertiesManager->update($clientModeProperty, Utils\ArrayHash::from([
							'value' => $clientMode->getValue(),
						]));
					} else {
						$this->propertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Connectors\Properties\Variable::class,
							'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE,
							'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
							'value' => $clientMode->getValue(),
							'connector' => $connector,
						]));
					}
				}
			} elseif (
				$versionProperty?->getValue() === Types\ClientVersion::TYPE_CLOUD
				|| $generation->getValue() === Types\ClientVersion::TYPE_CLOUD
			) {
				if ($cloudAuthKeyProperty !== null) {
					$this->propertiesManager->update($cloudAuthKeyProperty, Utils\ArrayHash::from([
						'value' => $cloudAuthKey,
					]));
				} else {
					$this->propertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Connectors\Properties\Variable::class,
						'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_CLOUD_AUTH_KEY,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
						'value' => $cloudAuthKey,
						'connector' => $connector,
					]));
				}

				if ($cloudServerProperty !== null) {
					$this->propertiesManager->update($cloudServerProperty, Utils\ArrayHash::from([
						'value' => $cloudServer,
					]));
				} else {
					$this->propertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Connectors\Properties\Variable::class,
						'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_CLOUD_SERVER,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
						'value' => $cloudServer,
						'connector' => $connector,
					]));
				}

				if ($clientModeProperty !== null) {
					$this->propertiesManager->delete($clientModeProperty);
				}
			} else {
				if ($clientModeProperty !== null) {
					$this->propertiesManager->delete($clientModeProperty);
				}
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(sprintf(
				'Connector "%s" was successfully updated',
				$connector->getName() ?? $connector->getIdentifier(),
			));
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'initialize-cmd',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
				],
			);

			$io->error('Something went wrong, connector could not be updated. Error was logged.');
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
	private function deleteExistingConfiguration(Style\SymfonyStyle $io): void
	{
		$io->newLine();

		$connectors = [];

		foreach ($this->connectorsDataStorageRepository as $connector) {
			if ($connector->getType() !== Entities\ShellyConnector::CONNECTOR_TYPE) {
				continue;
			}

			$connectors[$connector->getIdentifier()] = $connector->getIdentifier()
				. ($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
		}

		if (count($connectors) === 0) {
			$io->info('No Shelly connectors registered in system');

			return;
		}

		$question = new Console\Question\ChoiceQuestion(
			'Please select connector to remove',
			array_values($connectors),
		);

		$question->setErrorMessage('Selected connector: "%s" is not valid.');

		$connectorIdentifier = array_search($io->askQuestion($question), $connectors, true);

		if ($connectorIdentifier === false) {
			$io->error('Something went wrong, connector could not be loaded');

			$this->logger->alert(
				'Connector identifier was not able to get from answer',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'initialize-cmd',
				],
			);

			return;
		}

		$findConnectorQuery = new DevicesQueries\FindConnectors();
		$findConnectorQuery->byIdentifier($connectorIdentifier);

		$connector = $this->connectorsRepository->findOneBy($findConnectorQuery);

		if ($connector === null) {
			$io->error('Something went wrong, connector could not be loaded');

			$this->logger->alert(
				'Connector was not found',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'initialize-cmd',
				],
			);

			return;
		}

		$question = new Console\Question\ConfirmationQuestion(
			'Would you like to continue?',
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if ($continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->connectorsManager->delete($connector);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(sprintf(
				'Connector "%s" was successfully removed',
				$connector->getName() ?? $connector->getIdentifier(),
			));
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'initialize-cmd',
					'exception' => [
						'message' => $ex->getMessage(),
						'code' => $ex->getCode(),
					],
				],
			);

			$io->error('Something went wrong, connector could not be removed. Error was logged.');
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function askGeneration(Style\SymfonyStyle $io): Types\ClientVersion
	{
		$question = new Console\Question\ChoiceQuestion(
			'What generation of Shelly devices should this connector handle?',
			[
				self::CHOICE_QUESTION_GEN_1_CONNECTOR,
				self::CHOICE_QUESTION_GEN_2_CONNECTOR,
				self::CHOICE_QUESTION_CLOUD_CONTROL_API_CONNECTOR,
				self::CHOICE_QUESTION_INTEGRATOR_API_CONNECTOR,
			],
			0,
		);

		$question->setErrorMessage('Selected answer: "%s" is not valid.');

		$generation = $io->askQuestion($question);

		if ($generation === self::CHOICE_QUESTION_GEN_1_CONNECTOR) {
			return Types\ClientVersion::get(Types\ClientVersion::TYPE_GEN_1);
		}

		if ($generation === self::CHOICE_QUESTION_GEN_2_CONNECTOR) {
			return Types\ClientVersion::get(Types\ClientVersion::TYPE_GEN_2);
		}

		if ($generation === self::CHOICE_QUESTION_CLOUD_CONTROL_API_CONNECTOR) {
			return Types\ClientVersion::get(Types\ClientVersion::TYPE_CLOUD);
		}

		if ($generation === self::CHOICE_QUESTION_INTEGRATOR_API_CONNECTOR) {
			return Types\ClientVersion::get(Types\ClientVersion::TYPE_INTEGRATOR);
		}

		throw new Exceptions\InvalidState('Unknown connector version selected');
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
