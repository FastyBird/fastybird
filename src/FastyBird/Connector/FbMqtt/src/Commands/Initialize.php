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
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Queries;
use FastyBird\Connector\FbMqtt\Types;
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

	public function __construct(
		private readonly FbMqtt\Logger $logger,
		private readonly DevicesModels\Entities\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Entities\Connectors\ConnectorsManager $connectorsManager,
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Entities\Connectors\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
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
			->setDescription('FB MQTT connector initialization');
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title($this->translator->translate('//fb-mqtt-connector.cmd.initialize.title'));

		$io->note($this->translator->translate('//fb-mqtt-connector.cmd.initialize.subtitle'));

		if ($input->getOption('no-interaction') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//fb-mqtt-connector.cmd.base.questions.continue'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				return Console\Command\Command::SUCCESS;
			}
		}

		$this->askInitializeAction($io);

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function createConfiguration(Style\SymfonyStyle $io): void
	{
		$protocol = $this->askProtocol($io);

		$question = new Console\Question\Question(
			$this->translator->translate('//fb-mqtt-connector.cmd.initialize.questions.provide.identifier'),
		);

		$question->setValidator(function ($answer) {
			if ($answer !== null) {
				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($answer);

				if ($this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\FbMqttConnector::class,
				) !== null) {
					throw new Exceptions\Runtime(
						$this->translator->translate('//fb-mqtt-connector.cmd.initialize.messages.identifier.used'),
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

				if ($this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\FbMqttConnector::class,
				) === null) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error($this->translator->translate('//fb-mqtt-connector.cmd.initialize.messages.identifier.missing'));

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
				'identifier' => Types\ConnectorPropertyIdentifier::PROTOCOL_VERSION,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $protocol->getValue(),
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::SERVER,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $serverAddress,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::PORT,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
				'value' => $serverPort,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::SECURED_PORT,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
				'value' => $serverSecuredPort,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::USERNAME,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $username,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::PASSWORD,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $password,
				'connector' => $connector,
			]));

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//fb-mqtt-connector.cmd.initialize.messages.create.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
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

			$io->error($this->translator->translate('//fb-mqtt-connector.cmd.initialize.messages.create.error'));
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
	private function editConfiguration(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->warning($this->translator->translate('//fb-mqtt-connector.cmd.initialize.messages.noConnectors'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//fb-mqtt-connector.cmd.initialize.questions.create'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createConfiguration($io);
			}

			return;
		}

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::PROTOCOL_VERSION);

		$protocolProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($protocolProperty === null) {
			$changeProtocol = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//fb-mqtt-connector.cmd.initialize.questions.changeProtocol'),
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
				$this->translator->translate('//fb-mqtt-connector.cmd.initialize.questions.disable'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = false;
			}
		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//fb-mqtt-connector.cmd.initialize.questions.enable'),
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
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::SERVER);

		$serverAddressProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::PORT);

		$serverPortProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::SECURED_PORT);

		$serverSecuredProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::USERNAME);

		$usernameProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::PASSWORD);

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
					'identifier' => Types\ConnectorPropertyIdentifier::PROTOCOL_VERSION,
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
					'identifier' => Types\ConnectorPropertyIdentifier::SERVER,
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
					'identifier' => Types\ConnectorPropertyIdentifier::PORT,
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
					'identifier' => Types\ConnectorPropertyIdentifier::SECURED_PORT,
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
					'identifier' => Types\ConnectorPropertyIdentifier::USERNAME,
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
					'identifier' => Types\ConnectorPropertyIdentifier::PASSWORD,
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

			$io->success(
				$this->translator->translate(
					'//fb-mqtt-connector.cmd.initialize.messages.update.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
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

			$io->error($this->translator->translate('//fb-mqtt-connector.cmd.initialize.messages.update.error'));
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
	private function deleteConfiguration(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info($this->translator->translate('//fb-mqtt-connector.cmd.initialize.messages.noConnectors'));

			return;
		}

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
					'//fb-mqtt-connector.cmd.initialize.messages.remove.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
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

			$io->error($this->translator->translate('//fb-mqtt-connector.cmd.initialize.messages.remove.error'));
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
	private function listConfigurations(Style\SymfonyStyle $io): void
	{
		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		$connectors = $this->connectorsRepository->findAllBy($findConnectorsQuery, Entities\FbMqttConnector::class);
		usort(
			$connectors,
			static function (Entities\FbMqttConnector $a, Entities\FbMqttConnector $b): int {
				if ($a->getIdentifier() === $b->getIdentifier()) {
					return $a->getName() <=> $b->getName();
				}

				return $a->getIdentifier() <=> $b->getIdentifier();
			},
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//fb-mqtt-connector.cmd.initialize.data.name'),
			$this->translator->translate('//fb-mqtt-connector.cmd.initialize.data.devicesCnt'),
		]);

		foreach ($connectors as $index => $connector) {
			$findDevicesQuery = new Queries\Entities\FindDevices();
			$findDevicesQuery->forConnector($connector);

			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\FbMqttDevice::class);

			$table->addRow([
				$index + 1,
				$connector->getName() ?? $connector->getIdentifier(),
				count($devices),
			]);
		}

		$table->render();

		$io->newLine();
	}

	private function askProtocol(Style\SymfonyStyle $io): Types\ProtocolVersion
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//fb-mqtt-connector.cmd.initialize.questions.select.protocol'),
			[
				0 => $this->translator->translate('//fb-mqtt-connector.cmd.initialize.answers.protocol.v1'),
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
					'//fb-mqtt-connector.cmd.initialize.answers.dataCentre.centralEurope',
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

	private function askName(Style\SymfonyStyle $io, Entities\FbMqttConnector|null $connector = null): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//fb-mqtt-connector.cmd.initialize.questions.provide.name'),
			$connector?->getName(),
		);

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
			$this->translator->translate('//fb-mqtt-connector.cmd.initialize.questions.provide.address'),
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
	private function askServerPort(Style\SymfonyStyle $io, Entities\FbMqttConnector|null $connector = null): int
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//fb-mqtt-connector.cmd.initialize.questions.provide.port'),
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
	private function askServerSecuredPort(Style\SymfonyStyle $io, Entities\FbMqttConnector|null $connector = null): int
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//fb-mqtt-connector.cmd.initialize.questions.provide.securedPort'),
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
	private function askUsername(Style\SymfonyStyle $io, Entities\FbMqttConnector|null $connector = null): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//fb-mqtt-connector.cmd.initialize.questions.provide.username'),
			$connector?->getUsername(),
		);

		$username = $io->askQuestion($question);

		return strval($username) === '' ? null : strval($username);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askPassword(Style\SymfonyStyle $io, Entities\FbMqttConnector|null $connector = null): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//fb-mqtt-connector.cmd.initialize.questions.provide.password'),
			$connector?->getPassword(),
		);

		$password = $io->askQuestion($question);

		return strval($password) === '' ? null : strval($password);
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
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			static fn (DevicesEntities\Connectors\Connector $a, DevicesEntities\Connectors\Connector $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($systemConnectors as $connector) {
			$connectors[$connector->getIdentifier()] = $connector->getIdentifier()
				. ($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
		}

		if (count($connectors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//fb-mqtt-connector.cmd.initialize.questions.select.connector'),
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
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askInitializeAction(Style\SymfonyStyle $io): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//fb-mqtt-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//fb-mqtt-connector.cmd.initialize.actions.create'),
				1 => $this->translator->translate('//fb-mqtt-connector.cmd.initialize.actions.update'),
				2 => $this->translator->translate('//fb-mqtt-connector.cmd.initialize.actions.remove'),
				3 => $this->translator->translate('//fb-mqtt-connector.cmd.initialize.actions.list'),
				4 => $this->translator->translate('//fb-mqtt-connector.cmd.initialize.actions.nothing'),
			],
			4,
		);

		$question->setErrorMessage(
			$this->translator->translate('//fb-mqtt-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//fb-mqtt-connector.cmd.initialize.actions.create',
			)
			|| $whatToDo === '0'
		) {
			$this->createConfiguration($io);

			$this->askInitializeAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//fb-mqtt-connector.cmd.initialize.actions.update',
			)
			|| $whatToDo === '1'
		) {
			$this->editConfiguration($io);

			$this->askInitializeAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//fb-mqtt-connector.cmd.initialize.actions.remove',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteConfiguration($io);

			$this->askInitializeAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//fb-mqtt-connector.cmd.initialize.actions.list',
			)
			|| $whatToDo === '3'
		) {
			$this->listConfigurations($io);

			$this->askInitializeAction($io);
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
