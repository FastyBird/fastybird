<?php declare(strict_types = 1);

/**
 * Initialize.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           04.08.22
 */

namespace FastyBird\Connector\Tuya\Commands;

use Doctrine\DBAL;
use Doctrine\Persistence;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Types;
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
use function sprintf;
use function strval;
use function usort;

/**
 * Connector initialize command
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Initialize extends Console\Command\Command
{

	public const NAME = 'fb:tuya-connector:initialize';

	private const CHOICE_QUESTION_CREATE_CONNECTOR = 'Create new connector configuration';

	private const CHOICE_QUESTION_EDIT_CONNECTOR = 'Edit existing connector configuration';

	private const CHOICE_QUESTION_DELETE_CONNECTOR = 'Delete existing connector configuration';

	private const CHOICE_QUESTION_LOCAL_MODE = 'Local network mode';

	private const CHOICE_QUESTION_CLOUD_MODE = 'Cloud server mode';

	private const CHOICE_QUESTION_CENTRAL_EUROPE_DC = 'Central Europe';

	private const CHOICE_QUESTION_WESTERN_EUROPE_DC = 'Western Europe';

	private const CHOICE_QUESTION_WESTERN_AMERICA_DC = 'Western America';

	private const CHOICE_QUESTION_EASTERN_AMERICA_DC = 'Eastern America';

	private const CHOICE_QUESTION_CHINA_DC = 'China';

	private const CHOICE_QUESTION_INDIA_DC = 'India';

	public function __construct(
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Connectors\ConnectorsManager $connectorsManager,
		private readonly DevicesModels\Connectors\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Connectors\Properties\PropertiesManager $propertiesManager,
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
			->setDescription('Tuya connector initialization');
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

		$io->title('Tuya connector - initialization');

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
	 */
	private function createNewConfiguration(Style\SymfonyStyle $io): void
	{
		$mode = $this->askMode($io);

		$question = new Console\Question\Question('Provide connector identifier');

		$question->setValidator(function (string|null $answer) {
			if ($answer !== '' && $answer !== null) {
				$findConnectorQuery = new DevicesQueries\FindConnectors();
				$findConnectorQuery->byIdentifier($answer);

				if ($this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\TuyaConnector::class,
				) !== null) {
					throw new Exceptions\Runtime('This identifier is already used');
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'tuya-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findConnectorQuery = new DevicesQueries\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				if ($this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\TuyaConnector::class,
				) === null) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error('Connector identifier have to provided');

			return;
		}

		$name = $this->askName($io);

		$accessId = $this->askAccessId($io);

		$accessSecret = $this->askAccessSecret($io);

		$dataCentre = $this->askOpenApiEndpoint($io);

		$uid = null;

		if ($mode->equalsValue(Types\ClientMode::MODE_CLOUD)) {
			$uid = $this->askUid($io);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\TuyaConnector::class,
				'identifier' => $identifier,
				'name' => $name === '' ? null : $name,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE,
				'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'value' => $mode->getValue(),
				'format' => [Types\ClientMode::MODE_LOCAL, Types\ClientMode::MODE_CLOUD],
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID,
				'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $accessId,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_SECRET,
				'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_SECRET),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $accessSecret,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENAPI_ENDPOINT,
				'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENAPI_ENDPOINT),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'value' => $dataCentre->getValue(),
				'format' => [
					Types\OpenApiEndpoint::ENDPOINT_EUROPE,
					Types\OpenApiEndpoint::ENDPOINT_EUROPE_MS,
					Types\OpenApiEndpoint::ENDPOINT_AMERICA,
					Types\OpenApiEndpoint::ENDPOINT_AMERICA_AZURE,
					Types\OpenApiEndpoint::ENDPOINT_CHINA,
					Types\OpenApiEndpoint::ENDPOINT_INDIA,
				],
				'connector' => $connector,
			]));

			if ($mode->equalsValue(Types\ClientMode::MODE_CLOUD)) {
				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_UID,
					'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_UID),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $uid,
					'connector' => $connector,
				]));
			}

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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
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
			$io->warning('No Tuya connectors registered in system');

			$question = new Console\Question\ConfirmationQuestion(
				'Would you like to create new Tuya connector configuration?',
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createNewConfiguration($io);
			}

			return;
		}

		$findConnectorPropertyQuery = new DevicesQueries\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE);

		$modeProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($modeProperty === null) {
			$changeMode = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				'Do you want to change connector devices support?',
				false,
			);

			$changeMode = (bool) $io->askQuestion($question);
		}

		$mode = null;

		if ($changeMode) {
			$mode = $this->askMode($io);
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

		$accessId = $accessSecret = null;

		$findConnectorPropertyQuery = new DevicesQueries\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID);

		$accessIdProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($accessIdProperty === null) {
			$changeAccessId = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				'Do you want to change connector cloud Access ID?',
				false,
			);

			$changeAccessId = (bool) $io->askQuestion($question);
		}

		if ($changeAccessId) {
			$accessId = $this->askAccessId($io);
		}

		$findConnectorPropertyQuery = new DevicesQueries\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_SECRET);

		$accessSecretProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($accessSecretProperty === null) {
			$changeAccessSecret = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				'Do you want to change connector cloud Access Secret?',
				false,
			);

			$changeAccessSecret = (bool) $io->askQuestion($question);
		}

		if ($changeAccessSecret) {
			$accessSecret = $this->askAccessSecret($io);
		}

		$uid = null;
		$uidProperty = null;

		if (
			(
				$modeProperty !== null
				&& $modeProperty->getValue() === Types\ClientMode::MODE_CLOUD
			) || (
				$mode !== null
				&& $mode->equalsValue(Types\ClientMode::MODE_CLOUD)
			)
		) {
			$findConnectorPropertyQuery = new DevicesQueries\FindConnectorProperties();
			$findConnectorPropertyQuery->forConnector($connector);
			$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_UID);

			$uidProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

			if ($uidProperty === null) {
				$changeUid = true;

			} else {
				$question = new Console\Question\ConfirmationQuestion(
					'Do you want to change connector cloud user identifier?',
					false,
				);

				$changeUid = (bool) $io->askQuestion($question);
			}

			if ($changeUid) {
				$uid = $this->askUid($io);
			}
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->update($connector, Utils\ArrayHash::from([
				'name' => $name === '' ? null : $name,
				'enabled' => $enabled,
			]));

			if ($modeProperty === null) {
				if ($mode === null) {
					$mode = $this->askMode($io);
				}

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE,
					'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
					'value' => $mode->getValue(),
					'format' => [Types\ClientMode::MODE_LOCAL, Types\ClientMode::MODE_CLOUD],
					'connector' => $connector,
				]));
			} elseif ($mode !== null) {
				$this->propertiesManager->update($modeProperty, Utils\ArrayHash::from([
					'value' => $mode->getValue(),
				]));
			}

			if ($accessIdProperty === null) {
				if ($accessId === null) {
					$accessId = $this->askAccessId($io);
				}

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID,
					'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_ID),
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
					$accessSecret = $this->askAccessSecret($io);
				}

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_SECRET,
					'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_ACCESS_SECRET),
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
					&& $modeProperty->getValue() === Types\ClientMode::MODE_CLOUD
				) || (
					$mode !== null
					&& $mode->equalsValue(Types\ClientMode::MODE_CLOUD)
				)
			) {
				if ($uidProperty === null) {
					if ($uid === null) {
						$uid = $this->askUid($io);
					}

					$this->propertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Connectors\Properties\Variable::class,
						'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_UID,
						'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::IDENTIFIER_UID),
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

			$io->success(sprintf(
				'Connector "%s" was successfully updated',
				$connector->getName() ?? $connector->getIdentifier(),
			));
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
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
			$io->info('No Tuya connectors registered in system');

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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
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

	private function askMode(Style\SymfonyStyle $io): Types\ClientMode
	{
		$question = new Console\Question\ChoiceQuestion(
			'In what mode should this connector communicate with devices?',
			[
				self::CHOICE_QUESTION_LOCAL_MODE,
				self::CHOICE_QUESTION_CLOUD_MODE,
			],
			0,
		);

		$question->setErrorMessage('Selected answer: "%s" is not valid.');
		$question->setValidator(static function (string|null $answer): Types\ClientMode {
			if ($answer === null) {
				throw new Exceptions\InvalidState('Selected answer is not valid');
			}

			if ($answer === self::CHOICE_QUESTION_LOCAL_MODE || $answer === '0') {
				return Types\ClientMode::get(Types\ClientMode::MODE_LOCAL);
			}

			if ($answer === self::CHOICE_QUESTION_CLOUD_MODE || $answer === '1') {
				return Types\ClientMode::get(Types\ClientMode::MODE_CLOUD);
			}

			throw new Exceptions\InvalidState('Selected answer is not valid');
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\ClientMode);

		return $answer;
	}

	private function askName(Style\SymfonyStyle $io, Entities\TuyaConnector|null $connector = null): string|null
	{
		$question = new Console\Question\Question('Provide connector name', $connector?->getName());

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	private function askAccessId(Style\SymfonyStyle $io): string
	{
		$question = new Console\Question\Question('Provide cloud authentication Access ID');
		$question->setValidator(static function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime('You have to provide valid cloud authentication Access ID');
			}

			return $answer;
		});

		return strval($io->askQuestion($question));
	}

	private function askAccessSecret(Style\SymfonyStyle $io): string
	{
		$question = new Console\Question\Question('Provide cloud authentication Access Secret');
		$question->setValidator(static function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime('You have to provide valid cloud authentication Access Secret');
			}

			return $answer;
		});

		return strval($io->askQuestion($question));
	}

	private function askOpenApiEndpoint(Style\SymfonyStyle $io): Types\OpenApiEndpoint
	{
		$question = new Console\Question\ChoiceQuestion(
			'Provide which cloud data center you are using?',
			[
				0 => self::CHOICE_QUESTION_CENTRAL_EUROPE_DC,
				1 => self::CHOICE_QUESTION_WESTERN_EUROPE_DC,
				2 => self::CHOICE_QUESTION_WESTERN_AMERICA_DC,
				3 => self::CHOICE_QUESTION_EASTERN_AMERICA_DC,
				4 => self::CHOICE_QUESTION_CHINA_DC,
				5 => self::CHOICE_QUESTION_INDIA_DC,
			],
			0,
		);
		$question->setErrorMessage('Selected answer: "%s" is not valid.');
		$question->setValidator(static function (string|null $answer): Types\OpenApiEndpoint {
			if ($answer === null) {
				throw new Exceptions\InvalidState('Selected answer is not valid');
			}

			if ($answer === self::CHOICE_QUESTION_CENTRAL_EUROPE_DC || $answer === '0') {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::ENDPOINT_EUROPE);
			}

			if ($answer === self::CHOICE_QUESTION_WESTERN_EUROPE_DC || $answer === '1') {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::ENDPOINT_EUROPE_MS);
			}

			if ($answer === self::CHOICE_QUESTION_WESTERN_AMERICA_DC || $answer === '2') {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::ENDPOINT_AMERICA);
			}

			if ($answer === self::CHOICE_QUESTION_EASTERN_AMERICA_DC || $answer === '3') {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::ENDPOINT_AMERICA_AZURE);
			}

			if ($answer === self::CHOICE_QUESTION_CHINA_DC || $answer === '4') {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::ENDPOINT_CHINA);
			}

			if ($answer === self::CHOICE_QUESTION_INDIA_DC || $answer === '5') {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::ENDPOINT_INDIA);
			}

			throw new Exceptions\InvalidState('Selected answer is not valid');
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\OpenApiEndpoint);

		return $answer;
	}

	private function askUid(Style\SymfonyStyle $io): string
	{
		$question = new Console\Question\Question('Provide cloud user identification');
		$question->setValidator(static function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime('You have to provide valid cloud user identification');
			}

			return $answer;
		});

		return strval($io->askQuestion($question));
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\TuyaConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new DevicesQueries\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\TuyaConnector::class,
		);
		usort(
			$systemConnectors,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			static fn (DevicesEntities\Connectors\Connector $a, DevicesEntities\Connectors\Connector $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($systemConnectors as $connector) {
			assert($connector instanceof Entities\TuyaConnector);

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
		$question->setValidator(function (string|null $answer) use ($connectors): Entities\TuyaConnector {
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
					Entities\TuyaConnector::class,
				);
				assert($connector instanceof Entities\TuyaConnector || $connector === null);

				if ($connector !== null) {
					return $connector;
				}
			}

			throw new Exceptions\InvalidState('Selected answer is not valid');
		});

		$connector = $io->askQuestion($question);
		assert($connector instanceof Entities\TuyaConnector);

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

		throw new Exceptions\Runtime('Transformer manager could not be loaded');
	}

}
