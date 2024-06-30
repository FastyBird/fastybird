<?php declare(strict_types = 1);

/**
 * Install.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           12.12.23
 */

namespace FastyBird\Connector\NsPanel\Commands;

use Brick\Math;
use DateTimeInterface;
use Doctrine\DBAL;
use Exception;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Formats as MetadataFormats;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Module\Devices\Commands as DevicesCommands;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Types as DevicesTypes;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette;
use Nette\Localization;
use Nette\Utils;
use Ramsey\Uuid;
use RuntimeException;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use TypeError;
use ValueError;
use function array_combine;
use function array_filter;
use function array_flip;
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
use function preg_match;
use function sprintf;
use function strval;
use function usort;

/**
 * Connector install command
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Install extends Console\Command\Command
{

	public const NAME = 'fb:ns-panel-connector:install';

	private Input\InputInterface|null $input = null;

	private Output\OutputInterface|null $output = null;

	public function __construct(
		private readonly API\LanApiFactory $lanApiFactory,
		private readonly Helpers\Loader $loader,
		private readonly NsPanel\Logger $logger,
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
		private readonly ApplicationHelpers\Database $databaseHelper,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
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
			->setDescription('NS Panel connector installer');
	}

	/**
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws TypeError
	 * @throws ValueError
	 */
	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$this->input = $input;
		$this->output = $output;

		$io = new Style\SymfonyStyle($this->input, $this->output);

		$io->title((string) $this->translator->translate('//ns-panel-connector.cmd.install.title'));

		$io->note((string) $this->translator->translate('//ns-panel-connector.cmd.install.subtitle'));

		$this->askInstallAction($io);

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws RuntimeException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function createConnector(Style\SymfonyStyle $io): void
	{
		$mode = $this->askConnectorMode($io);

		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.questions.provide.connector.identifier',
			),
		);

		$question->setValidator(function ($answer) {
			if ($answer !== null) {
				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($answer);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\Connectors\Connector::class,
				);

				if ($connector !== null) {
					throw new Exceptions\Runtime(
						(string) $this->translator->translate(
							'//ns-panel-connector.cmd.install.messages.identifier.connector.used',
						),
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

				$findConnectorQuery = new Queries\Entities\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\Connectors\Connector::class,
				);

				if ($connector === null) {
					break;
				}
			}
		}

		if ($identifier === '') {
			$io->error(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.identifier.connector.missing',
				),
			);

			return;
		}

		$name = $this->askConnectorName($io);

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$connector = $this->connectorsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Connectors\Connector::class,
				'identifier' => $identifier,
				'name' => $name === '' ? null : $name,
			]));
			assert($connector instanceof Entities\Connectors\Connector);

			$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Connectors\Properties\Variable::class,
				'identifier' => Types\ConnectorPropertyIdentifier::CLIENT_MODE->value,
				'dataType' => MetadataTypes\DataType::ENUM,
				'value' => $mode->value,
				'format' => [
					Types\ClientMode::GATEWAY->value,
					Types\ClientMode::DEVICE->value,
					Types\ClientMode::BOTH->value,
				],
				'connector' => $connector,
			]));

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.create.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'install-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.create.connector.error',
				),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.create.gateways'),
			true,
		);

		$createGateways = (bool) $io->askQuestion($question);

		if ($createGateways) {
			$connector = $this->connectorsRepository->find($connector->getId(), Entities\Connectors\Connector::class);
			assert($connector instanceof Entities\Connectors\Connector);

			$this->createGateway($io, $connector);
		}
	}

	/**
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function editConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info((string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.noConnectors'));

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.create.connector'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createConnector($io);
			}

			return;
		}

		$findConnectorPropertyQuery = new Queries\Entities\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::CLIENT_MODE);

		$modeProperty = $this->connectorsPropertiesRepository->findOneBy($findConnectorPropertyQuery);

		if ($modeProperty === null) {
			$changeMode = true;

		} else {
			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.change.mode'),
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
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.disable.connector'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = false;
			}
		} else {
			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.enable.connector'),
				false,
			);

			if ($io->askQuestion($question) === true) {
				$enabled = true;
			}
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$connector = $this->connectorsManager->update($connector, Utils\ArrayHash::from([
				'name' => $name === '' ? null : $name,
				'enabled' => $enabled,
			]));
			assert($connector instanceof Entities\Connectors\Connector);

			if ($modeProperty === null) {
				if ($mode === null) {
					$mode = $this->askConnectorMode($io);
				}

				$this->connectorsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::CLIENT_MODE->value,
					'dataType' => MetadataTypes\DataType::ENUM,
					'value' => $mode->value,
					'format' => [Types\ClientMode::GATEWAY->value, Types\ClientMode::DEVICE->value, Types\ClientMode::BOTH->value],
					'connector' => $connector,
				]));
			} elseif ($mode !== null) {
				$this->connectorsPropertiesManager->update($modeProperty, Utils\ArrayHash::from([
					'value' => $mode->value,
				]));
			}

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.update.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'install-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.update.connector.error',
				),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.manage.gateways'),
			false,
		);

		$manage = (bool) $io->askQuestion($question);

		if (!$manage) {
			return;
		}

		$connector = $this->connectorsRepository->find($connector->getId(), Entities\Connectors\Connector::class);
		assert($connector instanceof Entities\Connectors\Connector);

		$this->askManageConnectorAction($io, $connector);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 */
	private function deleteConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info((string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.noConnectors'));

			return;
		}

		$io->warning(
			(string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.messages.remove.connector.confirm',
				['name' => $connector->getName() ?? $connector->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$this->connectorsManager->delete($connector);

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.remove.connector.success',
					['name' => $connector->getName() ?? $connector->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'install-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.remove.connector.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function manageConnector(Style\SymfonyStyle $io): void
	{
		$connector = $this->askWhichConnector($io);

		if ($connector === null) {
			$io->info((string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.noConnectors'));

			return;
		}

		$this->askManageConnectorAction($io, $connector);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function listConnectors(Style\SymfonyStyle $io): void
	{
		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		$connectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\Connectors\Connector::class,
		);
		usort(
			$connectors,
			static fn (Entities\Connectors\Connector $a, Entities\Connectors\Connector $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.name'),
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.mode'),
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.panelsCnt'),
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.subDevicesCnt'),
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.devicesCnt'),
		]);

		foreach ($connectors as $index => $connector) {
			$findDevicesQuery = new Queries\Entities\FindGatewayDevices();
			$findDevicesQuery->forConnector($connector);

			$nsPanels = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class);

			$findDevicesQuery = new Queries\Entities\FindSubDevices();
			$findDevicesQuery->forConnector($connector);

			$subDevices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\SubDevice::class);

			$findDevicesQuery = new Queries\Entities\FindThirdPartyDevices();
			$findDevicesQuery->forConnector($connector);

			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\ThirdPartyDevice::class);

			$table->addRow([
				$index + 1,
				$connector->getName() ?? $connector->getIdentifier(),
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.base.mode.' . $connector->getClientMode()->value,
				),
				count($nsPanels),
				count($subDevices),
				count($devices),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function createGateway(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
	): void
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.questions.provide.device.identifier',
			),
		);

		$question->setValidator(function (string|null $answer) {
			if ($answer !== '' && $answer !== null) {
				$findDeviceQuery = new Queries\Entities\FindDevices();
				$findDeviceQuery->byIdentifier($answer);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Device::class) !== null
				) {
					throw new Exceptions\Runtime(
						(string) $this->translator->translate(
							'//ns-panel-connector.cmd.install.messages.identifier.device.used',
						),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'ns-panel-gw-%d';

			$identifier = $this->findNextDeviceIdentifier($connector, $identifierPattern);
		}

		if ($identifier === '') {
			$io->error(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.identifier.device.missing',
				),
			);

			return;
		}

		assert(is_string($identifier));

		$name = $this->askDeviceName($io);

		$panelInfo = $this->askWhichPanel($io, $connector);

		$io->note((string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.gateway.prepare'));

		do {
			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.isGatewayReady'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				$question = new Console\Question\ConfirmationQuestion(
					(string) $this->translator->translate('//ns-panel-connector.cmd.base.questions.exit'),
					false,
				);

				$exit = (bool) $io->askQuestion($question);

				if ($exit) {
					return;
				}
			}
		} while (!$continue);

		$panelApi = $this->lanApiFactory->create($identifier);

		try {
			$accessToken = $panelApi->getGatewayAccessToken(
				$connector->getName() ?? $connector->getIdentifier(),
				$panelInfo->getData()->getIpAddress(),
				API\LanApi::GATEWAY_PORT,
				false,
			);
		} catch (Exceptions\LanApiCall) {
			$io->error(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.accessToken.error'),
			);

			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$gateway = $this->devicesManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Devices\Gateway::class,
				'connector' => $connector,
				'identifier' => $identifier,
				'name' => $name,
			]));
			assert($gateway instanceof Entities\Devices\Gateway);

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IP_ADDRESS->value,
				'dataType' => MetadataTypes\DataType::STRING,
				'value' => $panelInfo->getData()->getIpAddress(),
				'device' => $gateway,
			]));

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::DOMAIN->value,
				'dataType' => MetadataTypes\DataType::STRING,
				'value' => $panelInfo->getData()->getDomain(),
				'device' => $gateway,
			]));

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::MAC_ADDRESS->value,
				'dataType' => MetadataTypes\DataType::STRING,
				'value' => $panelInfo->getData()->getMacAddress(),
				'device' => $gateway,
			]));

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::FIRMWARE_VERSION->value,
				'dataType' => MetadataTypes\DataType::STRING,
				'value' => $panelInfo->getData()->getFirmwareVersion(),
				'device' => $gateway,
			]));

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::ACCESS_TOKEN->value,
				'dataType' => MetadataTypes\DataType::STRING,
				'value' => $accessToken->getData()->getAccessToken(),
				'device' => $gateway,
			]));

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.create.gateway.success',
					['name' => $gateway->getName() ?? $gateway->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'install-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.create.gateway.error'),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}

		if (
			$connector->getClientMode() === Types\ClientMode::DEVICE
			|| $connector->getClientMode() === Types\ClientMode::BOTH
		) {
			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.create.devices'),
				true,
			);

			$createDevices = (bool) $io->askQuestion($question);

			if ($createDevices) {
				$gateway = $this->devicesRepository->find($gateway->getId(), Entities\Devices\Gateway::class);
				assert($gateway instanceof Entities\Devices\Gateway);

				$this->createDevice($io, $connector, $gateway);
			}
		}
	}

	/**
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws RuntimeException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function editGateway(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
	): void
	{
		$gateway = $this->askWhichGateway($io, $connector);

		if ($gateway === null) {
			$io->info((string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.noGateways'));

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.create.gateway'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createGateway($io, $connector);
			}

			return;
		}

		$name = $this->askDeviceName($io, $gateway);

		$panelInfo = $this->askWhichPanel($io, $connector, $gateway);

		$findDevicePropertyQuery = new Queries\Entities\FindDeviceVariableProperties();
		$findDevicePropertyQuery->forDevice($gateway);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IP_ADDRESS);

		$ipAddressProperty = $this->devicesPropertiesRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		$findDevicePropertyQuery = new Queries\Entities\FindDeviceVariableProperties();
		$findDevicePropertyQuery->forDevice($gateway);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::DOMAIN);

		$domainProperty = $this->devicesPropertiesRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		$findDevicePropertyQuery = new Queries\Entities\FindDeviceVariableProperties();
		$findDevicePropertyQuery->forDevice($gateway);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::MAC_ADDRESS);

		$macAddressProperty = $this->devicesPropertiesRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		$findDevicePropertyQuery = new Queries\Entities\FindDeviceVariableProperties();
		$findDevicePropertyQuery->forDevice($gateway);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::FIRMWARE_VERSION);

		$firmwareVersionProperty = $this->devicesPropertiesRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.regenerateAccessToken'),
			false,
		);

		$regenerate = (bool) $io->askQuestion($question);

		$accessToken = null;

		if ($regenerate) {
			$io->note(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.gateway.prepare'),
			);

			do {
				$question = new Console\Question\ConfirmationQuestion(
					(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.isGatewayReady'),
					false,
				);

				$continue = (bool) $io->askQuestion($question);

				if (!$continue) {
					$question = new Console\Question\ConfirmationQuestion(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.questions.exit'),
						false,
					);

					$exit = (bool) $io->askQuestion($question);

					if ($exit) {
						return;
					}
				}
			} while (!$continue);

			$panelApi = $this->lanApiFactory->create($gateway->getIdentifier());

			try {
				$accessToken = $panelApi->getGatewayAccessToken(
					$connector->getName() ?? $connector->getIdentifier(),
					$panelInfo->getData()->getIpAddress(),
					API\LanApi::GATEWAY_PORT,
					false,
				);
			} catch (Exceptions\LanApiCall) {
				$io->error(
					(string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.messages.accessToken.error',
					),
				);
			}
		}

		$findDevicePropertyQuery = new Queries\Entities\FindDeviceVariableProperties();
		$findDevicePropertyQuery->forDevice($gateway);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::ACCESS_TOKEN);

		$accessTokenProperty = $this->devicesPropertiesRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$gateway = $this->devicesManager->update($gateway, Utils\ArrayHash::from([
				'name' => $name,
			]));
			assert($gateway instanceof Entities\Devices\Gateway);

			if ($ipAddressProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::IP_ADDRESS->value,
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => $panelInfo->getData()->getIpAddress(),
					'device' => $gateway,
				]));
			} elseif ($ipAddressProperty instanceof DevicesEntities\Devices\Properties\Variable) {
				$this->devicesPropertiesManager->update($ipAddressProperty, Utils\ArrayHash::from([
					'value' => $panelInfo->getData()->getIpAddress(),
				]));
			}

			if ($domainProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::DOMAIN->value,
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => $panelInfo->getData()->getDomain(),
					'device' => $gateway,
				]));
			} elseif ($domainProperty instanceof DevicesEntities\Devices\Properties\Variable) {
				$this->devicesPropertiesManager->update($domainProperty, Utils\ArrayHash::from([
					'value' => $panelInfo->getData()->getDomain(),
				]));
			}

			if ($macAddressProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::MAC_ADDRESS->value,
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => $panelInfo->getData()->getMacAddress(),
					'device' => $gateway,
				]));
			} elseif ($macAddressProperty instanceof DevicesEntities\Devices\Properties\Variable) {
				$this->devicesPropertiesManager->update($macAddressProperty, Utils\ArrayHash::from([
					'value' => $panelInfo->getData()->getMacAddress(),
				]));
			}

			if ($firmwareVersionProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::FIRMWARE_VERSION->value,
					'dataType' => MetadataTypes\DataType::STRING,
					'value' => $panelInfo->getData()->getFirmwareVersion(),
					'device' => $gateway,
				]));
			} elseif ($firmwareVersionProperty instanceof DevicesEntities\Devices\Properties\Variable) {
				$this->devicesPropertiesManager->update($firmwareVersionProperty, Utils\ArrayHash::from([
					'value' => $panelInfo->getData()->getFirmwareVersion(),
				]));
			}

			if ($accessToken !== null) {
				if ($accessTokenProperty === null) {
					$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Devices\Properties\Variable::class,
						'identifier' => Types\DevicePropertyIdentifier::ACCESS_TOKEN->value,
						'dataType' => MetadataTypes\DataType::STRING,
						'value' => $accessToken->getData()->getAccessToken(),
						'device' => $gateway,
					]));
				} elseif ($accessTokenProperty instanceof DevicesEntities\Devices\Properties\Variable) {
					$this->devicesPropertiesManager->update($accessTokenProperty, Utils\ArrayHash::from([
						'value' => $accessToken->getData()->getAccessToken(),
					]));
				}
			}

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.update.gateway.success',
					['name' => $gateway->getName() ?? $gateway->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'install-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.update.gateway.error'),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.manage.devices'),
			false,
		);

		$manage = (bool) $io->askQuestion($question);

		if (!$manage) {
			return;
		}

		$gateway = $this->devicesRepository->find($gateway->getId(), Entities\Devices\Gateway::class);
		assert($gateway instanceof Entities\Devices\Gateway);

		$this->askManageGatewayAction($io, $connector, $gateway);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 */
	private function deleteGateway(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
	): void
	{
		$gateway = $this->askWhichGateway($io, $connector);

		if ($gateway === null) {
			$io->info((string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.noGateways'));

			return;
		}

		$io->warning(
			(string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.messages.remove.gateway.confirm',
				['name' => $gateway->getName() ?? $gateway->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$this->devicesManager->delete($gateway);

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.remove.gateway.success',
					['name' => $gateway->getName() ?? $gateway->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'install-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.remove.gateway.error'),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws RuntimeException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function manageGateway(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
	): void
	{
		$gateway = $this->askWhichGateway($io, $connector);

		if ($gateway === null) {
			$io->info((string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.noGateways'));

			return;
		}

		$this->askManageGatewayAction($io, $connector, $gateway);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function listGateways(Style\SymfonyStyle $io, Entities\Connectors\Connector $connector): void
	{
		$findDevicesQuery = new Queries\Entities\FindGatewayDevices();
		$findDevicesQuery->forConnector($connector);

		/** @var array<Entities\Devices\Gateway> $devices */
		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class);
		usort(
			$devices,
			static fn (Entities\Devices\Gateway $a, Entities\Devices\Gateway $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.name'),
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.ipAddress'),
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.devicesCnt'),
		]);

		foreach ($devices as $index => $device) {
			$findDevicePropertyQuery = new Queries\Entities\FindDeviceVariableProperties();
			$findDevicePropertyQuery->forDevice($device);
			$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IP_ADDRESS);

			$ipAddressProperty = $this->devicesPropertiesRepository->findOneBy(
				$findDevicePropertyQuery,
				DevicesEntities\Devices\Properties\Variable::class,
			);

			$findDevicesQuery = new Queries\Entities\FindDevices();
			$findDevicesQuery->forParent($device);

			$childDevices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Device::class);

			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
				$ipAddressProperty?->getValue(),
				count($childDevices),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function discoverDevices(Style\SymfonyStyle $io, Entities\Connectors\Connector $connector): void
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

		$io->info((string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.discover.starting'));

		$result = $serviceCmd->run(new Input\ArrayInput([
			'--connector' => $connector->getId()->toString(),
			'--mode' => DevicesTypes\ConnectorMode::DISCOVER->value,
			'--no-interaction' => true,
			'--quiet' => true,
		]), $this->output);

		$this->databaseHelper->clear();

		$io->newLine(2);

		$io->info((string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.discover.stopping'));

		if ($result !== Console\Command\Command::SUCCESS) {
			$io->error(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.discover.error'),
			);

			return;
		}

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.id'),
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.name'),
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.model'),
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.gateway'),
		]);

		$foundDevices = 0;

		$findDevicesQuery = new Queries\Entities\FindGatewayDevices();
		$findDevicesQuery->forConnector($connector);

		$gateways = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class);

		foreach ($gateways as $gateway) {
			$findDevicesQuery = new Queries\Entities\FindSubDevices();
			$findDevicesQuery->forConnector($gateway->getConnector());
			$findDevicesQuery->forParent($gateway);

			$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\SubDevice::class);

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
						$gateway->getName() ?? $gateway->getIdentifier(),
					]);
				}
			}
		}

		if ($foundDevices > 0) {
			$io->info(sprintf(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.foundDevices'),
				$foundDevices,
			));

			$table->render();

			$io->newLine();

		} else {
			$io->info(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.noDevicesFound'),
			);
		}

		$io->success(
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.discover.success'),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function createDevice(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
		Entities\Devices\Gateway $gateway,
	): void
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.questions.provide.device.identifier',
			),
		);

		$question->setValidator(function (string|null $answer) {
			if ($answer !== '' && $answer !== null) {
				$findDeviceQuery = new Queries\Entities\FindDevices();
				$findDeviceQuery->byIdentifier($answer);

				if (
					$this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Device::class) !== null
				) {
					throw new Exceptions\Runtime(
						(string) $this->translator->translate(
							'//ns-panel-connector.cmd.install.messages.identifier.device.used',
						),
					);
				}
			}

			return $answer;
		});

		$identifier = $io->askQuestion($question);

		if ($identifier === '' || $identifier === null) {
			$identifierPattern = 'ns-panel-device-%d';

			$identifier = $this->findNextDeviceIdentifier($connector, $identifierPattern);
		}

		if ($identifier === '') {
			$io->error(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.identifier.device.missing',
				),
			);

			return;
		}

		assert(is_string($identifier));

		$name = $this->askDeviceName($io);

		$category = $this->askDeviceCategory($io);

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$device = $this->devicesManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Devices\ThirdPartyDevice::class,
				'connector' => $connector,
				'parent' => $gateway,
				'identifier' => $identifier,
				'name' => $name,
			]));
			assert($device instanceof Entities\Devices\ThirdPartyDevice);

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::CATEGORY->value,
				'dataType' => MetadataTypes\DataType::STRING,
				'value' => $category->value,
				'device' => $device,
			]));

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.create.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'install-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.create.device.error'),
			);

			return;
		} finally {
			$this->databaseHelper->clear();
		}

		$device = $this->devicesRepository->find($device->getId(), Entities\Devices\ThirdPartyDevice::class);
		assert($device instanceof Entities\Devices\ThirdPartyDevice);

		do {
			$channel = $this->createCapability($io, $device);

		} while ($channel !== null);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function editDevice(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
		Entities\Devices\Gateway $gateway,
	): void
	{
		$device = $this->askWhichDevice($io, $connector, $gateway);

		if ($device === null) {
			$io->info((string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.noDevices'));

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.create.device'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createDevice($io, $connector, $gateway);
			}

			return;
		}

		$name = $this->askDeviceName($io, $device);

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$device = $this->devicesManager->update($device, Utils\ArrayHash::from([
				'name' => $name,
			]));

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.update.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'install-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.update.device.error'),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function deleteDevice(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
		Entities\Devices\Gateway $gateway,
	): void
	{
		$device = $this->askWhichDevice($io, $connector, $gateway);

		if ($device === null) {
			$io->info((string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.noDevices'));

			return;
		}

		$io->warning(
			(string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.messages.remove.device.confirm',
				['name' => $device->getName() ?? $device->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		if (
			$device instanceof Entities\Devices\ThirdPartyDevice
			&& $device->getGatewayIdentifier() !== null
			&& $gateway->getIpAddress() !== null
			&& $gateway->getAccessToken() !== null
		) {
			$panelApi = $this->lanApiFactory->create($connector->getIdentifier());

			try {
				$panelApi->removeDevice(
					$device->getGatewayIdentifier(),
					$gateway->getIpAddress(),
					$gateway->getAccessToken(),
					API\LanApi::GATEWAY_PORT,
					false,
				);
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'Calling NS Panel api failed',
					[
						'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
						'type' => 'install-cmd',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
					],
				);

				$io->error((string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.removeDeviceFromPanelFailed',
					[
						'name' => $device->getName() ?? $device->getIdentifier(),
						'panel' => $gateway->getName() ?? $gateway->getIdentifier(),
					],
				));
			}
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$this->devicesManager->delete($device);

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.remove.device.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'install-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.remove.device.error'),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function manageDevice(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
		Entities\Devices\Gateway $gateway,
	): void
	{
		$device = $this->askWhichDevice($io, $connector, $gateway, true);

		if ($device === null) {
			$io->info((string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.noDevices'));

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.create.device'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createDevice($io, $connector, $gateway);
			}

			return;
		}

		assert($device instanceof Entities\Devices\ThirdPartyDevice);

		$this->askManageDeviceAction($io, $device);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function listDevices(Style\SymfonyStyle $io, Entities\Devices\Gateway $gateway): void
	{
		$findDevicesQuery = new Queries\Entities\FindDevices();
		$findDevicesQuery->forParent($gateway);

		/** @var array<Entities\Devices\Device> $devices */
		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Device::class);
		usort(
			$devices,
			static fn (Entities\Devices\Device $a, Entities\Devices\Device $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.name'),
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.category'),
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.capabilities'),
		]);

		foreach ($devices as $index => $device) {
			assert(
				$device instanceof Entities\Devices\ThirdPartyDevice || $device instanceof Entities\Devices\SubDevice,
			);

			$findChannelsQuery = new Queries\Entities\FindChannels();
			$findChannelsQuery->forDevice($device);

			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.base.deviceType.' . $device->getDisplayCategory()->value,
				),
				implode(
					', ',
					array_map(
						fn (Entities\Channels\Channel $channel): string => (string) $this->translator->translate(
							'//ns-panel-connector.cmd.base.capability.' . $channel->getCapability()->value,
						),
						$this->channelsRepository->findAllBy($findChannelsQuery, Entities\Channels\Channel::class),
					),
				),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function createCapability(
		Style\SymfonyStyle $io,
		Entities\Devices\ThirdPartyDevice $device,
	): Entities\Channels\Channel|null
	{
		$capability = $this->askCapabilityType($io, $device);

		if ($capability === null) {
			return null;
		}

		$metadata = $this->loader->loadCapabilities();

		if (!$metadata->offsetExists($capability->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for capability: %s was not found',
				$capability->value,
			));
		}

		$capabilityMetadata = $metadata->offsetGet($capability->value);

		if (
			!$capabilityMetadata instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('permission')
			|| !is_string($capabilityMetadata->offsetGet('permission'))
			|| Types\Permission::tryFrom($capabilityMetadata->offsetGet('permission')) === null
			|| !$capabilityMetadata->offsetExists('protocol')
			|| !$capabilityMetadata->offsetGet('protocol') instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('multiple')
			|| !is_bool($capabilityMetadata->offsetGet('multiple'))
		) {
			throw new Exceptions\InvalidState('Capability definition is missing required attributes');
		}

		$allowMultiple = $capabilityMetadata->offsetGet('multiple');

		if ($allowMultiple) {
			$identifier = $this->findNextChannelIdentifier($device, $capability->value);

		} else {
			$identifier = Helpers\Name::convertCapabilityToChannel($capability);

			$findChannelQuery = new Queries\Entities\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier($identifier);

			$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Channel::class);

			if ($channel !== null) {
				$io->error(
					(string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.messages.noMultipleCapabilities',
						['type' => $channel->getIdentifier()],
					),
				);

				return null;
			}
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			preg_match(NsPanel\Constants::CHANNEL_IDENTIFIER, $identifier, $matches);

			$channel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Channels\Channel::class,
				'identifier' => $identifier,
				'name' => (string) $this->translator->translate(
					'//ns-panel-connector.cmd.base.capability.' . $capability->value,
				) . (array_key_exists(
					'key',
					$matches,
				) ? ' ' . $matches['key'] : ''),
				'device' => $device,
			]));
			assert($channel instanceof Entities\Channels\Channel);

			do {
				$property = $this->createProtocol($io, $device, $channel);
			} while ($property !== null);

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.create.capability.success',
					['name' => $channel->getName() ?? $channel->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'install-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.create.capability.error',
				),
			);

			return null;
		} finally {
			$this->databaseHelper->clear();
		}

		$channel = $this->channelsRepository->find($channel->getId(), Entities\Channels\Channel::class);
		assert($channel instanceof Entities\Channels\Channel);

		return $channel;
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function editCapability(Style\SymfonyStyle $io, Entities\Devices\ThirdPartyDevice $device): void
	{
		$channel = $this->askWhichCapability($io, $device);

		if ($channel === null) {
			$io->info(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.noCapabilities'),
			);

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.create.capability'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createCapability($io, $device);
			}

			return;
		} elseif ($channel === false) {
			return;
		}

		$type = $channel->getCapability();

		$metadata = $this->loader->loadCapabilities();

		if (!$metadata->offsetExists($type->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for capability: %s was not found',
				$type->value,
			));
		}

		$capabilityMetadata = $metadata->offsetGet($type->value);

		if (
			!$capabilityMetadata instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('permission')
			|| !is_string($capabilityMetadata->offsetGet('permission'))
			|| Types\Permission::tryFrom($capabilityMetadata->offsetGet('permission')) === null
			|| !$capabilityMetadata->offsetExists('protocol')
			|| !$capabilityMetadata->offsetGet('protocol') instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('multiple')
			|| !is_bool($capabilityMetadata->offsetGet('multiple'))
		) {
			throw new Exceptions\InvalidState('Capability definition is missing required attributes');
		}

		$protocols = (array) $capabilityMetadata->offsetGet('protocol');

		$missingProtocols = [];

		foreach ($protocols as $protocol) {
			$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertyQuery->forChannel($channel);
			$findChannelPropertyQuery->byIdentifier(
				Helpers\Name::convertProtocolToProperty(Types\Protocol::from($protocol)),
			);

			$property = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

			if ($property === null) {
				$missingProtocols[] = $protocol;
			}
		}

		try {
			if (count($missingProtocols) > 0) {
				// Start transaction connection to the database
				$this->databaseHelper->beginTransaction();

				do {
					$property = $this->createProtocol($io, $device, $channel);
				} while ($property !== null);

				// Commit all changes into database
				$this->databaseHelper->commitTransaction();

				$io->success(
					(string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.messages.update.capability.success',
						['name' => $channel->getName() ?? $channel->getIdentifier()],
					),
				);
			} else {
				$io->success(
					(string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.messages.noMissingProtocols',
						['name' => $channel->getName() ?? $channel->getIdentifier()],
					),
				);
			}
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'install-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.update.capability.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function manageCapability(
		Style\SymfonyStyle $io,
		Entities\Devices\ThirdPartyDevice $device,
	): void
	{
		$channel = $this->askWhichCapability($io, $device);

		if ($channel === null) {
			$io->info(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.noCapabilities'),
			);

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.create.capability'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createCapability($io, $device);
			}

			return;
		} elseif ($channel === false) {
			return;
		}

		$this->askProtocolAction($io, $channel);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 */
	private function deleteCapability(Style\SymfonyStyle $io, Entities\Devices\ThirdPartyDevice $device): void
	{
		$channel = $this->askWhichCapability($io, $device);

		if ($channel === null) {
			$io->info(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.noCapabilities'),
			);

			return;
		} elseif ($channel === false) {
			return;
		}

		$io->warning(
			(string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.messages.remove.capability.confirm',
				['name' => $channel->getName() ?? $channel->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$this->channelsManager->delete($channel);

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.remove.capability.success',
					['name' => $channel->getName() ?? $channel->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'install-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.remove.capability.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function listCapabilities(Style\SymfonyStyle $io, Entities\Devices\ThirdPartyDevice $device): void
	{
		$findChannelsQuery = new Queries\Entities\FindChannels();
		$findChannelsQuery->forDevice($device);

		/** @var array<Entities\Channels\Channel> $deviceChannels */
		$deviceChannels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\Channels\Channel::class);
		usort(
			$deviceChannels,
			static fn (Entities\Channels\Channel $a, Entities\Channels\Channel $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.name'),
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.type'),
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.protocols'),
		]);

		foreach ($deviceChannels as $index => $channel) {
			$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
			$findChannelPropertiesQuery->forChannel($channel);

			$table->addRow([
				$index + 1,
				$channel->getName() ?? $channel->getIdentifier(),
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.base.capability.' . $channel->getCapability()->value,
				),
				implode(
					', ',
					array_map(
						fn (DevicesEntities\Channels\Properties\Property $property): string => (string) $this->translator->translate(
							'//ns-panel-connector.cmd.base.protocol.' . Helpers\Name::convertPropertyToProtocol(
								$property->getIdentifier(),
							)->value,
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
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 * @throws Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function createProtocol(
		Style\SymfonyStyle $io,
		Entities\Devices\ThirdPartyDevice $device,
		Entities\Channels\Channel $channel,
	): DevicesEntities\Channels\Properties\Property|null
	{
		$capabilitiesMetadata = $this->loader->loadCapabilities();

		if (!$capabilitiesMetadata->offsetExists($channel->getCapability()->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for capability: %s was not found',
				$channel->getCapability()->value,
			));
		}

		$capabilityMetadata = $capabilitiesMetadata->offsetGet($channel->getCapability()->value);

		if (
			!$capabilityMetadata instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('permission')
			|| !is_string($capabilityMetadata->offsetGet('permission'))
			|| Types\Permission::tryFrom($capabilityMetadata->offsetGet('permission')) === null
			|| !$capabilityMetadata->offsetExists('protocol')
			|| !$capabilityMetadata->offsetGet('protocol') instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('multiple')
			|| !is_bool($capabilityMetadata->offsetGet('multiple'))
		) {
			throw new Exceptions\InvalidState('Capability definition is missing required attributes');
		}

		$capabilityPermission = $capabilityMetadata->offsetGet('permission');

		$protocol = $this->askProtocolType($io, $channel);

		if ($protocol === null) {
			return null;
		}

		$metadata = $this->loader->loadProtocols();

		if (!$metadata->offsetExists($protocol->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for protocol: %s was not found',
				$protocol->value,
			));
		}

		$protocolMetadata = $metadata->offsetGet($protocol->value);

		if (
			!$protocolMetadata instanceof Utils\ArrayHash
			|| !$protocolMetadata->offsetExists('data_type')
			|| !is_string($protocolMetadata->offsetGet('data_type'))
		) {
			throw new Exceptions\InvalidState('Protocol definition is missing required attributes');
		}

		$dataType = MetadataTypes\DataType::from($protocolMetadata->offsetGet('data_type'));

		$format = $this->askFormat($io, $protocol);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.connectProtocol'),
			true,
		);

		$connect = (bool) $io->askQuestion($question);

		if ($connect) {
			$connectProperty = $this->askProperty(
				$io,
				null,
				in_array(
					$capabilityPermission,
					[Types\Permission::WRITE->value, Types\Permission::READ_WRITE->value],
					true,
				),
			);

			$format = $this->askFormat($io, $protocol, $connectProperty);

			if ($connectProperty instanceof DevicesEntities\Channels\Properties\Dynamic) {
				if (!$device->hasParent($connectProperty->getChannel()->getDevice())) {
					$this->devicesManager->update($device, Utils\ArrayHash::from([
						'parents' => array_merge($device->getParents(), [$connectProperty->getChannel()->getDevice()]),
					]));
				}

				return $this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Mapped::class,
					'parent' => $connectProperty,
					'identifier' => Helpers\Name::convertProtocolToProperty($protocol),
					'name' => (string) $this->translator->translate(
						'//ns-panel-connector.cmd.base.protocol.' . $protocol->value,
					),
					'channel' => $channel,
					'dataType' => $dataType,
					'format' => $format,
					'invalid' => $protocolMetadata->offsetExists('invalid_value')
						? $protocolMetadata->offsetGet('invalid_value')
						: null,
					'settable' => $connectProperty->isSettable(),
					'queryable' => $connectProperty->isQueryable(),
				]));
			}
		} else {
			$value = $this->provideProtocolValue($io, $protocol);

			return $this->channelsPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Channels\Properties\Variable::class,
				'identifier' => Helpers\Name::convertProtocolToProperty($protocol),
				'name' => (string) $this->translator->translate(
					'//ns-panel-connector.cmd.base.protocol.' . $protocol->value,
				),
				'channel' => $channel,
				'dataType' => $dataType,
				'format' => $format,
				'value' => $value,
				'invalid' => $protocolMetadata->offsetExists('invalid_value')
					? $protocolMetadata->offsetGet('invalid_value')
					: null,
			]));
		}

		return null;
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function editProtocol(Style\SymfonyStyle $io, Entities\Channels\Channel $channel): void
	{
		$capabilitiesMetadata = $this->loader->loadCapabilities();

		if (!$capabilitiesMetadata->offsetExists($channel->getCapability()->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for capability: %s was not found',
				$channel->getCapability()->value,
			));
		}

		$capabilityMetadata = $capabilitiesMetadata->offsetGet($channel->getCapability()->value);

		if (
			!$capabilityMetadata instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('permission')
			|| !is_string($capabilityMetadata->offsetGet('permission'))
			|| Types\Permission::tryFrom($capabilityMetadata->offsetGet('permission')) === null
			|| !$capabilityMetadata->offsetExists('protocol')
			|| !$capabilityMetadata->offsetGet('protocol') instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('multiple')
			|| !is_bool($capabilityMetadata->offsetGet('multiple'))
		) {
			throw new Exceptions\InvalidState('Capability definition is missing required attributes');
		}

		$capabilityPermission = $capabilityMetadata->offsetGet('permission');

		$property = $this->askWhichProtocol($io, $channel);

		if ($property === null) {
			$io->info((string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.noProtocols'));

			return;
		}

		$protocol = Helpers\Name::convertPropertyToProtocol($property->getIdentifier());

		$metadata = $this->loader->loadProtocols();

		if (!$metadata->offsetExists($protocol->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for protocol: %s was not found',
				$protocol->value,
			));
		}

		$protocolMetadata = $metadata->offsetGet($protocol->value);

		if (
			!$protocolMetadata instanceof Utils\ArrayHash
			|| !$protocolMetadata->offsetExists('data_type')
		) {
			throw new Exceptions\InvalidState('Protocol definition is missing required attributes');
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$dataType = MetadataTypes\DataType::from(strval($protocolMetadata->offsetGet('data_type')));

			$format = $this->askFormat($io, $protocol);

			$question = new Console\Question\ConfirmationQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.connectProtocol'),
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
					in_array(
						$capabilityPermission,
						[Types\Permission::WRITE->value, Types\Permission::READ_WRITE->value],
						true,
					),
				);

				$format = $this->askFormat($io, $protocol, $connectProperty);

				if (
					$property instanceof DevicesEntities\Channels\Properties\Mapped
					&& $connectProperty instanceof DevicesEntities\Channels\Properties\Dynamic
				) {
					$this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
						'parent' => $connectProperty,
						'format' => $format,
						'invalid' => $protocolMetadata->offsetExists('invalid_value')
							? $protocolMetadata->offsetGet('invalid_value')
							: null,
					]));
				} else {
					$this->channelsPropertiesManager->delete($property);

					if ($connectProperty instanceof DevicesEntities\Channels\Properties\Dynamic) {
						$property = $this->channelsPropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Channels\Properties\Mapped::class,
							'parent' => $connectProperty,
							'identifier' => $property->getIdentifier(),
							'name' => (string) $this->translator->translate(
								'//ns-panel-connector.cmd.base.protocol.' . $protocol->value,
							),
							'channel' => $channel,
							'dataType' => $dataType,
							'format' => $format,
							'invalid' => $protocolMetadata->offsetExists('invalid_value')
								? $protocolMetadata->offsetGet('invalid_value')
								: null,
							'settable' => $connectProperty->isSettable(),
							'queryable' => $connectProperty->isQueryable(),
						]));
					}
				}
			} else {
				$value = $this->provideProtocolValue(
					$io,
					$protocol,
					$property instanceof DevicesEntities\Channels\Properties\Variable ? $property->getValue() : null,
				);

				if ($property instanceof DevicesEntities\Channels\Properties\Variable) {
					$this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
						'value' => $value,
						'format' => $format,
						'invalid' => $protocolMetadata->offsetExists('invalid_value')
							? $protocolMetadata->offsetGet('invalid_value')
							: null,
					]));
				} else {
					$this->channelsPropertiesManager->delete($property);

					$property = $this->channelsPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => $property->getIdentifier(),
						'name' => (string) $this->translator->translate(
							'//ns-panel-connector.cmd.base.protocol.' . $protocol->value,
						),
						'channel' => $channel,
						'dataType' => $dataType,
						'format' => $format,
						'value' => $value,
						'invalid' => $protocolMetadata->offsetExists('invalid_value')
							? $protocolMetadata->offsetGet('invalid_value')
							: null,
					]));
				}
			}

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.update.protocol.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'install-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.update.protocol.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}

		$this->askProtocolAction($io, $channel);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function deleteProtocol(Style\SymfonyStyle $io, Entities\Channels\Channel $channel): void
	{
		$property = $this->askWhichProtocol($io, $channel);

		if ($property === null) {
			$io->info((string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.noProtocols'));

			return;
		}

		$io->warning(
			(string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.messages.remove.protocol.confirm',
				['name' => $property->getName() ?? $property->getIdentifier()],
			),
		);

		$question = new Console\Question\ConfirmationQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.questions.continue'),
			false,
		);

		$continue = (bool) $io->askQuestion($question);

		if (!$continue) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->databaseHelper->beginTransaction();

			$this->channelsPropertiesManager->delete($property);

			// Commit all changes into database
			$this->databaseHelper->commitTransaction();

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.remove.protocol.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'install-cmd',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
				],
			);

			$io->success(
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.messages.remove.protocol.error',
				),
			);
		} finally {
			$this->databaseHelper->clear();
		}

		$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		if (count($this->channelsPropertiesRepository->findAllBy($findChannelPropertiesQuery)) > 0) {
			$this->askProtocolAction($io, $channel);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function listProtocols(Style\SymfonyStyle $io, Entities\Channels\Channel $channel): void
	{
		$findPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findPropertiesQuery->forChannel($channel);

		$channelProperties = $this->channelsPropertiesRepository->findAllBy($findPropertiesQuery);
		usort(
			$channelProperties,
			static fn (DevicesEntities\Property $a, DevicesEntities\Property $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.name'),
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.type'),
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.data.value'),
		]);

		$metadata = $this->loader->loadProtocols();

		foreach ($channelProperties as $index => $property) {
			$type = Helpers\Name::convertPropertyToProtocol($property->getIdentifier());

			$value = $property instanceof DevicesEntities\Channels\Properties\Variable ? $property->getValue() : 'N/A';

			if (
				$property->getDataType() === MetadataTypes\DataType::ENUM
				&& $metadata->offsetExists($type->value)
				&& $metadata->offsetGet($type->value) instanceof Utils\ArrayHash
				&& $metadata->offsetGet($type->value)->offsetExists('valid_values')
				&& $metadata->offsetGet($type->value)->offsetGet('valid_values') instanceof Utils\ArrayHash
			) {
				$enumValue = array_search(
					intval(MetadataUtilities\Value::flattenValue($value)),
					(array) $metadata->offsetGet($type->value)->offsetGet('valid_values'),
					true,
				);

				if ($enumValue !== false) {
					$value = $enumValue;
				}
			}

			$table->addRow([
				$index + 1,
				$property->getName() ?? $property->getIdentifier(),
				(string) $this->translator->translate(
					'//ns-panel-connector.cmd.base.protocol.' . Helpers\Name::convertPropertyToProtocol(
						$property->getIdentifier(),
					)->value,
				),
				$value,
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askInstallAction(Style\SymfonyStyle $io): void
	{
		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
			[
				0 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.create.connector'),
				1 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.update.connector'),
				2 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.remove.connector'),
				3 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.manage.connector'),
				4 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.list.connectors'),
				5 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.nothing'),
			],
			5,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.create.connector',
			)
			|| $whatToDo === '0'
		) {
			$this->createConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.update.connector',
			)
			|| $whatToDo === '1'
		) {
			$this->editConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.remove.connector',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.manage.connector',
			)
			|| $whatToDo === '3'
		) {
			$this->manageConnector($io);

			$this->askInstallAction($io);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.list.connectors',
			)
			|| $whatToDo === '4'
		) {
			$this->listConnectors($io);

			$this->askInstallAction($io);
		}
	}

	/**
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askManageConnectorAction(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
	): void
	{
		$question
			= $connector->getClientMode() === Types\ClientMode::GATEWAY
			|| $connector->getClientMode() === Types\ClientMode::BOTH
		? new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
			[
				0 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.create.gateway'),
				1 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.update.gateway'),
				2 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.remove.gateway'),
				3 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.manage.gateway'),
				4 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.list.gateways'),
				5 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.discover.devices'),
				6 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.nothing'),
			],
			6,
		)
		: new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
			[
				0 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.create.gateway'),
				1 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.update.gateway'),
				2 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.remove.gateway'),
				3 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.manage.gateway'),
				4 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.list.gateways'),
				5 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.nothing'),
			],
			5,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.create.gateway',
			)
			|| $whatToDo === '0'
		) {
			$this->createGateway($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.update.gateway',
			)
			|| $whatToDo === '1'
		) {
			$this->editGateway($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.remove.gateway',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteGateway($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.manage.gateway',
			)
			|| $whatToDo === '3'
		) {
			$this->manageGateway($io, $connector);

			$this->askManageConnectorAction($io, $connector);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.list.gateways',
			)
			|| $whatToDo === '4'
		) {
			$this->listGateways($io, $connector);

			$this->askManageConnectorAction($io, $connector);
		}

		if (
			$connector->getClientMode() === Types\ClientMode::GATEWAY
			|| $connector->getClientMode() === Types\ClientMode::BOTH
		) {
			if (
				$whatToDo === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.discover.devices',
				)
				|| $whatToDo === '5'
			) {
				$this->discoverDevices($io, $connector);

				$this->askManageConnectorAction($io, $connector);
			}
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Console\Exception\ExceptionInterface
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askManageGatewayAction(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
		Entities\Devices\Gateway $gateway,
	): void
	{
		if ($connector->getClientMode() === Types\ClientMode::DEVICE) {
			$question = new Console\Question\ChoiceQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
				[
					0 => (string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.actions.create.device',
					),
					1 => (string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.actions.update.device',
					),
					2 => (string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.actions.remove.device',
					),
					3 => (string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.actions.manage.device',
					),
					4 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.list.devices'),
					5 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.nothing'),
				],
				5,
			);

		} elseif ($connector->getClientMode() === Types\ClientMode::GATEWAY) {
			$question = new Console\Question\ChoiceQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
				[
					0 => (string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.actions.update.device',
					),
					1 => (string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.actions.remove.device',
					),
					2 => (string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.actions.manage.device',
					),
					3 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.list.devices'),
					4 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.nothing'),
				],
				4,
			);

		} else {
			$question = new Console\Question\ChoiceQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
				[
					0 => (string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.actions.create.device',
					),
					1 => (string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.actions.update.device',
					),
					2 => (string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.actions.remove.device',
					),
					3 => (string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.actions.manage.device',
					),
					4 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.list.devices'),
					5 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.nothing'),
				],
				5,
			);
		}

		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if ($connector->getClientMode() === Types\ClientMode::DEVICE) {
			if (
				$whatToDo === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.create.device',
				)
				|| $whatToDo === '0'
			) {
				$this->createDevice($io, $connector, $gateway);

				$this->askManageGatewayAction($io, $connector, $gateway);

			} elseif (
				$whatToDo === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.update.device',
				)
				|| $whatToDo === '1'
			) {
				$this->editDevice($io, $connector, $gateway);

				$this->askManageGatewayAction($io, $connector, $gateway);

			} elseif (
				$whatToDo === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.remove.device',
				)
				|| $whatToDo === '2'
			) {
				$this->deleteDevice($io, $connector, $gateway);

				$this->askManageGatewayAction($io, $connector, $gateway);

			} elseif (
				$whatToDo === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.manage.device',
				)
				|| $whatToDo === '3'
			) {
				$this->manageDevice($io, $connector, $gateway);

				$this->askManageGatewayAction($io, $connector, $gateway);

			} elseif (
				$whatToDo === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.list.devices',
				)
				|| $whatToDo === '4'
			) {
				$this->listDevices($io, $gateway);

				$this->askManageGatewayAction($io, $connector, $gateway);
			}
		} elseif ($connector->getClientMode() === Types\ClientMode::GATEWAY) {
			if (
				$whatToDo === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.update.device',
				)
				|| $whatToDo === '0'
			) {
				$this->editDevice($io, $connector, $gateway);

				$this->askManageGatewayAction($io, $connector, $gateway);

			} elseif (
				$whatToDo === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.remove.device',
				)
				|| $whatToDo === '1'
			) {
				$this->deleteDevice($io, $connector, $gateway);

				$this->askManageGatewayAction($io, $connector, $gateway);

			} elseif (
				$whatToDo === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.manage.device',
				)
				|| $whatToDo === '2'
			) {
				$this->manageDevice($io, $connector, $gateway);

				$this->askManageGatewayAction($io, $connector, $gateway);

			} elseif (
				$whatToDo === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.list.devices',
				)
				|| $whatToDo === '3'
			) {
				$this->listDevices($io, $gateway);

				$this->askManageGatewayAction($io, $connector, $gateway);
			}
		} else {
			if (
				$whatToDo === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.create.device',
				)
				|| $whatToDo === '0'
			) {
				$this->createDevice($io, $connector, $gateway);

				$this->askManageGatewayAction($io, $connector, $gateway);

			} elseif (
				$whatToDo === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.update.device',
				)
				|| $whatToDo === '1'
			) {
				$this->editDevice($io, $connector, $gateway);

				$this->askManageGatewayAction($io, $connector, $gateway);

			} elseif (
				$whatToDo === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.remove.device',
				)
				|| $whatToDo === '2'
			) {
				$this->deleteDevice($io, $connector, $gateway);

				$this->askManageGatewayAction($io, $connector, $gateway);

			} elseif (
				$whatToDo === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.manage.device',
				)
				|| $whatToDo === '3'
			) {
				$this->manageDevice($io, $connector, $gateway);

				$this->askManageGatewayAction($io, $connector, $gateway);

			} elseif (
				$whatToDo === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.list.devices',
				)
				|| $whatToDo === '4'
			) {
				$this->listDevices($io, $gateway);

				$this->askManageGatewayAction($io, $connector, $gateway);
			}
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askManageDeviceAction(
		Style\SymfonyStyle $io,
		Entities\Devices\ThirdPartyDevice $device,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
			[
				0 => (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.create.capability',
				),
				1 => (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.update.capability',
				),
				2 => (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.remove.capability',
				),
				3 => (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.manage.capability',
				),
				4 => (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.actions.list.capabilities',
				),
				5 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.nothing'),
			],
			5,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.create.capability',
			)
			|| $whatToDo === '0'
		) {
			$this->createCapability($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.update.capability',
			)
			|| $whatToDo === '1'
		) {
			$this->editCapability($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.remove.capability',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteCapability($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.manage.capability',
			)
			|| $whatToDo === '3'
		) {
			$this->manageCapability($io, $device);

			$this->askManageDeviceAction($io, $device);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.list.capabilities',
			)
			|| $whatToDo === '4'
		) {
			$this->listCapabilities($io, $device);

			$this->askManageDeviceAction($io, $device);
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askProtocolAction(
		Style\SymfonyStyle $io,
		Entities\Channels\Channel $channel,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
			[
				0 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.update.protocol'),
				1 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.remove.protocol'),
				2 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.list.protocols'),
				3 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.actions.nothing'),
			],
			3,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.update.protocol',
			)
			|| $whatToDo === '0'
		) {
			$this->editProtocol($io, $channel);

			$this->askProtocolAction($io, $channel);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.remove.protocol',
			)
			|| $whatToDo === '1'
		) {
			$this->deleteProtocol($io, $channel);

			$this->askProtocolAction($io, $channel);

		} elseif (
			$whatToDo === (string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.actions.list.protocols',
			)
			|| $whatToDo === '2'
		) {
			$this->listProtocols($io, $channel);

			$this->askProtocolAction($io, $channel);
		}
	}

	private function askConnectorMode(Style\SymfonyStyle $io): Types\ClientMode
	{
		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.select.connector.mode'),
			[
				0 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.answers.mode.gateway'),
				1 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.answers.mode.device'),
				2 => (string) $this->translator->translate('//ns-panel-connector.cmd.install.answers.mode.both'),
			],
			2,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer): Types\ClientMode {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (
				$answer === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.answers.mode.gateway',
				)
				|| $answer === '0'
			) {
				return Types\ClientMode::GATEWAY;
			}

			if (
				$answer === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.answers.mode.device',
				)
				|| $answer === '1'
			) {
				return Types\ClientMode::DEVICE;
			}

			if (
				$answer === (string) $this->translator->translate(
					'//ns-panel-connector.cmd.install.answers.mode.both',
				)
				|| $answer === '2'
			) {
				return Types\ClientMode::BOTH;
			}

			throw new Exceptions\Runtime(
				sprintf(
					(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\ClientMode);

		return $answer;
	}

	private function askConnectorName(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector|null $connector = null,
	): string|null
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.provide.connector.name'),
			$connector?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	private function askDeviceName(Style\SymfonyStyle $io, Entities\Devices\Device|null $device = null): string|null
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.provide.device.name'),
			$device?->getName(),
		);

		$name = $io->askQuestion($question);

		return strval($name) === '' ? null : strval($name);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askDeviceCategory(
		Style\SymfonyStyle $io,
		Entities\Devices\ThirdPartyDevice|null $device = null,
	): Types\Category
	{
		$metadata = $this->loader->loadCategories();

		$categories = [];

		foreach ((array) $metadata as $type => $categoryMetadata) {
			if (
				$categoryMetadata instanceof Utils\ArrayHash
				&& $categoryMetadata->offsetExists('capabilities')
				&& $categoryMetadata->offsetGet('capabilities') instanceof Utils\ArrayHash
			) {
				$requiredCapabilities = $categoryMetadata->offsetGet('capabilities');

				if ((array) $requiredCapabilities !== []) {
					$categories[$type] = (string) $this->translator->translate(
						'//ns-panel-connector.cmd.base.deviceType.' . $type,
					);
				}
			}
		}

		asort($categories);

		$default = $device !== null ? array_search(
			(string) $this->translator->translate(
				'//ns-panel-connector.cmd.base.deviceType.' . $device->getDisplayCategory()->value,
			),
			array_values($categories),
			true,
		) : null;

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.select.device.category'),
			array_values($categories),
			$default,
		);
		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer) use ($categories): Types\Category {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($categories))) {
				$answer = array_values($categories)[$answer];
			}

			$category = array_search($answer, $categories, true);

			if ($category !== false && Types\Category::tryFrom($category) !== null) {
				return Types\Category::from($category);
			}

			throw new Exceptions\Runtime(
				sprintf(
					(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\Category);

		return $answer;
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askCapabilityType(
		Style\SymfonyStyle $io,
		Entities\Devices\ThirdPartyDevice $device,
	): Types\Capability|null
	{
		$metadata = $this->loader->loadCategories();

		if (!array_key_exists($device->getDisplayCategory()->value, (array) $metadata)) {
			return null;
		}

		$categoryMetadata = $metadata[$device->getDisplayCategory()->value];

		$capabilities = [];

		if (
			$categoryMetadata instanceof Utils\ArrayHash
			&& $categoryMetadata->offsetExists('capabilities')
			&& $categoryMetadata->offsetGet('capabilities') instanceof Utils\ArrayHash
			&& $categoryMetadata->offsetExists('optionalCapabilities')
			&& $categoryMetadata->offsetGet('optionalCapabilities') instanceof Utils\ArrayHash
		) {
			$metadata = $this->loader->loadCapabilities();

			$requiredCapabilities = $categoryMetadata->offsetGet('capabilities');

			foreach ($requiredCapabilities as $type) {
				if (
					array_key_exists(strval($type), (array) $metadata)
					&& $metadata[$type] instanceof Utils\ArrayHash
					&& $metadata[$type]->offsetExists('multiple')
					&& is_bool($metadata[$type]->offsetGet('multiple'))
				) {
					$allowMultiple = $metadata[$type]->offsetGet('multiple');

					$findChannelQuery = new Queries\Entities\FindChannels();
					$findChannelQuery->forDevice($device);
					$findChannelQuery->byIdentifier(
						Helpers\Name::convertCapabilityToChannel(Types\Capability::from(strval($type))),
					);

					$channel = $this->channelsRepository->findOneBy(
						$findChannelQuery,
						Entities\Channels\Channel::class,
					);

					if ($channel === null || $allowMultiple) {
						$capabilities[$type] = (string) $this->translator->translate(
							'//ns-panel-connector.cmd.base.capability.' . Types\Capability::from(strval($type))->value,
						);
					}
				}
			}

			if ($capabilities === []) {
				$optionalCapabilities = $categoryMetadata->offsetGet('optionalCapabilities');

				foreach ($optionalCapabilities as $type) {
					if (
						array_key_exists(strval($type), (array) $metadata)
						&& $metadata[$type] instanceof Utils\ArrayHash
						&& $metadata[$type]->offsetExists('multiple')
						&& is_bool($metadata[$type]->offsetGet('multiple'))
					) {
						$allowMultiple = $metadata[$type]->offsetGet('multiple');

						$findChannelQuery = new Queries\Entities\FindChannels();
						$findChannelQuery->forDevice($device);
						$findChannelQuery->byIdentifier(
							Helpers\Name::convertCapabilityToChannel(Types\Capability::from(strval($type))),
						);

						$channel = $this->channelsRepository->findOneBy(
							$findChannelQuery,
							Entities\Channels\Channel::class,
						);

						if ($channel === null || $allowMultiple) {
							$capabilities[$type] = (string) $this->translator->translate(
								'//ns-panel-connector.cmd.base.capability.' . Types\Capability::from(
									strval($type),
								)->value,
							);
						}
					}
				}

				if ($capabilities !== []) {
					$capabilities['none'] = (string) $this->translator->translate(
						'//ns-panel-connector.cmd.install.answers.none',
					);
				}
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.questions.select.device.capabilityType',
			),
			array_values($capabilities),
			0,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($capabilities): Types\Capability|null {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($capabilities))) {
				$answer = array_values($capabilities)[$answer];
			}

			$capability = array_search($answer, $capabilities, true);

			if ($capability === 'none') {
				return null;
			}

			if ($capability !== false) {
				return Types\Capability::from($capability);
			}

			throw new Exceptions\Runtime(
				sprintf(
					(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\Capability || $answer === null);

		return $answer;
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askProtocolType(
		Style\SymfonyStyle $io,
		Entities\Channels\Channel $channel,
	): Types\Protocol|null
	{
		$metadata = $this->loader->loadCapabilities();

		if (!$metadata->offsetExists($channel->getCapability()->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for capability: %s was not found',
				$channel->getCapability()->value,
			));
		}

		$capabilityMetadata = $metadata->offsetGet($channel->getCapability()->value);

		if (
			!$capabilityMetadata instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('permission')
			|| !is_string($capabilityMetadata->offsetGet('permission'))
			|| Types\Permission::tryFrom($capabilityMetadata->offsetGet('permission')) === null
			|| !$capabilityMetadata->offsetExists('protocol')
			|| !$capabilityMetadata->offsetGet('protocol') instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('multiple')
			|| !is_bool($capabilityMetadata->offsetGet('multiple'))
		) {
			throw new Exceptions\InvalidState('Capability definition is missing required attributes');
		}

		$capabilityProtocols = (array) $capabilityMetadata->offsetGet('protocol');

		$protocolsMetadata = $this->loader->loadProtocols();

		$protocols = [];

		foreach ($capabilityProtocols as $type) {
			if (
				$protocolsMetadata->offsetExists($type)
				&& $protocolsMetadata->offsetGet($type) instanceof Utils\ArrayHash
			) {
				$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
				$findChannelPropertyQuery->forChannel($channel);
				$findChannelPropertyQuery->byIdentifier(
					Helpers\Name::convertProtocolToProperty(Types\Protocol::from($type)),
				);

				$property = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

				if ($property === null) {
					$protocols[$type] = (string) $this->translator->translate(
						'//ns-panel-connector.cmd.base.protocol.' . Types\Protocol::from($type)->value,
					);
				}
			}
		}

		if ($protocols === []) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.questions.select.device.protocolType',
			),
			array_values($protocols),
			0,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($protocols): Types\Protocol {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($protocols))) {
				$answer = array_values($protocols)[$answer];
			}

			$protocol = array_search($answer, $protocols, true);

			if ($protocol !== false && Types\Protocol::tryFrom($protocol) !== null) {
				return Types\Protocol::from($protocol);
			}

			throw new Exceptions\Runtime(
				sprintf(
					(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\Protocol);

		return $answer;
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exception
	 */
	private function askProperty(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Properties\Dynamic|null $connectedProperty = null,
		bool|null $settable = null,
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
			),
		);

		foreach ($systemDevices as $device) {
			if ($device instanceof Entities\Devices\Device) {
				continue;
			}

			$findChannelsQuery = new DevicesQueries\Entities\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

			$hasProperty = false;

			foreach ($channels as $channel) {
				$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
				$findChannelPropertiesQuery->forChannel($channel);

				if ($settable === true) {
					$findChannelPropertiesQuery->settable(true);
				}

				if (
					$this->channelsPropertiesRepository->getResultSet(
						$findChannelPropertiesQuery,
						DevicesEntities\Channels\Properties\Dynamic::class,
					)->count() > 0
				) {
					$hasProperty = true;

					break;
				}
			}

			if (!$hasProperty) {
				continue;
			}

			$devices[$device->getId()->toString()] = '[' . ($device->getConnector()->getName() ?? $device->getConnector()->getIdentifier()) . '] '
				. ($device->getName() ?? $device->getIdentifier());
		}

		if (count($devices) === 0) {
			$io->warning(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.messages.noHardwareDevices'),
			);

			return null;
		}

		$default = count($devices) === 1 ? 0 : null;

		if ($connectedDevice !== null) {
			foreach (array_values(array_flip($devices)) as $index => $value) {
				if ($value === $connectedDevice->getId()->toString()) {
					$default = $index;

					break;
				}
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.questions.select.device.mappedDevice',
			),
			array_values($devices),
			$default,
		);
		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($devices): DevicesEntities\Devices\Device {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($devices))) {
				$answer = array_values($devices)[$answer];
			}

			$identifier = array_search($answer, $devices, true);

			if ($identifier !== false) {
				$device = $this->devicesRepository->find(Uuid\Uuid::fromString($identifier));

				if ($device !== null) {
					return $device;
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
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
			$hasProperty = false;

			$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
			$findChannelPropertiesQuery->forChannel($channel);

			if ($settable === true) {
				$findChannelPropertiesQuery->settable(true);
			}

			if (
				$this->channelsPropertiesRepository->getResultSet(
					$findChannelPropertiesQuery,
					DevicesEntities\Channels\Properties\Dynamic::class,
				)->count() > 0
			) {
				$hasProperty = true;
			}

			if (!$hasProperty) {
				continue;
			}

			$channels[$channel->getId()->toString()] = $channel->getName() ?? $channel->getIdentifier();
		}

		$default = count($channels) === 1 ? 0 : null;

		if ($connectedChannel !== null) {
			foreach (array_values(array_flip($channels)) as $index => $value) {
				if ($value === $connectedChannel->getId()->toString()) {
					$default = $index;

					break;
				}
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.questions.select.device.mappedDeviceChannel',
			),
			array_values($channels),
			$default,
		);
		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|null $answer) use ($channels): DevicesEntities\Channels\Channel {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//ns-panel-connector.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($channels))) {
					$answer = array_values($channels)[$answer];
				}

				$identifier = array_search($answer, $channels, true);

				if ($identifier !== false) {
					$channel = $this->channelsRepository->find(Uuid\Uuid::fromString($identifier));

					if ($channel !== null) {
						return $channel;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$channel = $io->askQuestion($question);
		assert($channel instanceof DevicesEntities\Channels\Channel);

		$properties = [];

		$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelDynamicProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		if ($settable === true) {
			$findChannelPropertiesQuery->settable(true);
		}

		$channelProperties = $this->channelsPropertiesRepository->findAllBy(
			$findChannelPropertiesQuery,
			DevicesEntities\Channels\Properties\Dynamic::class,
		);
		usort(
			$channelProperties,
			static fn (DevicesEntities\Channels\Properties\Dynamic $a, DevicesEntities\Channels\Properties\Dynamic $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($channelProperties as $property) {
			$properties[$property->getId()->toString()] = $property->getName() ?? $property->getIdentifier();
		}

		$default = count($properties) === 1 ? 0 : null;

		if ($connectedProperty !== null) {
			foreach (array_values(array_flip($properties)) as $index => $value) {
				if ($value === $connectedProperty->getId()->toString()) {
					$default = $index;

					break;
				}
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate(
				'//ns-panel-connector.cmd.install.questions.select.device.mappedChannelProperty',
			),
			array_values($properties),
			$default,
		);
		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|null $answer) use ($properties): DevicesEntities\Channels\Properties\Dynamic {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//ns-panel-connector.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($properties))) {
					$answer = array_values($properties)[$answer];
				}

				$identifier = array_search($answer, $properties, true);

				if ($identifier !== false) {
					$property = $this->channelsPropertiesRepository->find(
						Uuid\Uuid::fromString($identifier),
						DevicesEntities\Channels\Properties\Dynamic::class,
					);

					if ($property !== null) {
						return $property;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
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
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askFormat(
		Style\SymfonyStyle $io,
		Types\Protocol $protocol,
		DevicesEntities\Channels\Properties\Dynamic|null $connectProperty = null,
	): MetadataFormats\NumberRange|MetadataFormats\StringEnum|MetadataFormats\CombinedEnum|null
	{
		$metadata = $this->loader->loadProtocols();

		if (!$metadata->offsetExists($protocol->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for protocol: %s was not found',
				$protocol->value,
			));
		}

		$protocolMetadata = $metadata->offsetGet($protocol->value);

		if (
			!$protocolMetadata instanceof Utils\ArrayHash
			|| !$protocolMetadata->offsetExists('data_type')
			|| !is_string($protocolMetadata->offsetGet('data_type'))
			|| MetadataTypes\DataType::tryFrom($protocolMetadata->offsetGet('data_type')) === null
		) {
			throw new Exceptions\InvalidState('Protocol definition is missing required attributes');
		}

		$dataType = MetadataTypes\DataType::from($protocolMetadata->offsetGet('data_type'));

		$format = null;

		if (
			$protocolMetadata->offsetExists('min_value')
			|| $protocolMetadata->offsetExists('max_value')
		) {
			$format = new MetadataFormats\NumberRange([
				$protocolMetadata->offsetExists('min_value') ? floatval(
					$protocolMetadata->offsetGet('min_value'),
				) : null,
				$protocolMetadata->offsetExists('max_value') ? floatval(
					$protocolMetadata->offsetGet('max_value'),
				) : null,
			]);
		}

		if (
			(
				$dataType === MetadataTypes\DataType::ENUM
				|| $dataType === MetadataTypes\DataType::SWITCH
				|| $dataType === MetadataTypes\DataType::BUTTON
			)
			&& $protocolMetadata->offsetExists('valid_values')
			&& $protocolMetadata->offsetGet('valid_values') instanceof Utils\ArrayHash
		) {
			$format = new MetadataFormats\StringEnum(
				array_values((array) $protocolMetadata->offsetGet('valid_values')),
			);

			if (
				$connectProperty !== null
				&& (
					(
						(
							$connectProperty->getDataType() === MetadataTypes\DataType::ENUM
							|| $connectProperty->getDataType() === MetadataTypes\DataType::SWITCH
							|| $connectProperty->getDataType() === MetadataTypes\DataType::BUTTON
						) && (
							$connectProperty->getFormat() instanceof MetadataFormats\StringEnum
							|| $connectProperty->getFormat() instanceof MetadataFormats\CombinedEnum
						)
					)
					|| $connectProperty->getDataType() === MetadataTypes\DataType::BOOLEAN
				)
			) {
				$mappedFormat = [];

				foreach ($protocolMetadata->offsetGet('valid_values') as $name) {
					if ($connectProperty->getDataType() === MetadataTypes\DataType::BOOLEAN) {
						$options = [
							'true',
							'false',
						];
					} else {
						assert(
							$connectProperty->getFormat() instanceof MetadataFormats\StringEnum
							|| $connectProperty->getFormat() instanceof MetadataFormats\CombinedEnum,
						);

						$options = $connectProperty->getFormat() instanceof MetadataFormats\StringEnum
							? $connectProperty->getFormat()->toArray()
							: array_map(
								static function (array $items): array|null {
									if ($items[0] === null) {
										return null;
									}

									return [
										$items[0]->getDataType(),
										MetadataUtilities\Value::toString($items[0]->getValue(), true),
									];
								},
								$connectProperty->getFormat()->getItems(),
							);
					}

					$question = new Console\Question\ChoiceQuestion(
						(string) $this->translator->translate(
							'//ns-panel-connector.cmd.install.questions.select.device.valueMapping',
							['value' => $name],
						),
						array_map(
							static fn ($item): string|null => is_array($item) ? $item[1] : $item,
							$options,
						),
					);
					$question->setErrorMessage(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
					);
					$question->setValidator(function (string|null $answer) use ($options): string|array {
						if ($answer === null) {
							throw new Exceptions\Runtime(
								sprintf(
									(string) $this->translator->translate(
										'//ns-panel-connector.cmd.base.messages.answerNotValid',
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
								static fn ($item): bool => is_array($item) ? $item[1] === $answer : $item === $answer,
							));

							if (count($options) === 1 && $options[0] !== null) {
								return $options[0];
							}
						}

						throw new Exceptions\Runtime(
							sprintf(
								(string) $this->translator->translate(
									'//ns-panel-connector.cmd.base.messages.answerNotValid',
								),
								strval($answer),
							),
						);
					});

					$value = $io->askQuestion($question);
					assert(is_string($value) || is_int($value) || is_array($value));

					$valueDataType = is_array($value) ? strval($value[0]) : null;
					$value = is_array($value) ? $value[1] : $value;

					if (MetadataTypes\Payloads\Switcher::tryFrom($value) !== null) {
						$valueDataType = MetadataTypes\DataTypeShort::SWITCH->value;

					} elseif (MetadataTypes\Payloads\Button::tryFrom($value) !== null) {
						$valueDataType = MetadataTypes\DataTypeShort::BUTTON->value;

					} elseif (MetadataTypes\Payloads\Cover::tryFrom($value) !== null) {
						$valueDataType = MetadataTypes\DataTypeShort::COVER->value;
					}

					$mappedFormat[] = [
						[$valueDataType, strval($value)],
						[MetadataTypes\DataTypeShort::STRING->value, strval($name)],
						[MetadataTypes\DataTypeShort::STRING->value, strval($name)],
					];
				}

				$format = new MetadataFormats\CombinedEnum($mappedFormat);
			}
		}

		return $format;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function provideProtocolValue(
		Style\SymfonyStyle $io,
		Types\Protocol $protocol,
		bool|float|int|string|DateTimeInterface|MetadataTypes\Payloads\Payload|null $value = null,
	): string|int|bool|float
	{
		$metadata = $this->loader->loadProtocols();

		if (!$metadata->offsetExists($protocol->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for protocol: %s was not found',
				$protocol->value,
			));
		}

		$protocolMetadata = $metadata->offsetGet($protocol->value);

		if (
			!$protocolMetadata instanceof Utils\ArrayHash
			|| !$protocolMetadata->offsetExists('data_type')
			|| MetadataTypes\DataType::tryFrom(strval($protocolMetadata->offsetGet('data_type'))) === null
		) {
			throw new Exceptions\InvalidState('Protocol definition is missing required attributes');
		}

		$dataType = MetadataTypes\DataType::from(strval($protocolMetadata->offsetGet('data_type')));

		if (
			$protocolMetadata->offsetExists('valid_values')
			&& $protocolMetadata->offsetGet('valid_values') instanceof Utils\ArrayHash
		) {
			$options = array_combine(
				array_values((array) $protocolMetadata->offsetGet('valid_values')),
				array_keys((array) $protocolMetadata->offsetGet('valid_values')),
			);

			$question = new Console\Question\ChoiceQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.select.device.value'),
				$options,
				$value !== null ? array_key_exists(
					MetadataUtilities\Value::toString($value, true),
					$options,
				) : null,
			);
			$question->setErrorMessage(
				(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
			);
			$question->setValidator(function (string|int|null $answer) use ($options): string|int {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//ns-panel-connector.cmd.base.messages.answerNotValid',
							),
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
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			});

			$value = $io->askQuestion($question);
			assert(is_string($value) || is_numeric($value));

			return $value;
		}

		if ($dataType === MetadataTypes\DataType::BOOLEAN) {
			$question = new Console\Question\ChoiceQuestion(
				(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.select.value'),
				[
					(string) $this->translator->translate('//ns-panel-connector.cmd.install.answers.false'),
					(string) $this->translator->translate('//ns-panel-connector.cmd.install.answers.true'),
				],
				is_bool($value) ? ($value ? 0 : 1) : null,
			);
			$question->setErrorMessage(
				(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
			);
			$question->setValidator(function (string|int|null $answer): bool {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//ns-panel-connector.cmd.base.messages.answerNotValid',
							),
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

		$minValue = $protocolMetadata->offsetExists('min_value')
			? floatval($protocolMetadata->offsetGet('min_value'))
			: null;
		$maxValue = $protocolMetadata->offsetExists('max_value')
			? floatval($protocolMetadata->offsetGet('max_value'))
			: null;
		$step = $protocolMetadata->offsetExists('step_value')
			? floatval($protocolMetadata->offsetGet('step_value'))
			: null;

		$question = new Console\Question\Question(
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.provide.value'),
			is_object($value) ? MetadataUtilities\Value::toString($value) : $value,
		);
		$question->setValidator(
			function (string|int|null $answer) use ($dataType, $minValue, $maxValue, $step): string|int|float {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//ns-panel-connector.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if ($dataType === MetadataTypes\DataType::STRING) {
					return strval($answer);
				}

				if ($dataType === MetadataTypes\DataType::FLOAT) {
					if ($minValue !== null && floatval($answer) < $minValue) {
						throw new Exceptions\Runtime(
							sprintf(
								(string) $this->translator->translate(
									'//ns-panel-connector.cmd.base.messages.answerNotValid',
								),
								$answer,
							),
						);
					}

					if ($maxValue !== null && floatval($answer) > $maxValue) {
						throw new Exceptions\Runtime(
							sprintf(
								(string) $this->translator->translate(
									'//ns-panel-connector.cmd.base.messages.answerNotValid',
								),
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
								(string) $this->translator->translate(
									'//ns-panel-connector.cmd.base.messages.answerNotValid',
								),
								$answer,
							),
						);
					}

					return floatval($answer);
				}

				if (
					$dataType === MetadataTypes\DataType::CHAR
					|| $dataType === MetadataTypes\DataType::UCHAR
					|| $dataType === MetadataTypes\DataType::SHORT
					|| $dataType === MetadataTypes\DataType::USHORT
					|| $dataType === MetadataTypes\DataType::INT
					|| $dataType === MetadataTypes\DataType::UINT
				) {
					if ($minValue !== null && intval($answer) < $minValue) {
						throw new Exceptions\Runtime(
							sprintf(
								(string) $this->translator->translate(
									'//ns-panel-connector.cmd.base.messages.answerNotValid',
								),
								$answer,
							),
						);
					}

					if ($maxValue !== null && intval($answer) > $maxValue) {
						throw new Exceptions\Runtime(
							sprintf(
								(string) $this->translator->translate(
									'//ns-panel-connector.cmd.base.messages.answerNotValid',
								),
								$answer,
							),
						);
					}

					if ($step !== null && intval($answer) % $step !== 0) {
						throw new Exceptions\Runtime(
							sprintf(
								(string) $this->translator->translate(
									'//ns-panel-connector.cmd.base.messages.answerNotValid',
								),
								$answer,
							),
						);
					}

					return intval($answer);
				}

				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
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
	private function askWhichConnector(Style\SymfonyStyle $io): Entities\Connectors\Connector|null
	{
		$connectors = [];

		$findConnectorsQuery = new Queries\Entities\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\Connectors\Connector::class,
		);
		usort(
			$systemConnectors,
			static fn (Entities\Connectors\Connector $a, Entities\Connectors\Connector $b): int => (
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
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.select.item.connector'),
			array_values($connectors),
			count($connectors) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer) use ($connectors): Entities\Connectors\Connector {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
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
					Entities\Connectors\Connector::class,
				);

				if ($connector !== null) {
					return $connector;
				}
			}

			throw new Exceptions\Runtime(
				sprintf(
					(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$connector = $io->askQuestion($question);
		assert($connector instanceof Entities\Connectors\Connector);

		return $connector;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function askWhichPanel(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
		Entities\Devices\Gateway|null $gateway = null,
	): API\Messages\Response\GetGatewayInfo
	{
		$question = new Console\Question\Question(
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.provide.device.address'),
			$gateway?->getIpAddress(),
		);
		$question->setValidator(
			function (string|null $answer) use ($connector): API\Messages\Response\GetGatewayInfo {
				if ($answer !== null && $answer !== '') {
					$panelApi = $this->lanApiFactory->create($connector->getIdentifier());

					try {
						return $panelApi->getGatewayInfo($answer, API\LanApi::GATEWAY_PORT, false);
					} catch (Exceptions\LanApiCall $ex) {
						$this->logger->error(
							'Could not get NS Panel basic information',
							[
								'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
								'type' => 'install-cmd',
								'exception' => ApplicationHelpers\Logger::buildException($ex),
								'request' => [
									'method' => $ex->getRequest()?->getMethod(),
									'url' => $ex->getRequest() !== null ? strval($ex->getRequest()->getUri()) : null,
									'body' => $ex->getRequest()?->getBody()->getContents(),
								],
								'connector' => [
									'id' => $connector->getId()->toString(),
								],
							],
						);

						throw new Exceptions\Runtime(
							(string) $this->translator->translate(
								'//ns-panel-connector.cmd.install.messages.addressNotReachable',
								['address' => $answer],
							),
						);
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$panelInfo = $io->askQuestion($question);
		assert($panelInfo instanceof API\Messages\Response\GetGatewayInfo);

		return $panelInfo;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichGateway(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
	): Entities\Devices\Gateway|null
	{
		$gateways = [];

		$findDevicesQuery = new Queries\Entities\FindGatewayDevices();
		$findDevicesQuery->forConnector($connector);

		$connectorDevices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class);
		usort(
			$connectorDevices,
			static fn (DevicesEntities\Devices\Device $a, DevicesEntities\Devices\Device $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($connectorDevices as $gateway) {
			$gateways[$gateway->getIdentifier()] = $gateway->getName() ?? $gateway->getIdentifier();
		}

		if (count($gateways) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.select.item.gateway'),
			array_values($gateways),
			count($gateways) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connector, $gateways): Entities\Devices\Gateway {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//ns-panel-connector.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($gateways))) {
					$answer = array_values($gateways)[$answer];
				}

				$identifier = array_search($answer, $gateways, true);

				if ($identifier !== false) {
					$findDeviceQuery = new Queries\Entities\FindGatewayDevices();
					$findDeviceQuery->byIdentifier($identifier);
					$findDeviceQuery->forConnector($connector);

					$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Gateway::class);

					if ($device !== null) {
						return $device;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$gateway = $io->askQuestion($question);
		assert($gateway instanceof Entities\Devices\Gateway);

		return $gateway;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichDevice(
		Style\SymfonyStyle $io,
		Entities\Connectors\Connector $connector,
		Entities\Devices\Gateway $gateway,
		bool $onlyThirdParty = false,
	): Entities\Devices\ThirdPartyDevice|Entities\Devices\SubDevice|null
	{
		$devices = [];

		if ($onlyThirdParty) {
			$findDevicesQuery = new Queries\Entities\FindThirdPartyDevices();
			$findDevicesQuery->forConnector($connector);
			$findDevicesQuery->forParent($gateway);

			$connectorDevices = $this->devicesRepository->findAllBy(
				$findDevicesQuery,
				Entities\Devices\ThirdPartyDevice::class,
			);
		} else {
			$findDevicesQuery = new Queries\Entities\FindDevices();
			$findDevicesQuery->forConnector($connector);
			$findDevicesQuery->forParent($gateway);

			$connectorDevices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Device::class);
		}

		usort(
			$connectorDevices,
			static fn (Entities\Devices\Device $a, Entities\Devices\Device $b): int => (
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
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.select.item.device'),
			array_values($devices),
			count($devices) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connector, $gateway, $devices): Entities\Devices\ThirdPartyDevice|Entities\Devices\SubDevice {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//ns-panel-connector.cmd.base.messages.answerNotValid',
							),
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
					$findDeviceQuery->forParent($gateway);

					$device = $this->devicesRepository->findOneBy(
						$findDeviceQuery,
						Entities\Devices\Device::class,
					);

					if (
						$device instanceof Entities\Devices\ThirdPartyDevice
						|| $device instanceof Entities\Devices\SubDevice
					) {
						return $device;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\Devices\ThirdPartyDevice || $device instanceof Entities\Devices\SubDevice);

		return $device;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichCapability(
		Style\SymfonyStyle $io,
		Entities\Devices\ThirdPartyDevice $device,
	): Entities\Channels\Channel|false|null
	{
		$channels = [];

		$findChannelsQuery = new Queries\Entities\FindChannels();
		$findChannelsQuery->forDevice($device);

		$deviceChannels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\Channels\Channel::class);
		usort(
			$deviceChannels,
			static fn (Entities\Channels\Channel $a, Entities\Channels\Channel $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($deviceChannels as $channel) {
			$channels[$channel->getIdentifier()] = $channel->getName() ?? $channel->getIdentifier();
		}

		if (count($channels) === 0) {
			return null;
		}

		$channels['none'] = (string) $this->translator->translate(
			'//ns-panel-connector.cmd.install.answers.none',
		);

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.select.item.capability'),
			array_values($channels),
			count($channels) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($device, $channels): Entities\Channels\Channel|false {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//ns-panel-connector.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($channels))) {
					$answer = array_values($channels)[$answer];
				}

				$identifier = array_search($answer, $channels, true);

				if ($identifier === 'none') {
					return false;
				}

				if ($identifier !== false) {
					$findChannelQuery = new Queries\Entities\FindChannels();
					$findChannelQuery->forDevice($device);
					$findChannelQuery->byIdentifier($identifier);

					$channel = $this->channelsRepository->findOneBy(
						$findChannelQuery,
						Entities\Channels\Channel::class,
					);

					if ($channel !== null) {
						return $channel;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$channel = $io->askQuestion($question);
		assert($channel instanceof Entities\Channels\Channel || $channel === false);

		return $channel;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichProtocol(
		Style\SymfonyStyle $io,
		Entities\Channels\Channel $channel,
	): DevicesEntities\Channels\Properties\Variable|DevicesEntities\Channels\Properties\Mapped|null
	{
		$properties = [];

		$findChannelPropertiesQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		$channelProperties = $this->channelsPropertiesRepository->findAllBy($findChannelPropertiesQuery);
		usort(
			$channelProperties,
			static fn (DevicesEntities\Channels\Properties\Property $a, DevicesEntities\Channels\Properties\Property $b): int => (
				($a->getName() ?? $a->getIdentifier()) <=> ($b->getName() ?? $b->getIdentifier())
			),
		);

		foreach ($channelProperties as $property) {
			$properties[$property->getIdentifier()] = $property->getName() ?? $property->getIdentifier();
		}

		if (count($properties) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			(string) $this->translator->translate('//ns-panel-connector.cmd.install.questions.select.item.protocol'),
			array_values($properties),
			count($properties) === 1 ? 0 : null,
		);

		$question->setErrorMessage(
			(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			function (string|int|null $answer) use ($channel, $properties): DevicesEntities\Channels\Properties\Variable|DevicesEntities\Channels\Properties\Mapped {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							(string) $this->translator->translate(
								'//ns-panel-connector.cmd.base.messages.answerNotValid',
							),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($properties))) {
					$answer = array_values($properties)[$answer];
				}

				$identifier = array_search($answer, $properties, true);

				if ($identifier !== false) {
					$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
					$findChannelPropertyQuery->forChannel($channel);
					$findChannelPropertyQuery->byIdentifier($identifier);

					$property = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

					if ($property !== null) {
						assert(
							// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
							$property instanceof DevicesEntities\Channels\Properties\Variable || $property instanceof DevicesEntities\Channels\Properties\Mapped,
						);

						return $property;
					}
				}

				throw new Exceptions\Runtime(
					sprintf(
						(string) $this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$property = $io->askQuestion($question);
		assert(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			$property instanceof DevicesEntities\Channels\Properties\Variable || $property instanceof DevicesEntities\Channels\Properties\Mapped,
		);

		return $property;
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	private function findNextDeviceIdentifier(Entities\Connectors\Connector $connector, string $pattern): string
	{
		for ($i = 1; $i <= 100; $i++) {
			$identifier = sprintf($pattern, $i);

			$findDeviceQuery = new Queries\Entities\FindDevices();
			$findDeviceQuery->forConnector($connector);
			$findDeviceQuery->byIdentifier($identifier);

			$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Device::class);

			if ($device === null) {
				return $identifier;
			}
		}

		throw new Exceptions\InvalidState('Could not find free device identifier');
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function findNextChannelIdentifier(Entities\Devices\ThirdPartyDevice $device, string $type): string
	{
		for ($i = 1; $i <= 100; $i++) {
			$identifier = Helpers\Name::convertCapabilityToChannel(Types\Capability::from($type), $i);

			$findChannelQuery = new Queries\Entities\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier($identifier);

			$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Channel::class);

			if ($channel === null) {
				return $identifier;
			}
		}

		throw new Exceptions\InvalidState('Could not find free channel identifier');
	}

}
