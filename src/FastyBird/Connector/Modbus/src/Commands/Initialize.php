<?php declare(strict_types = 1);

/**
 * Initialize.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           04.08.22
 */

namespace FastyBird\Connector\Modbus\Commands;

use Doctrine\DBAL;
use Doctrine\Persistence;
use FastyBird\Connector\Modbus;
use FastyBird\Connector\Modbus\Entities;
use FastyBird\Connector\Modbus\Exceptions;
use FastyBird\Connector\Modbus\Queries;
use FastyBird\Connector\Modbus\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette\Localization;
use Nette\Utils;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function array_combine;
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
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Initialize extends Console\Command\Command
{

	public const NAME = 'fb:modbus-connector:initialize';

	public function __construct(
		private readonly Modbus\Logger $logger,
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
			->setDescription('Modbus connector initialization');
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

		$io->title($this->translator->translate('//modbus-connector.cmd.initialize.title'));

		$io->note($this->translator->translate('//modbus-connector.cmd.initialize.subtitle'));

		if ($input->getOption('no-interaction') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//modbus-connector.cmd.base.questions.continue'),
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
			$this->translator->translate('//modbus-connector.cmd.initialize.questions.provide.identifier'),
		);

		$question->setValidator(function ($answer) {
			if ($answer !== null) {
				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($answer);

				if ($this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\ModbusConnector::class,
				) !== null) {
					throw new Exceptions\Runtime(
						$this->translator->translate('//modbus-connector.cmd.initialize.messages.identifier.used'),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'modbus-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				if ($this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\ModbusConnector::class,
				) === null) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error($this->translator->translate('//modbus-connector.cmd.initialize.messages.identifier.missing'));

			return;
		}

		$name = $this->askName($io);

		$interface = $baudRate = $byteSize = $dataParity = $stopBits = null;

		if ($mode->equalsValue(Types\ClientMode::RTU)) {
			$interface = $this->askRtuInterface($io);
			$baudRate = $this->askRtuBaudRate($io);
			$byteSize = $this->askRtuByteSize($io);
			$dataParity = $this->askRtuDataParity($io);
			$stopBits = $this->askRtuStopBits($io);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\ModbusConnector::class,
				'identifier' => $identifier,
				'name' => $name,
			]));

			$this->propertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::CLIENT_MODE,
				'name' => DevicesUtilities\Name::createName(Types\ConnectorPropertyIdentifier::CLIENT_MODE),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $mode->getValue(),
				'connector' => $connector,
			]));

			if ($mode->equalsValue(Types\ClientMode::RTU)) {
				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::RTU_INTERFACE,
					'name' => DevicesUtilities\Name::createName(Types\ConnectorPropertyIdentifier::RTU_INTERFACE),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $interface,
					'connector' => $connector,
				]));

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::RTU_BAUD_RATE,
					'name' => DevicesUtilities\Name::createName(Types\ConnectorPropertyIdentifier::RTU_BAUD_RATE),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
					'value' => $baudRate?->getValue(),
					'connector' => $connector,
				]));

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::RTU_BYTE_SIZE,
					'name' => DevicesUtilities\Name::createName(Types\ConnectorPropertyIdentifier::RTU_BYTE_SIZE),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
					'value' => $byteSize?->getValue(),
					'connector' => $connector,
				]));

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::RTU_PARITY,
					'name' => DevicesUtilities\Name::createName(Types\ConnectorPropertyIdentifier::RTU_PARITY),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
					'value' => $dataParity?->getValue(),
					'connector' => $connector,
				]));

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::RTU_STOP_BITS,
					'name' => DevicesUtilities\Name::createName(Types\ConnectorPropertyIdentifier::RTU_STOP_BITS),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
					'value' => $stopBits?->getValue(),
					'connector' => $connector,
				]));
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//modbus-connector.cmd.initialize.messages.create.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'initialize-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//modbus-connector.cmd.initialize.messages.create.error'));
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
			$io->warning($this->translator->translate('//modbus-connector.cmd.base.messages.noConnectors'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//modbus-connector.cmd.initialize.questions.create'),
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
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::CLIENT_MODE);

		$modeProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($modeProperty === null) {
			$changeMode = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//modbus-connector.cmd.initialize.questions.changeMode'),
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
				$this->translator->translate('//modbus-connector.cmd.initialize.questions.disable'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = false;
			}
		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//modbus-connector.cmd.initialize.questions.enable'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = true;
			}
		}

		$interface = $baudRate = $byteSize = $dataParity = $stopBits = null;

		if (
			$modeProperty?->getValue() === Types\ClientMode::RTU
			|| $mode?->getValue() === Types\ClientMode::RTU
		) {
			$interface = $this->askRtuInterface($io, $connector);
			$baudRate = $this->askRtuBaudRate($io, $connector);
			$byteSize = $this->askRtuByteSize($io, $connector);
			$dataParity = $this->askRtuDataParity($io, $connector);
			$stopBits = $this->askRtuStopBits($io, $connector);
		}

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::RTU_INTERFACE);

		$interfaceProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::RTU_BAUD_RATE);

		$baudRateProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::RTU_BYTE_SIZE);

		$byteSizeProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::RTU_PARITY);

		$dataParityProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::RTU_STOP_BITS);

		$stopBitsProperty = $this->propertiesRepository->findOneBy($findConnectorPropertyQuery);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$connector = $this->connectorsManager->update($connector, Utils\ArrayHash::from([
				'name' => $name === '' ? null : $name,
				'enabled' => $enabled,
			]));
			assert($connector instanceof Entities\ModbusConnector);

			if ($modeProperty === null) {
				if ($mode === null) {
					$mode = $this->askMode($io);
				}

				$this->propertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::CLIENT_MODE,
					'name' => DevicesUtilities\Name::createName(Types\ConnectorPropertyIdentifier::CLIENT_MODE),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
					'value' => $mode->getValue(),
					'format' => [
						Types\ClientMode::RTU,
						Types\ClientMode::TCP,
					],
					'connector' => $connector,
				]));
			} elseif ($mode !== null) {
				$this->propertiesManager->update($modeProperty, Utils\ArrayHash::from([
					'value' => $mode->getValue(),
				]));
			}

			if (
				$modeProperty?->getValue() === Types\ClientMode::RTU
				|| $mode?->getValue() === Types\ClientMode::RTU
			) {
				if ($interfaceProperty === null) {
					$this->propertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Connectors\Properties\Variable::class,
						'identifier' => Types\ConnectorPropertyIdentifier::RTU_INTERFACE,
						'name' => DevicesUtilities\Name::createName(Types\ConnectorPropertyIdentifier::RTU_INTERFACE),
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
						'value' => $interface,
						'connector' => $connector,
					]));
				} elseif ($interfaceProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
					$this->propertiesManager->update($interfaceProperty, Utils\ArrayHash::from([
						'value' => $interface,
					]));
				}

				if ($baudRateProperty === null) {
					$this->propertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Connectors\Properties\Variable::class,
						'identifier' => Types\ConnectorPropertyIdentifier::RTU_BAUD_RATE,
						'name' => DevicesUtilities\Name::createName(Types\ConnectorPropertyIdentifier::RTU_BAUD_RATE),
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
						'value' => $baudRate?->getValue(),
						'connector' => $connector,
					]));
				} elseif ($baudRateProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
					$this->propertiesManager->update($baudRateProperty, Utils\ArrayHash::from([
						'value' => $baudRate?->getValue(),
					]));
				}

				if ($byteSizeProperty === null) {
					$this->propertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Connectors\Properties\Variable::class,
						'identifier' => Types\ConnectorPropertyIdentifier::RTU_BYTE_SIZE,
						'name' => DevicesUtilities\Name::createName(Types\ConnectorPropertyIdentifier::RTU_BYTE_SIZE),
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
						'value' => $byteSize?->getValue(),
						'connector' => $connector,
					]));
				} elseif ($byteSizeProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
					$this->propertiesManager->update($byteSizeProperty, Utils\ArrayHash::from([
						'value' => $byteSize?->getValue(),
					]));
				}

				if ($dataParityProperty === null) {
					$this->propertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Connectors\Properties\Variable::class,
						'identifier' => Types\ConnectorPropertyIdentifier::RTU_PARITY,
						'name' => DevicesUtilities\Name::createName(Types\ConnectorPropertyIdentifier::RTU_PARITY),
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
						'value' => $dataParity?->getValue(),
						'connector' => $connector,
					]));
				} elseif ($dataParityProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
					$this->propertiesManager->update($dataParityProperty, Utils\ArrayHash::from([
						'value' => $dataParity?->getValue(),
					]));
				}

				if ($stopBitsProperty === null) {
					$this->propertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Connectors\Properties\Variable::class,
						'identifier' => Types\ConnectorPropertyIdentifier::RTU_STOP_BITS,
						'name' => DevicesUtilities\Name::createName(Types\ConnectorPropertyIdentifier::RTU_STOP_BITS),
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
						'value' => $stopBits?->getValue(),
						'connector' => $connector,
					]));
				} elseif ($stopBitsProperty instanceof DevicesEntities\Connectors\Properties\Variable) {
					$this->propertiesManager->update($stopBitsProperty, Utils\ArrayHash::from([
						'value' => $stopBits?->getValue(),
					]));
				}
			} else {
				if ($interfaceProperty !== null) {
					$this->propertiesManager->delete($interfaceProperty);
				}

				if ($baudRateProperty !== null) {
					$this->propertiesManager->delete($baudRateProperty);
				}

				if ($byteSizeProperty !== null) {
					$this->propertiesManager->delete($byteSizeProperty);
				}

				if ($dataParityProperty !== null) {
					$this->propertiesManager->delete($dataParityProperty);
				}

				if ($stopBitsProperty !== null) {
					$this->propertiesManager->delete($stopBitsProperty);
				}
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//modbus-connector.cmd.initialize.messages.update.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'initialize-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//modbus-connector.cmd.initialize.messages.update.error'));
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
			$io->info($this->translator->translate('//modbus-connector.cmd.base.messages.noConnectors'));

			return;
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//modbus-connector.cmd.base.questions.continue'),
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
					'//modbus-connector.cmd.initialize.messages.remove.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_MODBUS,
					'type' => 'initialize-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//modbus-connector.cmd.initialize.messages.remove.error'));
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

		$connectors = $this->connectorsRepository->findAllBy($findConnectorsQuery, Entities\ModbusConnector::class);
		usort(
			$connectors,
			static function (Entities\ModbusConnector $a, Entities\ModbusConnector $b): int {
				if ($a->getIdentifier() === $b->getIdentifier()) {
					return $a->getName() <=> $b->getName();
				}

				return $a->getIdentifier() <=> $b->getIdentifier();
			},
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//modbus-connector.cmd.initialize.data.name'),
			$this->translator->translate('//modbus-connector.cmd.initialize.data.devicesCnt'),
		]);

		foreach ($connectors as $index => $connector) {
			$findDevicesQuery = new Queries\Entities\FindDevices();
			$findDevicesQuery->forConnector($connector);

			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\ModbusDevice::class);

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
			$this->translator->translate('//modbus-connector.cmd.initialize.questions.select.mode'),
			[
				0 => $this->translator->translate('//modbus-connector.cmd.initialize.answers.mode.rtu'),
				1 => $this->translator->translate('//modbus-connector.cmd.initialize.answers.mode.tcp'),
			],
			1,
		);

		$question->setErrorMessage(
			$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer): Types\ClientMode {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (
				$answer === $this->translator->translate(
					'//modbus-connector.cmd.initialize.answers.mode.rtu',
				)
				|| $answer === '0'
			) {
				return Types\ClientMode::get(Types\ClientMode::RTU);
			}

			if (
				$answer === $this->translator->translate(
					'//modbus-connector.cmd.initialize.answers.mode.tcp',
				)
				|| $answer === '1'
			) {
				return Types\ClientMode::get(Types\ClientMode::TCP);
			}

			throw new Exceptions\Runtime(
				sprintf($this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'), $answer),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\ClientMode);

		return $answer;
	}

	private function askName(Style\SymfonyStyle $io, Entities\ModbusConnector|null $connector = null): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//modbus-connector.cmd.initialize.questions.provide.name'),
			$connector?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askRtuInterface(Style\SymfonyStyle $io, Entities\ModbusConnector|null $connector = null): string
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//modbus-connector.cmd.initialize.questions.provide.rtuInterface'),
			$connector?->getRtuInterface(),
		);
		$question->setValidator(function (string|null $answer): string {
			if ($answer === '' || $answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
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
	private function askRtuByteSize(
		Style\SymfonyStyle $io,
		Entities\ModbusConnector|null $connector = null,
	): Types\ByteSize
	{
		$default = $connector?->getByteSize()->getValue() ?? Types\ByteSize::SIZE_8;

		$byteSizes = array_combine(
			array_values(Types\ByteSize::getValues()),
			array_values(Types\ByteSize::getValues()),
		);

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//modbus-connector.cmd.initialize.questions.select.byteSize'),
			array_values($byteSizes),
			$default,
		);

		$question->setErrorMessage(
			$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($byteSizes): Types\ByteSize {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($byteSizes))) {
				$answer = array_values($byteSizes)[$answer];
			}

			$byteSize = array_search($answer, $byteSizes, true);

			if ($byteSize !== false && Types\ByteSize::isValidValue($byteSize)) {
				return Types\ByteSize::get(intval($byteSize));
			}

			throw new Exceptions\Runtime(
				sprintf($this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'), $answer),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\ByteSize);

		return $answer;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askRtuBaudRate(
		Style\SymfonyStyle $io,
		Entities\ModbusConnector|null $connector = null,
	): Types\BaudRate
	{
		$default = $connector?->getBaudRate()->getValue() ?? Types\BaudRate::RATE_9600;

		$baudRates = array_combine(
			array_values(Types\BaudRate::getValues()),
			array_values(Types\BaudRate::getValues()),
		);

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//modbus-connector.cmd.initialize.questions.select.baudRate'),
			array_values($baudRates),
			$default,
		);

		$question->setErrorMessage(
			$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($baudRates): Types\BaudRate {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($baudRates))) {
				$answer = array_values($baudRates)[$answer];
			}

			$baudRate = array_search($answer, $baudRates, true);

			if ($baudRate !== false && Types\BaudRate::isValidValue($baudRate)) {
				return Types\BaudRate::get(intval($baudRate));
			}

			throw new Exceptions\Runtime(
				sprintf($this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'), $answer),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\BaudRate);

		return $answer;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askRtuDataParity(
		Style\SymfonyStyle $io,
		Entities\ModbusConnector|null $connector = null,
	): Types\Parity
	{
		$default = 0;

		switch ($connector?->getParity()->getValue()) {
			case Types\Parity::ODD:
				$default = 1;

				break;
			case Types\Parity::EVEN:
				$default = 2;

				break;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//modbus-connector.cmd.initialize.questions.select.dataParity'),
			[
				0 => $this->translator->translate('//modbus-connector.cmd.initialize.answers.parity.none'),
				1 => $this->translator->translate('//modbus-connector.cmd.initialize.answers.parity.odd'),
				2 => $this->translator->translate('//modbus-connector.cmd.initialize.answers.parity.even'),
			],
			$default,
		);

		$question->setErrorMessage(
			$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer): Types\Parity {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (
				$answer === $this->translator->translate(
					'//modbus-connector.cmd.initialize.answers.parity.none',
				)
				|| $answer === '0'
			) {
				return Types\Parity::get(Types\Parity::NONE);
			}

			if (
				$answer === $this->translator->translate(
					'//modbus-connector.cmd.initialize.answers.parity.odd',
				)
				|| $answer === '1'
			) {
				return Types\Parity::get(Types\Parity::ODD);
			}

			if (
				$answer === $this->translator->translate(
					'//modbus-connector.cmd.initialize.answers.parity.even',
				)
				|| $answer === '2'
			) {
				return Types\Parity::get(Types\Parity::EVEN);
			}

			throw new Exceptions\Runtime(
				sprintf($this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'), $answer),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\Parity);

		return $answer;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askRtuStopBits(
		Style\SymfonyStyle $io,
		Entities\ModbusConnector|null $connector = null,
	): Types\StopBits
	{
		$default = $connector?->getStopBits()->getValue() ?? Types\StopBits::ONE;

		$stopBits = array_combine(
			array_values(Types\StopBits::getValues()),
			array_values(Types\StopBits::getValues()),
		);

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//modbus-connector.cmd.initialize.questions.select.stopBits'),
			array_values($stopBits),
			$default,
		);

		$question->setErrorMessage(
			$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($stopBits): Types\StopBits {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($stopBits))) {
				$answer = array_values($stopBits)[$answer];
			}

			$stopBit = array_search($answer, $stopBits, true);

			if ($stopBit !== false && Types\StopBits::isValidValue($stopBit)) {
				return Types\StopBits::get(intval($stopBit));
			}

			throw new Exceptions\Runtime(
				sprintf($this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'), $answer),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\StopBits);

		return $answer;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\ModbusConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\ModbusConnector::class,
		);
		usort(
			$systemConnectors,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			static fn (Entities\ModbusConnector $a, Entities\ModbusConnector $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($systemConnectors as $connector) {
			$connectors[$connector->getIdentifier()] = $connector->getIdentifier()
				. ($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
		}

		if (count($connectors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//modbus-connector.cmd.initialize.questions.select.connector'),
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer) use ($connectors): Entities\ModbusConnector {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
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
					Entities\ModbusConnector::class,
				);

				if ($connector !== null) {
					return $connector;
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$connector = $io->askQuestion($question);
		assert($connector instanceof Entities\ModbusConnector);

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
			$this->translator->translate('//modbus-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//modbus-connector.cmd.initialize.actions.create'),
				1 => $this->translator->translate('//modbus-connector.cmd.initialize.actions.update'),
				2 => $this->translator->translate('//modbus-connector.cmd.initialize.actions.remove'),
				3 => $this->translator->translate('//modbus-connector.cmd.initialize.actions.list'),
				4 => $this->translator->translate('//modbus-connector.cmd.initialize.actions.nothing'),
			],
			4,
		);

		$question->setErrorMessage(
			$this->translator->translate('//modbus-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//modbus-connector.cmd.initialize.actions.create',
			)
			|| $whatToDo === '0'
		) {
			$this->createConfiguration($io);

			$this->askInitializeAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//modbus-connector.cmd.initialize.actions.update',
			)
			|| $whatToDo === '1'
		) {
			$this->editConfiguration($io);

			$this->askInitializeAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//modbus-connector.cmd.initialize.actions.remove',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteConfiguration($io);

			$this->askInitializeAction($io);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//modbus-connector.cmd.initialize.actions.list',
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
