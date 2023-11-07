<?php declare(strict_types = 1);

/**
 * Initialize.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           04.08.22
 */

namespace FastyBird\Connector\FbMqtt\Commands;

use Doctrine\DBAL;
use Doctrine\Persistence;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
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
use function array_search;
use function array_values;
use function assert;
use function count;
use function intval;
use function sprintf;
use function strval;
use function usort;

/**
 * Connector initialize command
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Initialize extends Console\Command\Command
{

	public const NAME = 'fb:fb-mqtt-connector:initialize';

	private const CHOICE_QUESTION_CREATE_CONNECTOR = 'Create new connector configuration';

	private const CHOICE_QUESTION_EDIT_CONNECTOR = 'Edit existing connector configuration';

	private const CHOICE_QUESTION_DELETE_CONNECTOR = 'Delete existing connector configuration';

	private const CHOICE_QUESTION_V1_MODE = 'FB MQTT v1 protocol';

	public function __construct(
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Connectors\ConnectorsManager $connectorsManager,
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesManager $propertiesManager,
		private readonly Persistence\ManagerRegistry $managerRegistry,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
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
			->setDescription('FB MQTT connector initialization');
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title('FB MQTT connector - initialization');

		$io->note('This action will create|update|delete connector configuration.');

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
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function createNewConfiguration(Style\SymfonyStyle $io): void
	{
		$protocol = $this->askProtocol($io);

		$question = new Console\Question\Question('Provide connector identifier');

		$question->setValidator(function (string|null $answer) {
			if ($answer !== '' && $answer !== null) {
				$findConnectorQuery = new DevicesQueries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($answer);

				if (
					$this->connectorsRepository->findOneBy(
						$findConnectorQuery,
						Entities\FbMqttConnector::class,
					) !== null
				) {
					throw new Exceptions\Runtime('This identifier is already used');
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'fb-mqtt-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findConnectorQuery = new DevicesQueries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				if (
					$this->connectorsRepository->findOneBy(
						$findConnectorQuery,
						Entities\FbMqttConnector::class,
					) === null
				) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error('Connector identifier have to be provided');

			return;
		}

		$name = $this->askName($io);

		$serverAddress = $this->askServerAddress($io);
		$serverPort = $this->askServerPort($io);
		$serverSecuredPort = $this->askServerSecuredPort($io);
		$username = $this->askUsername($io);
		$password = $this->askPassword($io);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\FbMqttConnector::class,
				'identifier' => $identifier,
				'name' => $name,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_PROTOCOL_VERSION,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $protocol->getValue(),
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_SERVER,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $serverAddress,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_PORT,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
				'value' => $serverPort,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_SECURED_PORT,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
				'value' => $serverSecuredPort,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_USERNAME,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $username,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_PASSWORD,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $password,
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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
					'type' => 'initialize-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
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
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function editExistingConfiguration(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->warning('No FB MQTT connectors registered in system');

			$question = new Console\Question\ConfirmationQuestion(
				'Would you like to create new FB MQTT connector configuration?',
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createNewConfiguration($io);
			}

			return;
		}

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_PROTOCOL_VERSION);

		$protocolProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($protocolProperty === null) {
			$changeProtocol = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				'Do you want to change connector protocol version?',
				false,
			);

			$changeProtocol = (bool) $io->askQuestion($question);
		}

		$protocol = null;

		if ($changeProtocol) {
			$protocol = $this->askProtocol($io);
		}

		$name = $this->askName($io, $connector);

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

		$serverAddress = $this->askServerAddress($io, $connector);
		$serverPort = $this->askServerPort($io, $connector);
		$serverSecuredPort = $this->askServerSecuredPort($io, $connector);
		$username = $this->askUsername($io, $connector);
		$password = $this->askPassword($io, $connector);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_SERVER);

		$serverAddressProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_PORT);

		$serverPortProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_SECURED_PORT);

		$serverSecuredProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_USERNAME);

		$usernameProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_PASSWORD);

		$passwordProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

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
					$protocol = $this->askProtocol($io);
				}

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_PROTOCOL_VERSION,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $protocol->getValue(),
					'connector' => $connector,
				]));
			} elseif ($protocol !== null) {
				$this->propertiesManager->update($protocolProperty, Utils\ArrayHash::from([
					'value' => $protocol->getValue(),
				]));
			}

			if ($serverAddressProperty === null) {
				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_SERVER,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $serverAddress,
					'connector' => $connector,
				]));
			} elseif ($serverAddressProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
				$this->propertiesManager->update($serverAddressProperty, Utils\ArrayHash::from([
					'value' => $serverAddress,
				]));
			}

			if ($serverPortProperty === null) {
				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_PORT,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
					'value' => $serverPort,
					'connector' => $connector,
				]));
			} elseif ($serverPortProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
				$this->propertiesManager->update($serverPortProperty, Utils\ArrayHash::from([
					'value' => $serverPort,
				]));
			}

			if ($serverSecuredProperty === null) {
				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_SECURED_PORT,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
					'value' => $serverSecuredPort,
					'connector' => $connector,
				]));
			} elseif ($serverSecuredProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
				$this->propertiesManager->update($serverSecuredProperty, Utils\ArrayHash::from([
					'value' => $serverSecuredPort,
				]));
			}

			if ($usernameProperty === null) {
				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_USERNAME,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $username,
					'connector' => $connector,
				]));
			} elseif ($usernameProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
				$this->propertiesManager->update($usernameProperty, Utils\ArrayHash::from([
					'value' => $username,
				]));
			}

			if ($passwordProperty === null) {
				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_PASSWORD,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $password,
					'connector' => $connector,
				]));
			} elseif ($passwordProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
				$this->propertiesManager->update($passwordProperty, Utils\ArrayHash::from([
					'value' => $password,
				]));
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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
					'type' => 'initialize-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
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
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info('No FB MQTT connectors registered in system');

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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
					'type' => 'initialize-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
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

	private function askProtocol(Style\SymfonyStyle $io): Types\ProtocolVersion
	{
		$question = new Console\Question\ChoiceQuestion(
			'What type of FB MQTT protocol will this connector handle?',
			[
				self::CHOICE_QUESTION_V1_MODE,
			],
			0,
		);
		$question->setErrorMessage('Selected answer: "%s" is not valid.');
		$question->setValidator(static function (string|null $answer): Types\ProtocolVersion {
			if ($answer === null) {
				throw new Exceptions\InvalidState('Selected answer is not valid');
			}

			if ($answer === self::CHOICE_QUESTION_V1_MODE || $answer === '0') {
				return Types\ProtocolVersion::get(Types\ProtocolVersion::VERSION_1);
			}

			throw new Exceptions\InvalidState('Selected answer is not valid');
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\ProtocolVersion);

		return $answer;
	}

	private function askName(Style\SymfonyStyle $io, Entities\FbMqttConnector|null $connector = null): string|null
	{
		$question = new Console\Question\Question('Provide connector name', $connector?->getName());

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askServerAddress(Style\SymfonyStyle $io, Entities\FbMqttConnector|null $connector = null): string
	{
		$question = new Console\Question\Question(
			'Provide server address',
			$connector?->getServerAddress() ?? Entities\FbMqttConnector::DEFAULT_SERVER_ADDRESS,
		);
		$question->setValidator(static function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime('You have to provide valid server address');
			}

			return $answer;
		});

		return strval($io->askQuestion($question));
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askServerPort(Style\SymfonyStyle $io, Entities\FbMqttConnector|null $connector = null): int
	{
		$question = new Console\Question\Question(
			'Provide server port',
			$connector?->getServerPort() ?? Entities\FbMqttConnector::DEFAULT_SERVER_PORT,
		);
		$question->setValidator(static function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime('You have to provide valid server port');
			}

			return $answer;
		});

		return intval($io->askQuestion($question));
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askServerSecuredPort(Style\SymfonyStyle $io, Entities\FbMqttConnector|null $connector = null): int
	{
		$question = new Console\Question\Question(
			'Provide server secured port',
			$connector?->getServerSecuredPort() ?? Entities\FbMqttConnector::DEFAULT_SERVER_SECURED_PORT,
		);
		$question->setValidator(static function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime('You have to provide valid server secured port');
			}

			return $answer;
		});

		return intval($io->askQuestion($question));
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askUsername(Style\SymfonyStyle $io, Entities\FbMqttConnector|null $connector = null): string|null
	{
		$question = new Console\Question\Question('Provide server username', $connector?->getUsername());

		$username = $io->askQuestion($question);

		return strval($username) === '' ? null : strval($username);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askPassword(Style\SymfonyStyle $io, Entities\FbMqttConnector|null $connector = null): string|null
	{
		$question = new Console\Question\Question('Provide server password', $connector?->getPassword());

		$password = $io->askQuestion($question);

		return strval($password) === '' ? null : strval($password);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\FbMqttConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new DevicesQueries\Entities\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\FbMqttConnector::class,
		);
		usort(
			$systemConnectors,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			static fn (DevicesEntities\Connectors\Connector $a, DevicesEntities\Connectors\Connector $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($systemConnectors as $connector) {
			assert($connector instanceof Entities\FbMqttConnector);

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
		$question->setValidator(function (string|null $answer) use ($connectors): Entities\FbMqttConnector {
			if ($answer === null) {
				throw new Exceptions\InvalidState('Selected answer is not valid');
			}

			if (array_key_exists($answer, array_values($connectors))) {
				$answer = array_values($connectors)[$answer];
			}

			$identifier = array_search($answer, $connectors, true);

			if ($identifier !== false) {
				$findConnectorQuery = new DevicesQueries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\FbMqttConnector::class,
				);
				assert($connector instanceof Entities\FbMqttConnector || $connector === null);

				if ($connector !== null) {
					return $connector;
				}
			}

			throw new Exceptions\InvalidState('Selected answer is not valid');
		});

		$connector = $io->askQuestion($question);
		assert($connector instanceof Entities\FbMqttConnector);

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

		throw new Exceptions\Runtime('Database connection could not be established');
	}

}
