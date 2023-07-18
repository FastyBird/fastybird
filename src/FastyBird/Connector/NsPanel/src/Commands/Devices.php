<?php declare(strict_types = 1);

/**
 * Devices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           12.07.23
 */

namespace FastyBird\Connector\NsPanel\Commands;

use Doctrine\DBAL;
use Doctrine\Persistence;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use InvalidArgumentException as InvalidArgumentExceptionAlias;
use Nette\Localization;
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
use function is_string;
use function sprintf;
use function strval;
use function usort;

/**
 * Connector devices management command
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Devices extends Console\Command\Command
{

	public const NAME = 'fb:viera-connector:devices';

	public function __construct(
		private readonly API\LanApiFactory $lanApiFactory,
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly Persistence\ManagerRegistry $managerRegistry,
		private readonly Localization\Translator $translator,
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
			->setDescription('NS Panel devices management');
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentExceptionAlias
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->title($this->translator->translate('//ns-panel-connector.cmd.devices.title'));

		$io->note($this->translator->translate('//ns-panel-connector.cmd.devices.subtitle'));

		if ($input->getOption('no-interaction') === false) {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.base.questions.continue'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				return Console\Command\Command::SUCCESS;
			}
		}

		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->warning($this->translator->translate('//ns-panel-connector.cmd.base.messages.noConnectors'));

			return Console\Command\Command::SUCCESS;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.create'),
				1 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.update'),
				2 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.remove'),
			],
		);

		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if ($whatToDo === $this->translator->translate('//ns-panel-connector.cmd.devices.actions.create')) {
			$this->createNewDevice($io, $connector);

		} elseif ($whatToDo === $this->translator->translate('//ns-panel-connector.cmd.devices.actions.update')) {
			$this->editExistingDevice($io, $connector);

		} elseif ($whatToDo === $this->translator->translate('//ns-panel-connector.cmd.devices.actions.remove')) {
			$this->deleteExistingDevice($io, $connector);
		}

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function createNewDevice(Style\SymfonyStyle $io, Entities\NsPanelConnector $connector): void
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.provide.identifier'),
		);

		$question->setValidator(function (string|null $answer) {
			if ($answer !== '' && $answer !== null) {
				$findDeviceQuery = new DevicesQueries\FindDevices();
				$findDeviceQuery->byIdentifier($answer);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\NsPanelDevice::class) !== null
				) {
					throw new Exceptions\Runtime(
						$this->translator->translate('//ns-panel-connector.cmd.devices.messages.identifier.used'),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'ns-panel-%d';

			for ($i = 1; $i <= 100; $i++) {
				$identifier = sprintf($identifierPattern, $i);

				$findDeviceQuery = new DevicesQueries\FindDevices();
				$findDeviceQuery->byIdentifier($identifier);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\NsPanelDevice::class) === null
				) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error($this->translator->translate('//ns-panel-connector.cmd.devices.messages.identifier.missing'));

			return;
		}

		assert(is_string($identifier));

		$name = $this->askDeviceName($io);

		$panelInfo = $this->askWhichPanel($io, $identifier);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Devices\Gateway::class,
				'connector' => $connector,
				'identifier' => $identifier,
				'name' => $name,
			]));
			assert($device instanceof Entities\Devices\Gateway);

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $panelInfo->getIpAddress(),
				'connector' => $connector,
			]));

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_DOMAIN,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_DOMAIN),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $panelInfo->getDomain(),
				'connector' => $connector,
			]));

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_MAC_ADDRESS,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_MAC_ADDRESS),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $panelInfo->getMacAddress(),
				'connector' => $connector,
			]));

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_VERSION,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_VERSION),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $panelInfo->getFirmwareVersion(),
				'connector' => $connector,
			]));

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//ns-panel-connector.cmd.devices.messages.create.device.success',
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

			$io->error($this->translator->translate('//ns-panel-connector.cmd.devices.messages.create.device.error'));

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
	private function editExistingDevice(Style\SymfonyStyle $io, Entities\NsPanelConnector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->warning($this->translator->translate('//ns-panel-connector.cmd.devices.messages.noDevices'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.create'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createNewDevice($io, $connector);
			}

			return;
		}

		assert($device instanceof Entities\Devices\Gateway);

		$name = $this->askDeviceName($io, $device);

		$findConnectorPropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findConnectorPropertyQuery->forDevice($device);
		$findConnectorPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS);

		$ipAddressProperty = $this->devicesPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findConnectorPropertyQuery->forDevice($device);
		$findConnectorPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IDENTIFIER_DOMAIN);

		$domainProperty = $this->devicesPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		$findConnectorPropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findConnectorPropertyQuery->forDevice($device);
		$findConnectorPropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IDENTIFIER_MAC_ADDRESS);

		$macAddressProperty = $this->devicesPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		$panelInfo = null;

		if ($ipAddressProperty === null || $domainProperty === null || $macAddressProperty === null) {
			$changeWhichPanel = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.connection'),
				false,
			);

			$changeWhichPanel = (bool) $io->askQuestion($question);
		}

		if ($changeWhichPanel) {
			$panelInfo = $this->askWhichPanel($io, $connector->getIdentifier(), $device);
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->update($device, Utils\ArrayHash::from([
				'name' => $name,
			]));
			assert($device instanceof Entities\Devices\Gateway);

			if ($ipAddressProperty === null) {
				if ($panelInfo === null) {
					$panelInfo = $this->askWhichPanel($io, $connector->getIdentifier(), $device);
				}

				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
					'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $panelInfo->getIpAddress(),
					'connector' => $connector,
				]));
			} elseif ($panelInfo !== null) {
				$this->devicesPropertiesManager->update($ipAddressProperty, Utils\ArrayHash::from([
					'value' => $panelInfo->getIpAddress(),
				]));
			}

			if ($domainProperty === null) {
				if ($panelInfo === null) {
					$panelInfo = $this->askWhichPanel($io, $connector->getIdentifier(), $device);
				}

				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_DOMAIN,
					'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_DOMAIN),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $panelInfo->getDomain(),
					'connector' => $connector,
				]));
			} elseif ($panelInfo !== null) {
				$this->devicesPropertiesManager->update($domainProperty, Utils\ArrayHash::from([
					'value' => $panelInfo->getDomain(),
				]));
			}

			if ($macAddressProperty === null) {
				if ($panelInfo === null) {
					$panelInfo = $this->askWhichPanel($io, $connector->getIdentifier(), $device);
				}

				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_MAC_ADDRESS,
					'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_MAC_ADDRESS),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $panelInfo->getMacAddress(),
					'connector' => $connector,
				]));
			} elseif ($panelInfo !== null) {
				$this->devicesPropertiesManager->update($macAddressProperty, Utils\ArrayHash::from([
					'value' => $panelInfo->getMacAddress(),
				]));
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//ns-panel-connector.cmd.devices.messages.update.device.success',
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

			$io->error($this->translator->translate('//ns-panel-connector.cmd.devices.messages.update.device.error'));
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
	private function deleteExistingDevice(Style\SymfonyStyle $io, Entities\NsPanelConnector $connector): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->info($this->translator->translate('//ns-panel-connector.cmd.devices.messages.noDevices'));

			return;
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.base.questions.continue'),
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
					'//ns-panel-connector.cmd.devices.messages.remove.device.success',
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

			$io->error($this->translator->translate('//ns-panel-connector.cmd.devices.messages.remove.device.error'));
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}
	}

	private function askDeviceName(Style\SymfonyStyle $io, Entities\NsPanelDevice|null $device = null): string|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.provide.name'),
			$device?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	private function askWhichPanel(
		Style\SymfonyStyle $io,
		string $identifier,
		Entities\Devices\Gateway|null $device = null,
	): Entities\Commands\GatewayInfo
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.provide.address'),
			$device?->getName(),
		);
		$question->setValidator(
			function (string|null $answer) use ($identifier): Entities\Commands\GatewayInfo {
				if ($answer !== null && $answer !== '') {
					$panelApi = $this->lanApiFactory->create($identifier);

					try {
						$panelInfo = $panelApi->getGatewayInfo($answer, API\LanApi::GATEWAY_PORT, false);
					} catch (Exceptions\LanApiCall) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate(
									'//ns-panel-connector.cmd.devices.messages.addressNotReachable',
								),
								$answer,
							),
						);
					}

					return Entities\EntityFactory::build(
						Entities\Commands\GatewayInfo::class,
						Utils\ArrayHash::from($panelInfo->getData()->toArray()),
					);
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$panelInfo = $io->askQuestion($question);
		assert($panelInfo instanceof Entities\Commands\GatewayInfo);

		return $panelInfo;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\NsPanelConnector|null
	{
		$connectors = [];

		$findConnectorsQuery = new DevicesQueries\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\NsPanelConnector::class,
		);
		usort(
			$systemConnectors,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			static fn (DevicesEntities\Connectors\Connector $a, DevicesEntities\Connectors\Connector $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($systemConnectors as $connector) {
			assert($connector instanceof Entities\NsPanelConnector);

			$connectors[$connector->getIdentifier()] = $connector->getIdentifier()
				. ($connector->getName() !== null ? ' [' . $connector->getName() . ']' : '');
		}

		if (count($connectors) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.connector'),
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer) use ($connectors): Entities\NsPanelConnector {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
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
					Entities\NsPanelConnector::class,
				);
				assert($connector instanceof Entities\NsPanelConnector || $connector === null);

				if ($connector !== null) {
					return $connector;
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$connector = $io->askQuestion($question);
		assert($connector instanceof Entities\NsPanelConnector);

		return $connector;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichDevice(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
	): Entities\NsPanelDevice|null
	{
		$devices = [];

		$findDevicesQuery = new DevicesQueries\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$connectorDevices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\NsPanelDevice::class);
		usort(
			$connectorDevices,
			static fn (DevicesEntities\Devices\Device $a, DevicesEntities\Devices\Device $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($connectorDevices as $device) {
			assert($device instanceof Entities\NsPanelDevice);

			$devices[$device->getIdentifier()] = $device->getIdentifier()
				. ($device->getName() !== null ? ' [' . $device->getName() . ']' : '');
		}

		if (count($devices) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.device'),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer) use ($connector, $devices): Entities\NsPanelDevice {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($devices))) {
				$answer = array_values($devices)[$answer];
			}

			$identifier = array_search($answer, $devices, true);

			if ($identifier !== false) {
				$findDeviceQuery = new DevicesQueries\FindDevices();
				$findDeviceQuery->byIdentifier($identifier);
				$findDeviceQuery->forConnector($connector);

				$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\NsPanelDevice::class);
				assert($device instanceof Entities\NsPanelDevice || $device === null);

				if ($device !== null) {
					return $device;
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\NsPanelDevice);

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
