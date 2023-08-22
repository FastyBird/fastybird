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
use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Types;
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

	public function __construct(
		private readonly Tuya\Logger $logger,
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Connectors\ConnectorsManager $connectorsManager,
		private readonly DevicesModels\Connectors\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Connectors\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
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

		$io->title($this->translator->translate('//tuya-connector.cmd.initialize.title'));

		$io->note($this->translator->translate('//tuya-connector.cmd.initialize.subtitle'));

		if ($input->getOption('no-interaction') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//tuya-connector.cmd.base.questions.continue'),
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
		$mode = $this->askMode($io);

		$question = new Console\Question\Question(
			$this->translator->translate('//tuya-connector.cmd.initialize.questions.provide.identifier'),
		);

		$question->setValidator(function ($answer) {
			if ($answer !== null) {
				$findConnectorQuery = new Queries\FindConnectors();
				$findConnectorQuery->byIdentifier($answer);

				if ($this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\TuyaConnector::class,
				) !== null) {
					throw new Exceptions\Runtime(
						$this->translator->translate('//tuya-connector.cmd.initialize.messages.identifier.used'),
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

				$findConnectorQuery = new Queries\FindConnectors();
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
			$io->error($this->translator->translate('//tuya-connector.cmd.initialize.messages.identifier.missing'));

			return;
		}

		$name = $this->askName($io);

		$accessId = $this->askAccessId($io);

		$accessSecret = $this->askAccessSecret($io);

		$dataCentre = $this->askOpenApiEndpoint($io);

		$uid = null;

		if ($mode->equalsValue(Types\ClientMode::CLOUD)) {
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
				'identifier' => Types\ConnectorPropertyIdentifier::CLIENT_MODE,
				'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::CLIENT_MODE),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'value' => $mode->getValue(),
				'format' => [Types\ClientMode::LOCAL, Types\ClientMode::CLOUD],
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::ACCESS_ID,
				'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::ACCESS_ID),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $accessId,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::ACCESS_SECRET,
				'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::ACCESS_SECRET),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $accessSecret,
				'connector' => $connector,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::OPENAPI_ENDPOINT,
				'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::OPENAPI_ENDPOINT),
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
					'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::UID),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $uid,
					'connector' => $connector,
				]));
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//tuya-connector.cmd.initialize.messages.create.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
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

			$io->error($this->translator->translate('//tuya-connector.cmd.initialize.messages.create.error'));
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
			$io->warning($this->translator->translate('//tuya-connector.cmd.base.messages.noConnectors'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//tuya-connector.cmd.initialize.questions.create'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createConfiguration($io);
			}

			return;
		}

		$findConnectorPropertyQuery = new DevicesQueries\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::CLIENT_MODE);

		$modeProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($modeProperty === null) {
			$changeMode = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//tuya-connector.cmd.initialize.questions.changeMode'),
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
				$this->translator->translate('//tuya-connector.cmd.initialize.questions.disable'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = false;
			}
		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//tuya-connector.cmd.initialize.questions.enable'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = true;
			}
		}

		$accessId = $accessSecret = null;

		$findConnectorPropertyQuery = new DevicesQueries\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::ACCESS_ID);

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
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::ACCESS_SECRET);

		$accessSecretProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($accessSecretProperty === null) {
			$changeAccessSecret = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//tuya-connector.cmd.initialize.questions.changeAccessSecret'),
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
				&& $modeProperty->getValue() === Types\ClientMode::CLOUD
			) || (
				$mode !== null
				&& $mode->equalsValue(Types\ClientMode::CLOUD)
			)
		) {
			$findConnectorPropertyQuery = new DevicesQueries\FindConnectorProperties();
			$findConnectorPropertyQuery->forConnector($connector);
			$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::UID);

			$uidProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

			if ($uidProperty === null) {
				$changeUid = true;

			} else {
				$question = new Console\Question\ConfirmationQuestion(
					$this->translator->translate('//tuya-connector.cmd.initialize.questions.changeUser'),
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
					'identifier' => Types\ConnectorPropertyIdentifier::CLIENT_MODE,
					'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::CLIENT_MODE),
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
					$accessId = $this->askAccessId($io);
				}

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::ACCESS_ID,
					'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::ACCESS_ID),
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
					'identifier' => Types\ConnectorPropertyIdentifier::ACCESS_SECRET,
					'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::ACCESS_SECRET),
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
						$uid = $this->askUid($io);
					}

					$this->propertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Connectors\Properties\Variable::class,
						'identifier' => Types\ConnectorPropertyIdentifier::UID,
						'name' => Helpers\Name::createName(Types\ConnectorPropertyIdentifier::UID),
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

			$io->success(
				$this->translator->translate(
					'//tuya-connector.cmd.initialize.messages.update.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
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

			$io->error($this->translator->translate('//tuya-connector.cmd.initialize.messages.update.error'));
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
			$io->info($this->translator->translate('//tuya-connector.cmd.base.messages.noConnectors'));

			return;
		}

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

			$io->success(
				$this->translator->translate(
					'//tuya-connector.cmd.initialize.messages.remove.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
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

			$io->error($this->translator->translate('//tuya-connector.cmd.initialize.messages.remove.error'));
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
		$findConnectorsQuery = new Queries\FindConnectors();

		$connectors = $this->connectorsRepository->findAllBy($findConnectorsQuery, Entities\TuyaConnector::class);
		usort(
			$connectors,
			static function (Entities\TuyaConnector $a, Entities\TuyaConnector $b): int {
				if ($a->getIdentifier() === $b->getIdentifier()) {
					return $a->getName() <=> $b->getName();
				}

				return $a->getIdentifier() <=> $b->getIdentifier();
			},
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//tuya-connector.cmd.initialize.data.name'),
			$this->translator->translate('//tuya-connector.cmd.initialize.data.devicesCnt'),
		]);

		foreach ($connectors as $index => $connector) {
			$findDevicesQuery = new Queries\FindDevices();
			$findDevicesQuery->forConnector($connector);

			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\TuyaDevice::class);

			$table->addRow([
				$index + 1,
				$connector->getName() ?? $connector->getIdentifier(),
				count($devices),
			]);
		}

		$table->render();

		$io->newLine();
	}

	private function askMode(Style\SymfonyStyle $io): Types\ClientMode
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//tuya-connector.cmd.initialize.questions.select.mode'),
			[
				0 => $this->translator->translate('//tuya-connector.cmd.initialize.answers.mode.local'),
				1 => $this->translator->translate('//tuya-connector.cmd.initialize.answers.mode.cloud'),
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
					'//tuya-connector.cmd.initialize.answers.mode.local',
				)
				|| $answer === '0'
			) {
				return Types\ClientMode::get(Types\ClientMode::LOCAL);
			}

			if (
				$answer === $this->translator->translate(
					'//tuya-connector.cmd.initialize.answers.mode.cloud',
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

	private function askName(Style\SymfonyStyle $io, Entities\TuyaConnector|null $connector = null): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//tuya-connector.cmd.initialize.questions.provide.name'),
			$connector?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askAccessId(Style\SymfonyStyle $io, Entities\TuyaConnector|null $connector = null): string
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//tuya-connector.cmd.initialize.questions.provide.accessId'),
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askAccessSecret(Style\SymfonyStyle $io, Entities\TuyaConnector|null $connector = null): string
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//tuya-connector.cmd.initialize.questions.provide.accessSecret'),
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

	private function askOpenApiEndpoint(Style\SymfonyStyle $io): Types\OpenApiEndpoint
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//tuya-connector.cmd.initialize.questions.select.dataCentre'),
			[
				0 => $this->translator->translate('//tuya-connector.cmd.initialize.answers.dataCentre.centralEurope'),
				1 => $this->translator->translate('//tuya-connector.cmd.initialize.answers.dataCentre.westernEurope'),
				2 => $this->translator->translate('//tuya-connector.cmd.initialize.answers.dataCentre.westernAmerica'),
				3 => $this->translator->translate('//tuya-connector.cmd.initialize.answers.dataCentre.easternAmerica'),
				4 => $this->translator->translate('//tuya-connector.cmd.initialize.answers.dataCentre.china'),
				5 => $this->translator->translate('//tuya-connector.cmd.initialize.answers.dataCentre.india'),
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
					'//tuya-connector.cmd.initialize.answers.dataCentre.centralEurope',
				)
				|| $answer === '0'
			) {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::EUROPE);
			}

			if (
				$answer === $this->translator->translate(
					'//tuya-connector.cmd.initialize.answers.dataCentre.westernEurope',
				)
				|| $answer === '1'
			) {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::EUROPE_MS);
			}

			if (
				$answer === $this->translator->translate(
					'//tuya-connector.cmd.initialize.answers.dataCentre.westernAmerica',
				)
				|| $answer === '2'
			) {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::AMERICA);
			}

			if (
				$answer === $this->translator->translate(
					'//tuya-connector.cmd.initialize.answers.dataCentre.easternAmerica',
				)
				|| $answer === '3'
			) {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::AMERICA_AZURE);
			}

			if (
				$answer === $this->translator->translate('//tuya-connector.cmd.initialize.answers.dataCentre.china')
				|| $answer === '4'
			) {
				return Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::CHINA);
			}

			if (
				$answer === $this->translator->translate('//tuya-connector.cmd.initialize.answers.dataCentre.india')
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askUid(Style\SymfonyStyle $io, Entities\TuyaConnector|null $connector = null): string
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//tuya-connector.cmd.initialize.questions.provide.uid'),
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

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\TuyaConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new Queries\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\TuyaConnector::class,
		);
		usort(
			$systemConnectors,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			static fn (Entities\TuyaConnector $a, Entities\TuyaConnector $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($systemConnectors as $connector) {
			$connectors[$connector->getIdentifier()] = $connector->getIdentifier()
				. ($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
		}

		if (count($connectors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//tuya-connector.cmd.initialize.questions.select.connector'),
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
				$findConnectorQuery = new Queries\FindConnectors();
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
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askInitializeAction(Style\SymfonyStyle $io): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//tuya-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//tuya-connector.cmd.initialize.actions.create'),
				1 => $this->translator->translate('//tuya-connector.cmd.initialize.actions.update'),
				2 => $this->translator->translate('//tuya-connector.cmd.initialize.actions.remove'),
				3 => $this->translator->translate('//tuya-connector.cmd.initialize.actions.list'),
				4 => $this->translator->translate('//tuya-connector.cmd.initialize.actions.nothing'),
			],
			4,
		);

		$question->setErrorMessage(
			$this->translator->translate('//tuya-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//tuya-connector.cmd.initialize.actions.create',
			)
			|| $whatToDo === '0'
		) {
			$this->createConfiguration($io);

			$this->askInitializeAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//tuya-connector.cmd.initialize.actions.update',
			)
			|| $whatToDo === '1'
		) {
			$this->editConfiguration($io);

			$this->askInitializeAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//tuya-connector.cmd.initialize.actions.remove',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteConfiguration($io);

			$this->askInitializeAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//tuya-connector.cmd.initialize.actions.list',
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
