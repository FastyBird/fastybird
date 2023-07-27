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

use Brick\Math;
use DateTimeInterface;
use Doctrine\DBAL;
use Doctrine\Persistence;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use InvalidArgumentException as InvalidArgumentExceptionAlias;
use Nette;
use Nette\Localization;
use Nette\Utils;
use Ramsey\Uuid;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;
use Throwable;
use function array_combine;
use function array_diff;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
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
 * Connector devices management command
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Devices extends Console\Command\Command
{

	public const NAME = 'fb:ns-panel-connector:devices';

	public function __construct(
		private readonly API\LanApiFactory $lanApiFactory,
		private readonly Helpers\Loader $loader,
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Devices\DevicesManager $devicesManager,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
		private readonly Persistence\ManagerRegistry $managerRegistry,
		private readonly Localization\Translator $translator,
		private readonly NsPanel\Logger $logger,
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
			->setDescription('NS Panel connector devices management');
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws InvalidArgumentExceptionAlias
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
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

		$this->askGatewayAction($io, $connector);

		$gateway = $this->askWhichGateway($io, $connector);

		if ($gateway === null) {
			$io->warning($this->translator->translate('//ns-panel-connector.cmd.devices.messages.noGateways'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.create.gateway'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$gateway = $this->createNewGateway($io, $connector);

				if ($gateway === null) {
					return Console\Command\Command::FAILURE;
				}
			} else {
				return Console\Command\Command::SUCCESS;
			}
		}

		$this->askDeviceAction($io, $connector, $gateway);

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function createNewGateway(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
		bool $editMode = false,
	): Entities\Devices\Gateway|null
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
			$identifierPattern = 'ns-panel-gw-%d';

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

			return null;
		}

		assert(is_string($identifier));

		$name = $this->askDeviceName($io);

		$panelInfo = $this->askWhichPanel($io, $identifier);

		$io->note($this->translator->translate('//ns-panel-connector.cmd.devices.messages.prepareGateway'));

		do {
			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.isGatewayReady'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if (!$continue) {
				$question = new Console\Question\ConfirmationQuestion(
					$this->translator->translate('//ns-panel-connector.cmd.base.questions.exit'),
					false,
				);

				$exit = (bool) $io->askQuestion($question);

				if ($exit) {
					return null;
				}
			}
		} while (!$continue);

		$panelApi = $this->lanApiFactory->create($identifier);

		try {
			$accessToken = $panelApi->getGatewayAccessToken(
				$connector->getName() ?? $connector->getIdentifier(),
				$panelInfo->getIpAddress(),
				API\LanApi::GATEWAY_PORT,
				false,
			);
		} catch (Exceptions\LanApiCall) {
			$io->error(
				$this->translator->translate('//ns-panel-connector.cmd.devices.messages.getAccessTokenFailed'),
			);

			return null;
		}

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
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $panelInfo->getIpAddress(),
				'device' => $device,
			]));

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_DOMAIN,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_DOMAIN),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $panelInfo->getDomain(),
				'device' => $device,
			]));

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_MAC_ADDRESS,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_MAC_ADDRESS),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $panelInfo->getMacAddress(),
				'device' => $device,
			]));

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_VERSION,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_VERSION),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $panelInfo->getFirmwareVersion(),
				'device' => $device,
			]));

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_ACCESS_TOKEN,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_ACCESS_TOKEN),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $accessToken->getData()->getAccessToken(),
				'device' => $device,
			]));

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//ns-panel-connector.cmd.devices.messages.create.gateway.success',
					['name' => $device->getName() ?? $device->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//ns-panel-connector.cmd.devices.messages.create.gateway.error'));

			return null;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		return $device;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function editExistingGateway(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
	): void
	{
		$gateway = $this->askWhichGateway($io, $connector);

		if ($gateway === null) {
			$io->warning($this->translator->translate('//ns-panel-connector.cmd.devices.messages.noGateways'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.create.gateway'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createNewGateway($io, $connector);
			}

			return;
		}

		$name = $this->askDeviceName($io, $gateway);

		$panelInfo = $this->askWhichPanel($io, $gateway->getIdentifier(), $gateway);

		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($gateway);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS);

		$ipAddressProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($gateway);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IDENTIFIER_DOMAIN);

		$domainProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($gateway);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IDENTIFIER_MAC_ADDRESS);

		$macAddressProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($gateway);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_VERSION);

		$firmwareVersionProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.regenerateAccessToken'),
			false,
		);

		$regenerate = (bool) $io->askQuestion($question);

		$accessToken = null;

		if ($regenerate) {
			$io->note($this->translator->translate('//ns-panel-connector.cmd.devices.messages.prepareGateway'));

			do {
				$question = new Console\Question\ConfirmationQuestion(
					$this->translator->translate('//ns-panel-connector.cmd.devices.questions.isGatewayReady'),
					false,
				);

				$continue = (bool) $io->askQuestion($question);

				if (!$continue) {
					$question = new Console\Question\ConfirmationQuestion(
						$this->translator->translate('//ns-panel-connector.cmd.base.questions.exit'),
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
					$panelInfo->getIpAddress(),
					API\LanApi::GATEWAY_PORT,
					false,
				);
			} catch (Exceptions\LanApiCall) {
				$io->error(
					$this->translator->translate('//ns-panel-connector.cmd.devices.messages.getAccessTokenFailed'),
				);
			}
		}

		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($gateway);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IDENTIFIER_ACCESS_TOKEN);

		$accessTokenProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$gateway = $this->devicesManager->update($gateway, Utils\ArrayHash::from([
				'name' => $name,
			]));

			if ($ipAddressProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS,
					'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_IP_ADDRESS),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $panelInfo->getIpAddress(),
					'device' => $gateway,
				]));
			} elseif ($ipAddressProperty instanceof DevicesEntities\Devices\Properties\Variable) {
				$this->devicesPropertiesManager->update($ipAddressProperty, Utils\ArrayHash::from([
					'value' => $panelInfo->getIpAddress(),
				]));
			}

			if ($domainProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_DOMAIN,
					'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_DOMAIN),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $panelInfo->getDomain(),
					'device' => $gateway,
				]));
			} elseif ($domainProperty instanceof DevicesEntities\Devices\Properties\Variable) {
				$this->devicesPropertiesManager->update($domainProperty, Utils\ArrayHash::from([
					'value' => $panelInfo->getDomain(),
				]));
			}

			if ($macAddressProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_MAC_ADDRESS,
					'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_MAC_ADDRESS),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $panelInfo->getMacAddress(),
					'device' => $gateway,
				]));
			} elseif ($macAddressProperty instanceof DevicesEntities\Devices\Properties\Variable) {
				$this->devicesPropertiesManager->update($macAddressProperty, Utils\ArrayHash::from([
					'value' => $panelInfo->getMacAddress(),
				]));
			}

			if ($firmwareVersionProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_VERSION,
					'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_FIRMWARE_VERSION),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $panelInfo->getFirmwareVersion(),
					'device' => $gateway,
				]));
			} elseif ($firmwareVersionProperty instanceof DevicesEntities\Devices\Properties\Variable) {
				$this->devicesPropertiesManager->update($firmwareVersionProperty, Utils\ArrayHash::from([
					'value' => $panelInfo->getFirmwareVersion(),
				]));
			}

			if ($accessToken !== null) {
				if ($accessTokenProperty === null) {
					$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Devices\Properties\Variable::class,
						'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_ACCESS_TOKEN,
						'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_ACCESS_TOKEN),
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
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
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//ns-panel-connector.cmd.devices.messages.update.gateway.success',
					['name' => $gateway->getName() ?? $gateway->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//ns-panel-connector.cmd.devices.messages.update.gateway.error'));
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
	private function deleteExistingGateway(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
	): void
	{
		$gateway = $this->askWhichGateway($io, $connector);

		if ($gateway === null) {
			$io->info($this->translator->translate('//ns-panel-connector.cmd.devices.messages.noGateways'));

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

			$this->devicesManager->delete($gateway);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//ns-panel-connector.cmd.devices.messages.remove.gateway.success',
					['name' => $gateway->getName() ?? $gateway->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error($this->translator->translate('//ns-panel-connector.cmd.devices.messages.remove.gateway.error'));
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function createNewDevice(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
		Entities\Devices\Gateway $gateway,
	): void
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
			$identifierPattern = 'ns-panel-device-%d';

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

		$category = $this->askCategory($io);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Devices\Device::class,
				'connector' => $connector,
				'parent' => $gateway,
				'identifier' => $identifier,
				'name' => $name,
			]));
			assert($device instanceof Entities\Devices\Device);

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_CATEGORY,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_CATEGORY),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $category->getValue(),
				'device' => $device,
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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
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

		$this->createCapability($io, $device);
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function editExistingDevice(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
		Entities\Devices\Gateway $gateway,
	): void
	{
		$device = $this->askWhichDevice($io, $connector);

		if ($device === null) {
			$io->warning($this->translator->translate('//ns-panel-connector.cmd.devices.messages.noDevices'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.create.device'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createNewDevice($io, $connector, $gateway);
			}

			return;
		}

		$name = $this->askDeviceName($io, $device);

		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IDENTIFIER_CATEGORY);

		$categoryProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		$category = $this->askCategory($io, $device);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->update($device, Utils\ArrayHash::from([
				'name' => $name,
			]));

			if ($categoryProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_CATEGORY,
					'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_CATEGORY),
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'value' => $category->getValue(),
					'device' => $device,
				]));
			} elseif ($categoryProperty instanceof DevicesEntities\Devices\Properties\Variable) {
				$this->devicesPropertiesManager->update($categoryProperty, Utils\ArrayHash::from([
					'value' => $category->getValue(),
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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
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

		assert($device instanceof Entities\Devices\Device);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.editCapabilities'),
			false,
		);

		$manage = (bool) $io->askQuestion($question);

		if (!$manage) {
			return;
		}

		$findChannelsQuery = new DevicesQueries\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\NsPanelChannel::class);

		if (count($channels) > 0) {
			$this->askCapabilityAction($io, $device);

			return;
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.createChannel'),
			false,
		);

		$create = (bool) $io->askQuestion($question);

		if ($create) {
			$this->createCapability($io, $device, true);
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function deleteExistingDevice(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
	): void
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
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
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

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function createCapability(
		Style\SymfonyStyle $io,
		Entities\Devices\Device $device,
		bool $editMode = false,
	): void
	{
		$capability = $this->askCapabilityType($io, $device);

		$metadata = $this->loader->loadCapabilities();

		if (!$metadata->offsetExists($capability->getValue())) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for capability: %s was not found',
				$capability->getValue(),
			));
		}

		$capabilityMetadata = $metadata->offsetGet($capability->getValue());

		if (
			!$capabilityMetadata instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('permission')
			|| !is_string($capabilityMetadata->offsetGet('permission'))
			|| !$capabilityMetadata->offsetExists('protocol')
			|| !$capabilityMetadata->offsetGet('protocol') instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('multiple')
			|| !is_bool($capabilityMetadata->offsetGet('multiple'))
		) {
			throw new Exceptions\InvalidState('Capability definition is missing required attributes');
		}

		$allowMultiple = $capabilityMetadata->offsetGet('multiple');

		if ($allowMultiple) {
			$identifier = $this->findNextChannelIdentifier($device, $capability->getValue());

		} else {
			$identifier = Helpers\Name::convertCapabilityToChannel($capability);

			$findChannelQuery = new DevicesQueries\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier($identifier);

			$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\NsPanelChannel::class);

			if ($channel !== null) {
				$io->error(
					$this->translator->translate(
						'//ns-panel-connector.cmd.devices.messages.noMultipleCapabilities',
						['type' => $channel->getIdentifier()],
					),
				);

				if ($editMode) {
					$this->askCapabilityAction($io, $device, $editMode);

					return;
				}

				$question = new Console\Question\ConfirmationQuestion(
					$this->translator->translate('//ns-panel-connector.cmd.devices.questions.createAnotherCapability'),
					false,
				);

				$create = (bool) $io->askQuestion($question);

				if ($create) {
					$this->createCapability($io, $device, $editMode);
				}

				return;
			}
		}

		$protocols = (array) $capabilityMetadata->offsetGet('protocol');

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			preg_match(Entities\NsPanelChannel::CAPABILITY_IDENTIFIER, $identifier, $matches);

			$channel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\NsPanelChannel::class,
				'identifier' => $identifier,
				'name' => $this->translator->translate(
					'//ns-panel-connector.cmd.base.capability.' . $capability->getValue(),
				) . (array_key_exists(
					'key',
					$matches,
				) ? ' ' . $matches['key'] : ''),
				'device' => $device,
			]));
			assert($channel instanceof Entities\NsPanelChannel);

			$this->createProtocols($io, $device, $channel, $protocols);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//ns-panel-connector.cmd.devices.messages.create.capability.success',
					['name' => $channel->getName() ?? $channel->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->error(
				$this->translator->translate('//ns-panel-connector.cmd.devices.messages.create.capability.error'),
			);

			return;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		if ($editMode) {
			$this->askCapabilityAction($io, $device, $editMode);

			return;
		}

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.createAnotherCapability'),
			false,
		);

		$create = (bool) $io->askQuestion($question);

		if ($create) {
			$this->createCapability($io, $device, $editMode);
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function editCapability(Style\SymfonyStyle $io, Entities\Devices\Device $device): void
	{
		$channels = $this->getCapabilitiesList($device);

		if (count($channels) === 0) {
			$io->warning($this->translator->translate('//ns-panel-connector.cmd.devices.messages.noCapabilities'));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.create.capability'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createCapability($io, $device, true);
			}

			return;
		}

		$channel = $this->askWhichCapability($io, $device, $channels);

		if ($channel === null) {
			return;
		}

		$type = $channel->getCapability();

		$metadata = $this->loader->loadCapabilities();

		if (!$metadata->offsetExists($type->getValue())) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for capability: %s was not found',
				$type->getValue(),
			));
		}

		$capabilityMetadata = $metadata->offsetGet($type->getValue());

		if (
			!$capabilityMetadata instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('permission')
			|| !is_string($capabilityMetadata->offsetGet('permission'))
			|| !$capabilityMetadata->offsetExists('protocol')
			|| !$capabilityMetadata->offsetGet('protocol') instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('multiple')
			|| !is_bool($capabilityMetadata->offsetGet('multiple'))
		) {
			throw new Exceptions\InvalidState('Capability definition is missing required attributes');
		}

		$protocols = (array) $capabilityMetadata->offsetGet('protocol');

		$missingRequired = [];

		foreach ($protocols as $requiredProtocol) {
			$findPropertyQuery = new DevicesQueries\FindChannelProperties();
			$findPropertyQuery->forChannel($channel);
			$findPropertyQuery->byIdentifier(
				Helpers\Name::convertProtocolToProperty(Types\Protocol::get($requiredProtocol)),
			);

			$property = $this->channelsPropertiesRepository->findOneBy($findPropertyQuery);

			if ($property === null) {
				$missingRequired[] = $requiredProtocol;
			}
		}

		try {
			if (count($missingRequired) > 0) {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				$this->createProtocols($io, $device, $channel, $missingRequired);

				// Commit all changes into database
				$this->getOrmConnection()->commit();

				$io->success(
					$this->translator->translate(
						'//ns-panel-connector.cmd.devices.messages.update.capability.success',
						['name' => $channel->getName() ?? $channel->getIdentifier()],
					),
				);
			} else {
				$io->success(
					$this->translator->translate(
						'//ns-panel-connector.cmd.devices.messages.noMissingProtocols',
						['name' => $channel->getName() ?? $channel->getIdentifier()],
					),
				);
			}
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->success(
				$this->translator->translate('//ns-panel-connector.cmd.devices.messages.update.capability.error'),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		$this->askCapabilityAction($io, $device);
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function deleteCapability(Style\SymfonyStyle $io, Entities\Devices\Device $device): void
	{
		$channels = $this->getCapabilitiesList($device);

		if (count($channels) === 0) {
			$io->warning($this->translator->translate('//ns-panel-connector.cmd.devices.messages.noCapabilities'));

			return;
		}

		$channel = $this->askWhichCapability($io, $device, $channels);

		if ($channel === null) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->channelsManager->delete($channel);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//ns-panel-connector.cmd.devices.messages.remove.capability.success',
					['name' => $channel->getName() ?? $channel->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->success(
				$this->translator->translate('//ns-panel-connector.cmd.devices.messages.remove.capability.error'),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		$findChannelsQuery = new DevicesQueries\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\NsPanelChannel::class);

		if (count($channels) > 0) {
			$this->askCapabilityAction($io, $device, true);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function listGateways(Style\SymfonyStyle $io, Entities\NsPanelConnector $connector): void
	{
		$findDevicesQuery = new DevicesQueries\FindDevices();
		$findDevicesQuery->forConnector($connector);

		/** @var array<Entities\Devices\Gateway> $devices */
		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class);
		usort(
			$devices,
			static function (Entities\Devices\Gateway $a, Entities\Devices\Gateway $b): int {
				if ($a->getIdentifier() === $b->getIdentifier()) {
					return $a->getName() <=> $b->getName();
				}

				return $a->getIdentifier() <=> $b->getIdentifier();
			},
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			'Name',
			'Devices',
		]);

		foreach ($devices as $index => $device) {
			$findDevicesQuery = new DevicesQueries\FindDevices();
			$findDevicesQuery->forParent($device);

			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
				implode(
					', ',
					array_map(
						static fn (DevicesEntities\Devices\Device $device): string => $device->getName() ?? $device->getIdentifier(),
						$this->devicesRepository->findAllBy($findDevicesQuery, Entities\NsPanelDevice::class),
					),
				),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function listDevices(Style\SymfonyStyle $io, Entities\Devices\Gateway $gateway): void
	{
		$findDevicesQuery = new DevicesQueries\FindDevices();
		$findDevicesQuery->forParent($gateway);

		/** @var array<Entities\NsPanelDevice> $devices */
		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\NsPanelDevice::class);
		usort(
			$devices,
			static function (Entities\NsPanelDevice $a, Entities\NsPanelDevice $b): int {
				if ($a->getIdentifier() === $b->getIdentifier()) {
					return $a->getName() <=> $b->getName();
				}

				return $a->getIdentifier() <=> $b->getIdentifier();
			},
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			'Name',
			'Category',
			'Type',
		]);

		foreach ($devices as $index => $device) {
			assert($device instanceof Entities\Devices\Device || $device instanceof Entities\Devices\SubDevice);

			$findChannelsQuery = new DevicesQueries\FindChannels();
			$findChannelsQuery->forDevice($device);

			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
				$device->getDisplayCategory()->getValue(),
				implode(
					', ',
					array_map(
						static function (DevicesEntities\Channels\Channel $channel): string {
							assert($channel instanceof Entities\NsPanelChannel);

							return $channel->getCapability()->getValue();
						},
						$this->channelsRepository->findAllBy($findChannelsQuery, Entities\NsPanelChannel::class),
					),
				),
			]);
		}

		$table->render();

		$io->newLine();
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function listCapabilities(Style\SymfonyStyle $io, Entities\Devices\Device $device): void
	{
		$findChannelsQuery = new DevicesQueries\FindChannels();
		$findChannelsQuery->forDevice($device);

		/** @var array<Entities\NsPanelChannel> $deviceChannels */
		$deviceChannels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\NsPanelChannel::class);
		usort(
			$deviceChannels,
			static function (Entities\NsPanelChannel $a, Entities\NsPanelChannel $b): int {
				if ($a->getIdentifier() === $b->getIdentifier()) {
					return $a->getName() <=> $b->getName();
				}

				return $a->getIdentifier() <=> $b->getIdentifier();
			},
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			'Name',
			'Type',
			'Protocols',
		]);

		foreach ($deviceChannels as $index => $channel) {
			$findChannelPropertiesQuery = new DevicesQueries\FindChannelProperties();
			$findChannelPropertiesQuery->forChannel($channel);

			$table->addRow([
				$index + 1,
				$channel->getName() ?? $channel->getIdentifier(),
				$channel->getCapability()->getValue(),
				implode(
					', ',
					array_map(
						static fn (DevicesEntities\Channels\Properties\Property $property): string => Helpers\Name::convertPropertyToProtocol(
							$property->getIdentifier(),
						)->getValue(),
						$this->channelsPropertiesRepository->findAllBy($findChannelPropertiesQuery),
					),
				),
			]);
		}

		$table->render();

		$io->newLine();

		$findChannelsQuery = new DevicesQueries\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\NsPanelChannel::class);

		if (count($channels) > 0) {
			$this->askCapabilityAction($io, $device, true);
		}
	}

	/**
	 * @param array<string> $protocols
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws Nette\IOException
	 */
	private function createProtocols(
		Style\SymfonyStyle $io,
		Entities\Devices\Device $device,
		Entities\NsPanelChannel $channel,
		array $protocols,
	): void
	{
		$metadata = $this->loader->loadProtocols();

		$createdProtocols = [];

		while (count(array_diff($protocols, $createdProtocols)) > 0) {
			$protocol = $this->askProtocol(
				$io,
				$channel->getCapability(),
				$protocols,
				$createdProtocols,
			);

			if ($protocol === null) {
				break;
			}

			$protocolMetadata = $metadata->offsetGet($protocol->getValue());

			if (
				!$protocolMetadata instanceof Utils\ArrayHash
				|| !$protocolMetadata->offsetExists('data_type')
				|| !is_string($protocolMetadata->offsetGet('data_type'))
			) {
				throw new Exceptions\InvalidState('Protocol definition is missing required attributes');
			}

			$dataType = MetadataTypes\DataType::get($protocolMetadata->offsetGet('data_type'));

			$format = $this->askFormat($io, $protocol);

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.connectProtocol'),
				true,
			);

			$connect = (bool) $io->askQuestion($question);

			if ($connect) {
				$connectProperty = $this->askProperty($io);

				$format = $this->askFormat($io, $protocol, $connectProperty);

				if ($connectProperty instanceof DevicesEntities\Devices\Properties\Dynamic) {
					$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Devices\Properties\Mapped::class,
						'parent' => $connectProperty,
						'identifier' => Helpers\Name::convertProtocolToProperty($protocol),
						'name' => $this->translator->translate(
							'//ns-panel-connector.cmd.base.protocol.' . $protocol->getValue(),
						),
						'device' => $device,
						'dataType' => $dataType,
						'format' => $format,
					]));

				} elseif ($connectProperty instanceof DevicesEntities\Channels\Properties\Dynamic) {
					$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Mapped::class,
						'parent' => $connectProperty,
						'identifier' => Helpers\Name::convertProtocolToProperty($protocol),
						'name' => $this->translator->translate(
							'//ns-panel-connector.cmd.base.protocol.' . $protocol->getValue(),
						),
						'channel' => $channel,
						'dataType' => $dataType,
						'format' => $format,
					]));
				}
			} else {
				$value = $this->provideProtocolValue($io, $protocol);

				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Helpers\Name::convertProtocolToProperty($protocol),
					'name' => $this->translator->translate(
						'//ns-panel-connector.cmd.base.protocol.' . $protocol->getValue(),
					),
					'channel' => $channel,
					'dataType' => $dataType,
					'format' => $format,
					'settable' => false,
					'queryable' => false,
					'value' => $value,
				]));
			}

			$createdProtocols[] = $protocol->getValue();
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function editProtocol(Style\SymfonyStyle $io, Entities\NsPanelChannel $channel): void
	{
		$properties = $this->getProtocolsList($channel);

		if (count($properties) === 0) {
			$io->warning($this->translator->translate('//ns-panel-connector.cmd.devices.messages.noProtocols'));

			return;
		}

		$property = $this->askWhichProtocol($io, $channel, $properties);

		if ($property === null) {
			return;
		}

		$protocol = Helpers\Name::convertPropertyToProtocol($property->getIdentifier());

		$metadata = $this->loader->loadProtocols();

		if (!$metadata->offsetExists($protocol->getValue())) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for protocol: %s was not found',
				$protocol->getValue(),
			));
		}

		$protocolMetadata = $metadata->offsetGet($protocol->getValue());

		if (
			!$protocolMetadata instanceof Utils\ArrayHash
			|| !$protocolMetadata->offsetExists('data_type')
		) {
			throw new Exceptions\InvalidState('Protocol definition is missing required attributes');
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$dataType = MetadataTypes\DataType::get($protocolMetadata->offsetGet('data_type'));

			$format = $this->askFormat($io, $protocol);

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.connectProtocol'),
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
				);

				$format = $this->askFormat($io, $protocol, $connectProperty);

				if (
					$property instanceof DevicesEntities\Channels\Properties\Mapped
					&& $connectProperty instanceof DevicesEntities\Channels\Properties\Dynamic
				) {
					$this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
						'parent' => $connectProperty,
						'format' => $format,
					]));
				} else {
					$this->channelsPropertiesManager->delete($property);

					if ($connectProperty instanceof DevicesEntities\Devices\Properties\Dynamic) {
						$property = $this->devicesPropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Devices\Properties\Mapped::class,
							'parent' => $connectProperty,
							'identifier' => $property->getIdentifier(),
							'name' => $this->translator->translate(
								'//ns-panel-connector.cmd.base.protocol.' . $protocol->getValue(),
							),
							'device' => $channel->getDevice(),
							'dataType' => $dataType,
							'format' => $format,
						]));

					} elseif ($connectProperty instanceof DevicesEntities\Channels\Properties\Dynamic) {
						$property = $this->channelsPropertiesManager->create(Utils\ArrayHash::from([
							'entity' => DevicesEntities\Channels\Properties\Mapped::class,
							'parent' => $connectProperty,
							'identifier' => $property->getIdentifier(),
							'name' => $this->translator->translate(
								'//ns-panel-connector.cmd.base.protocol.' . $protocol->getValue(),
							),
							'channel' => $channel,
							'dataType' => $dataType,
							'format' => $format,
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
					]));
				} else {
					$this->channelsPropertiesManager->delete($property);

					$property = $this->channelsPropertiesManager->create(Utils\ArrayHash::from([
						'entity' => DevicesEntities\Channels\Properties\Variable::class,
						'identifier' => $property->getIdentifier(),
						'name' => $this->translator->translate(
							'//ns-panel-connector.cmd.base.protocol.' . $protocol->getValue(),
						),
						'channel' => $channel,
						'dataType' => $dataType,
						'format' => $format,
						'settable' => false,
						'queryable' => false,
						'value' => $value,
					]));
				}
			}

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//ns-panel-connector.cmd.devices.messages.update.protocol.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->success(
				$this->translator->translate('//ns-panel-connector.cmd.devices.messages.update.protocol.error'),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		$this->askProtocolAction($io, $channel);
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function deleteProtocol(Style\SymfonyStyle $io, Entities\NsPanelChannel $channel): void
	{
		$properties = $this->getProtocolsList($channel);

		if (count($properties) === 0) {
			$io->warning($this->translator->translate('//ns-panel-connector.cmd.devices.messages.noProtocols'));

			return;
		}

		$property = $this->askWhichProtocol($io, $channel, $properties);

		if ($property === null) {
			return;
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$this->channelsPropertiesManager->delete($property);

			// Commit all changes into database
			$this->getOrmConnection()->commit();

			$io->success(
				$this->translator->translate(
					'//ns-panel-connector.cmd.devices.messages.remove.protocol.success',
					['name' => $property->getName() ?? $property->getIdentifier()],
				),
			);
		} catch (Throwable $ex) {
			// Log caught exception
			$this->logger->error(
				'An unhandled error occurred',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
				],
			);

			$io->success(
				$this->translator->translate('//ns-panel-connector.cmd.devices.messages.remove.protocol.error'),
			);
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		$findChannelPropertiesQuery = new DevicesQueries\FindChannelProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		if (count($this->channelsPropertiesRepository->findAllBy($findChannelPropertiesQuery)) > 0) {
			$this->askProtocolAction($io, $channel);
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function listProtocols(Style\SymfonyStyle $io, Entities\NsPanelChannel $channel): void
	{
		$findPropertiesQuery = new DevicesQueries\FindChannelProperties();
		$findPropertiesQuery->forChannel($channel);

		$channelProperties = $this->channelsPropertiesRepository->findAllBy($findPropertiesQuery);
		usort(
			$channelProperties,
			static function (DevicesEntities\Channels\Properties\Property $a, DevicesEntities\Channels\Properties\Property $b): int {
				if ($a->getIdentifier() === $b->getIdentifier()) {
					return $a->getName() <=> $b->getName();
				}

				return $a->getIdentifier() <=> $b->getIdentifier();
			},
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			'Name',
			'Type',
			'Value',
		]);

		$metadata = $this->loader->loadProtocols();

		foreach ($channelProperties as $index => $property) {
			$type = Helpers\Name::convertPropertyToProtocol($property->getIdentifier());

			$value = $property instanceof DevicesEntities\Channels\Properties\Variable ? $property->getValue() : 'N/A';

			if (
				$property->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)
				&& $metadata->offsetExists($type->getValue())
				&& $metadata->offsetGet($type->getValue()) instanceof Utils\ArrayHash
				&& $metadata->offsetGet($type->getValue())->offsetExists('valid_values')
				&& $metadata->offsetGet($type->getValue())->offsetGet('valid_values') instanceof Utils\ArrayHash
			) {
				$enumValue = array_search(
					intval(DevicesUtilities\ValueHelper::flattenValue($value)),
					(array) $metadata->offsetGet($type->getValue())->offsetGet('valid_values'),
					true,
				);

				if ($enumValue !== false) {
					$value = $enumValue;
				}
			}

			$table->addRow([
				$index + 1,
				$property->getName() ?? $property->getIdentifier(),
				Helpers\Name::convertPropertyToProtocol($property->getIdentifier())->getValue(),
				$value,
			]);
		}

		$table->render();

		$io->newLine();

		$findChannelPropertiesQuery = new DevicesQueries\FindChannelProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		if (count($this->channelsPropertiesRepository->findAllBy($findChannelPropertiesQuery)) > 0) {
			$this->askProtocolAction($io, $channel);
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

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function askCategory(
		Style\SymfonyStyle $io,
		Entities\Devices\Device|null $device = null,
	): Types\Category
	{
		$categories = array_combine(
			array_values(Types\Category::getValues()),
			array_map(
				fn (Types\Category $category): string => $this->translator->translate(
					'//ns-panel-connector.cmd.base.deviceType.' . $category->getValue(),
				),
				(array) Types\Category::getAvailableEnums(),
			),
		);
		$categories = array_filter(
			$categories,
			fn (string $category): bool => $category !== $this->translator->translate(
				'//ns-panel-connector.cmd.base.deviceType.' . Types\Category::UNKNOWN,
			)
		);
		asort($categories);

		$default = $device !== null ? array_search(
			$this->translator->translate(
				'//ns-panel-connector.cmd.base.deviceType.' . $device->getDisplayCategory()->getValue(),
			),
			array_values($categories),
			true,
		) : null;

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.category'),
			array_values($categories),
			$default,
		);
		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer) use ($categories): Types\Category {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($categories))) {
				$answer = array_values($categories)[$answer];
			}

			$category = array_search($answer, $categories, true);

			if ($category !== false && Types\Category::isValidValue($category)) {
				return Types\Category::get($category);
			}

			throw new Exceptions\Runtime(
				sprintf($this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'), $answer),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\Category);

		return $answer;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function askGatewayAction(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
		bool $editMode = false,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.create.gateway'),
				1 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.update.gateway'),
				2 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.remove.gateway'),
				3 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.list.gateways'),
				4 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.nothing'),
			],
			4,
		);

		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.create.gateway',
			)
			|| $whatToDo === '0'
		) {
			$this->createNewGateway($io, $connector, $editMode);

			$this->askGatewayAction($io, $connector, $editMode);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.update.gateway',
			)
			|| $whatToDo === '1'
		) {
			$this->editExistingGateway($io, $connector);

			$this->askGatewayAction($io, $connector, $editMode);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.remove.gateway',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteExistingGateway($io, $connector);

			$this->askGatewayAction($io, $connector, $editMode);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.list.gateways',
			)
			|| $whatToDo === '3'
		) {
			$this->listGateways($io, $connector);

			$this->askGatewayAction($io, $connector, $editMode);
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function askDeviceAction(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
		Entities\Devices\Gateway $gateway,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.create.device'),
				1 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.update.device'),
				2 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.remove.device'),
				3 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.list.devices'),
				4 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.nothing'),
			],
			4,
		);

		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.create.device',
			)
			|| $whatToDo === '0'
		) {
			$this->createNewDevice($io, $connector, $gateway);

			$this->askDeviceAction($io, $connector, $gateway);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.update.device',
			)
			|| $whatToDo === '1'
		) {
			$this->editExistingDevice($io, $connector, $gateway);

			$this->askDeviceAction($io, $connector, $gateway);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.remove.device',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteExistingDevice($io, $connector);

			$this->askDeviceAction($io, $connector, $gateway);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.list.devices',
			)
			|| $whatToDo === '3'
		) {
			$this->listDevices($io, $gateway);

			$this->askDeviceAction($io, $connector, $gateway);
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function askCapabilityAction(
		Style\SymfonyStyle $io,
		Entities\Devices\Device $device,
		bool $editMode = false,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.create.capability'),
				1 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.update.capability'),
				2 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.remove.capability'),
				3 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.list.capabilities'),
				4 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.nothing'),
			],
			4,
		);

		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.create.capability',
			)
			|| $whatToDo === '0'
		) {
			$this->createCapability($io, $device, $editMode);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.update.capability',
			)
			|| $whatToDo === '1'
		) {
			$this->editCapability($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.remove.capability',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteCapability($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.list.capabilities',
			)
			|| $whatToDo === '3'
		) {
			$this->listCapabilities($io, $device);
		}
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function askProtocolAction(
		Style\SymfonyStyle $io,
		Entities\NsPanelChannel $channel,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.update.protocol'),
				1 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.remove.protocol'),
				2 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.list.protocols'),
				3 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.nothing'),
			],
			3,
		);

		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);

		$whatToDo = $io->askQuestion($question);

		if (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.update.protocol',
			)
			|| $whatToDo === '0'
		) {
			$this->editProtocol($io, $channel);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.remove.protocol',
			)
			|| $whatToDo === '1'
		) {
			$this->deleteProtocol($io, $channel);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.list.protocols',
			)
			|| $whatToDo === '2'
		) {
			$this->listProtocols($io, $channel);
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function askCapabilityType(
		Style\SymfonyStyle $io,
		Entities\Devices\Device $device,
	): Types\Capability
	{
		$metadata = $this->loader->loadCapabilities();

		$capabilities = [];

		foreach ((array) $metadata as $type => $capabilityMetadata) {
			if (
				$capabilityMetadata instanceof Utils\ArrayHash
				&& $capabilityMetadata->offsetExists('multiple')
				&& is_bool($capabilityMetadata->offsetGet('multiple'))
			) {
				$allowMultiple = $capabilityMetadata->offsetGet('multiple');

				$findChannelQuery = new DevicesQueries\FindChannels();
				$findChannelQuery->forDevice($device);
				$findChannelQuery->byIdentifier(Helpers\Name::convertCapabilityToChannel(Types\Capability::get($type)));

				$channel = $this->channelsRepository->findOneBy($findChannelQuery);

				if ($channel === null || $allowMultiple) {
					$capabilities[$type] = $this->translator->translate(
						'//ns-panel-connector.cmd.base.capability.' . Types\Capability::get($type)->getValue(),
					);
				}
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.capabilityType'),
			array_values($capabilities),
			0,
		);

		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($capabilities): Types\Capability {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($capabilities))) {
				$answer = array_values($capabilities)[$answer];
			}

			$capability = array_search($answer, $capabilities, true);

			if ($capability !== false && Types\Capability::isValidValue($capability)) {
				return Types\Capability::get($capability);
			}

			throw new Exceptions\Runtime(
				sprintf($this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'), $answer),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\Capability);

		return $answer;
	}

	/**
	 * @param array<string> $protocols
	 * @param array<string> $ignore
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function askProtocol(
		Style\SymfonyStyle $io,
		Types\Capability $capability,
		array $protocols = [],
		array $ignore = [],
	): Types\Protocol|null
	{
		$metadata = $this->loader->loadCapabilities();

		if (!$metadata->offsetExists($capability->getValue())) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for capability: %s was not found',
				$capability->getValue(),
			));
		}

		$protocols = array_combine(
			array_values(array_diff($protocols, $ignore)),
			array_map(
				fn (string $protocol): string => $this->translator->translate(
					'//ns-panel-connector.cmd.base.protocol.' . $protocol,
				),
				array_values(array_diff($protocols, $ignore)),
			),
		);

		if ($protocols === []) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.requiredProtocol'),
			array_values($protocols),
			0,
		);

		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($protocols): Types\Protocol {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (array_key_exists($answer, array_values($protocols))) {
				$answer = array_values($protocols)[$answer];
			}

			$protocol = array_search($answer, $protocols, true);

			if ($protocol !== false && Types\Protocol::isValidValue($protocol)) {
				return Types\Protocol::get($protocol);
			}

			throw new Exceptions\Runtime(
				sprintf($this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'), $answer),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\Protocol);

		return $answer;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askProperty(
		Style\SymfonyStyle $io,
		DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Channels\Properties\Dynamic|null $connectedProperty = null,
	): DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Channels\Properties\Dynamic|null
	{
		$devices = [];

		$connectedDevice = null;
		$connectedChannel = null;

		if ($connectedProperty instanceof DevicesEntities\Devices\Properties\Dynamic) {
			$connectedDevice = $connectedProperty->getDevice();

		} elseif ($connectedProperty instanceof DevicesEntities\Channels\Properties\Dynamic) {
			$connectedChannel = $connectedProperty->getChannel();
			$connectedDevice = $connectedProperty->getChannel()->getDevice();
		}

		$findDevicesQuery = new DevicesQueries\FindDevices();

		$systemDevices = $this->devicesRepository->findAllBy($findDevicesQuery);
		usort(
			$systemDevices,
			static fn (DevicesEntities\Devices\Device $a, DevicesEntities\Devices\Device $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($systemDevices as $device) {
			if ($device instanceof Entities\Devices\Device) {
				continue;
			}

			$devices[$device->getPlainId()] = $device->getIdentifier()
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				. ($device->getConnector()->getName() !== null ? ' [' . $device->getConnector()->getName() . ']' : '[' . $device->getConnector()->getIdentifier() . ']')
				. ($device->getName() !== null ? ' [' . $device->getName() . ']' : '');
		}

		if (count($devices) === 0) {
			$io->warning($this->translator->translate('//ns-panel-connector.cmd.devices.messages.noHardwareDevices'));

			return null;
		}

		$default = count($devices) === 1 ? 0 : null;

		if ($connectedDevice !== null) {
			foreach (array_values($devices) as $index => $value) {
				if (Utils\Strings::contains($value, $connectedDevice->getIdentifier())) {
					$default = $index;

					break;
				}
			}
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.mappedDevice'),
			array_values($devices),
			$default,
		);
		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|null $answer) use ($devices): DevicesEntities\Devices\Device {
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
				$findDeviceQuery->byId(Uuid\Uuid::fromString($identifier));

				$device = $this->devicesRepository->findOneBy($findDeviceQuery);

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
		assert($device instanceof DevicesEntities\Devices\Device);

		$default = 1;

		if ($connectedProperty !== null) {
			$default = $connectedProperty instanceof DevicesEntities\Devices\Properties\Dynamic ? 0 : 1;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.propertyType'),
			[
				$this->translator->translate('//ns-panel-connector.cmd.devices.answers.deviceProperty'),
				$this->translator->translate('//ns-panel-connector.cmd.devices.answers.channelProperty'),
			],
			$default,
		);
		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(function (string|int|null $answer): int {
			if ($answer === null) {
				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			}

			if (
				$answer === $this->translator->translate(
					'//ns-panel-connector.cmd.devices.answers.deviceProperty',
				)
				|| strval($answer) === '0'
			) {
				return 0;
			}

			if (
				$answer === $this->translator->translate(
					'//ns-panel-connector.cmd.devices.answers.channelProperty',
				)
				|| strval($answer) === '1'
			) {
				return 1;
			}

			throw new Exceptions\Runtime(
				sprintf(
					$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
					$answer,
				),
			);
		});

		$type = $io->askQuestion($question);
		assert(is_int($type));

		if ($type === 0) {
			$properties = [];

			$findDevicePropertiesQuery = new DevicesQueries\FindDeviceProperties();
			$findDevicePropertiesQuery->forDevice($device);

			$deviceProperties = $this->devicesPropertiesRepository->findAllBy(
				$findDevicePropertiesQuery,
				DevicesEntities\Devices\Properties\Dynamic::class,
			);
			usort(
				$deviceProperties,
				static function (DevicesEntities\Devices\Properties\Property $a, DevicesEntities\Devices\Properties\Property $b): int {
					if ($a->getIdentifier() === $b->getIdentifier()) {
						return $a->getName() <=> $b->getName();
					}

					return $a->getIdentifier() <=> $b->getIdentifier();
				},
			);

			foreach ($deviceProperties as $property) {
				if (!$property instanceof DevicesEntities\Devices\Properties\Dynamic) {
					continue;
				}

				$properties[$property->getIdentifier()] = sprintf(
					'%s%s',
					$property->getIdentifier(),
					($property->getName() !== null ? ' [' . $property->getName() . ']' : ''),
				);
			}

			$default = count($properties) === 1 ? 0 : null;

			if ($connectedProperty !== null) {
				foreach (array_values($properties) as $index => $value) {
					if (Utils\Strings::contains($value, $connectedProperty->getIdentifier())) {
						$default = $index;

						break;
					}
				}
			}

			$question = new Console\Question\ChoiceQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.mappedDeviceProperty'),
				array_values($properties),
				$default,
			);
			$question->setErrorMessage(
				$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
			);
			$question->setValidator(
				function (string|null $answer) use ($device, $properties): DevicesEntities\Devices\Properties\Dynamic {
					if ($answer === null) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
								$answer,
							),
						);
					}

					if (array_key_exists($answer, array_values($properties))) {
						$answer = array_values($properties)[$answer];
					}

					$identifier = array_search($answer, $properties, true);

					if ($identifier !== false) {
						$findPropertyQuery = new DevicesQueries\FindDeviceProperties();
						$findPropertyQuery->byIdentifier($identifier);
						$findPropertyQuery->forDevice($device);

						$property = $this->devicesPropertiesRepository->findOneBy(
							$findPropertyQuery,
							DevicesEntities\Devices\Properties\Dynamic::class,
						);

						if ($property !== null) {
							assert($property instanceof DevicesEntities\Devices\Properties\Dynamic);

							return $property;
						}
					}

					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				},
			);

			$property = $io->askQuestion($question);
			assert($property instanceof DevicesEntities\Devices\Properties\Dynamic);

			return $property;
		} else {
			$channels = [];

			$findChannelsQuery = new DevicesQueries\FindChannels();
			$findChannelsQuery->forDevice($device);
			$findChannelsQuery->withProperties();

			$deviceChannels = $this->channelsRepository->findAllBy($findChannelsQuery);
			usort(
				$deviceChannels,
				static function (DevicesEntities\Channels\Channel $a, DevicesEntities\Channels\Channel $b): int {
					if ($a->getIdentifier() === $b->getIdentifier()) {
						return $a->getName() <=> $b->getName();
					}

					return $a->getIdentifier() <=> $b->getIdentifier();
				},
			);

			foreach ($deviceChannels as $channel) {
				$channels[$channel->getIdentifier()] = sprintf(
					'%s%s',
					$channel->getIdentifier(),
					($channel->getName() !== null ? ' [' . $channel->getName() . ']' : ''),
				);
			}

			$default = count($channels) === 1 ? 0 : null;

			if ($connectedChannel !== null) {
				foreach (array_values($channels) as $index => $value) {
					if (Utils\Strings::contains($value, $connectedChannel->getIdentifier())) {
						$default = $index;

						break;
					}
				}
			}

			$question = new Console\Question\ChoiceQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.mappedDeviceChannel'),
				array_values($channels),
				$default,
			);
			$question->setErrorMessage(
				$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
			);
			$question->setValidator(
				function (string|null $answer) use ($device, $channels): DevicesEntities\Channels\Channel {
					if ($answer === null) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
								$answer,
							),
						);
					}

					if (array_key_exists($answer, array_values($channels))) {
						$answer = array_values($channels)[$answer];
					}

					$identifier = array_search($answer, $channels, true);

					if ($identifier !== false) {
						$findChannelQuery = new DevicesQueries\FindChannels();
						$findChannelQuery->byIdentifier($identifier);
						$findChannelQuery->forDevice($device);

						$channel = $this->channelsRepository->findOneBy($findChannelQuery);

						if ($channel !== null) {
							return $channel;
						}
					}

					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				},
			);

			$channel = $io->askQuestion($question);
			assert($channel instanceof DevicesEntities\Channels\Channel);

			$properties = [];

			$findDevicePropertiesQuery = new DevicesQueries\FindChannelProperties();
			$findDevicePropertiesQuery->forChannel($channel);

			$channelProperties = $this->channelsPropertiesRepository->findAllBy(
				$findDevicePropertiesQuery,
				DevicesEntities\Channels\Properties\Dynamic::class,
			);
			usort(
				$channelProperties,
				static function (DevicesEntities\Channels\Properties\Property $a, DevicesEntities\Channels\Properties\Property $b): int {
					if ($a->getIdentifier() === $b->getIdentifier()) {
						return $a->getName() <=> $b->getName();
					}

					return $a->getIdentifier() <=> $b->getIdentifier();
				},
			);

			foreach ($channelProperties as $property) {
				if (!$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
					continue;
				}

				$properties[$property->getIdentifier()] = sprintf(
					'%s%s',
					$property->getIdentifier(),
					($property->getName() !== null ? ' [' . $property->getName() . ']' : ''),
				);
			}

			$default = count($properties) === 1 ? 0 : null;

			if ($connectedProperty !== null) {
				foreach (array_values($properties) as $index => $value) {
					if (Utils\Strings::contains($value, $connectedProperty->getIdentifier())) {
						$default = $index;

						break;
					}
				}
			}

			$question = new Console\Question\ChoiceQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.mappedChannelProperty'),
				array_values($properties),
				$default,
			);
			$question->setErrorMessage(
				$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
			);
			$question->setValidator(
				function (string|null $answer) use ($channel, $properties): DevicesEntities\Channels\Properties\Dynamic {
					if ($answer === null) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
								$answer,
							),
						);
					}

					if (array_key_exists($answer, array_values($properties))) {
						$answer = array_values($properties)[$answer];
					}

					$identifier = array_search($answer, $properties, true);

					if ($identifier !== false) {
						$findPropertyQuery = new DevicesQueries\FindChannelProperties();
						$findPropertyQuery->byIdentifier($identifier);
						$findPropertyQuery->forChannel($channel);

						$property = $this->channelsPropertiesRepository->findOneBy(
							$findPropertyQuery,
							DevicesEntities\Channels\Properties\Dynamic::class,
						);

						if ($property !== null) {
							assert($property instanceof DevicesEntities\Channels\Properties\Dynamic);

							return $property;
						}
					}

					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				},
			);

			$property = $io->askQuestion($question);
			assert($property instanceof DevicesEntities\Channels\Properties\Dynamic);

			return $property;
		}
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws Nette\IOException
	 */
	private function askFormat(
		Style\SymfonyStyle $io,
		Types\Protocol $protocol,
		DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Channels\Properties\Dynamic|null $connectProperty = null,
	): MetadataValueObjects\NumberRangeFormat|MetadataValueObjects\StringEnumFormat|MetadataValueObjects\CombinedEnumFormat|null
	{
		$metadata = $this->loader->loadProtocols();

		if (!$metadata->offsetExists($protocol->getValue())) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for protocol: %s was not found',
				$protocol->getValue(),
			));
		}

		$protocolMetadata = $metadata->offsetGet($protocol->getValue());

		if (
			!$protocolMetadata instanceof Utils\ArrayHash
			|| !$protocolMetadata->offsetExists('data_type')
			|| !is_string($protocolMetadata->offsetGet('data_type'))
		) {
			throw new Exceptions\InvalidState('Protocol definition is missing required attributes');
		}

		$dataType = MetadataTypes\DataType::get($protocolMetadata->offsetGet('data_type'));

		$format = null;

		if (
			$protocolMetadata->offsetExists('min_value')
			|| $protocolMetadata->offsetExists('max_value')
		) {
			$format = new MetadataValueObjects\NumberRangeFormat([
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
				$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)
				|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
				|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
			)
			&& $protocolMetadata->offsetExists('valid_values')
			&& $protocolMetadata->offsetGet('valid_values') instanceof Utils\ArrayHash
		) {
			$format = new MetadataValueObjects\StringEnumFormat(
				array_values((array) $protocolMetadata->offsetGet('valid_values')),
			);

			if (
				$connectProperty !== null
				&& (
					$connectProperty->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)
					|| $connectProperty->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
					|| $connectProperty->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
				) && (
					$connectProperty->getFormat() instanceof MetadataValueObjects\StringEnumFormat
					|| $connectProperty->getFormat() instanceof MetadataValueObjects\CombinedEnumFormat
				)
			) {
				$mappedFormat = [];

				foreach ($protocolMetadata->offsetGet('valid_values') as $name) {
					$options = $connectProperty->getFormat() instanceof MetadataValueObjects\StringEnumFormat
						? $connectProperty->getFormat()->toArray()
						: array_map(
							static function (array $items): array|null {
								if ($items[0] === null) {
									return null;
								}

								return [
									$items[0]->getDataType(),
									strval($items[0]->getValue()),
								];
							},
							$connectProperty->getFormat()->getItems(),
						);

					$question = new Console\Question\ChoiceQuestion(
						$this->translator->translate(
							'//ns-panel-connector.cmd.devices.questions.select.valueMapping',
							['value' => $name],
						),
						array_map(
							static fn ($item): string|null => is_array($item) ? $item[1] : $item,
							$options,
						),
					);
					$question->setErrorMessage(
						$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
					);
					$question->setValidator(function (string|null $answer) use ($options): string|array {
						if ($answer === null) {
							throw new Exceptions\Runtime(
								sprintf(
									$this->translator->translate(
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
								static fn ($item): bool => is_array($item) ? $item[1] === $answer : $item === $answer
							));

							if (count($options) === 1 && $options[0] !== null) {
								return $options[0];
							}
						}

						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
								strval($answer),
							),
						);
					});

					$value = $io->askQuestion($question);
					assert(is_string($value) || is_int($value) || is_array($value));

					$valueDataType = is_array($value) ? strval($value[0]) : null;
					$value = is_array($value) ? $value[1] : $value;

					if (MetadataTypes\SwitchPayload::isValidValue($value)) {
						$valueDataType = MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH;

					} elseif (MetadataTypes\ButtonPayload::isValidValue($value)) {
						$valueDataType = MetadataTypes\DataTypeShort::DATA_TYPE_BUTTON;

					} elseif (MetadataTypes\CoverPayload::isValidValue($value)) {
						$valueDataType = MetadataTypes\DataTypeShort::DATA_TYPE_COVER;
					}

					$mappedFormat[] = [
						[$valueDataType, strval($value)],
						[MetadataTypes\DataTypeShort::DATA_TYPE_UCHAR, strval($name)],
						[MetadataTypes\DataTypeShort::DATA_TYPE_UCHAR, strval($name)],
					];
				}

				$format = new MetadataValueObjects\CombinedEnumFormat($mappedFormat);
			}
		}

		return $format;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function provideProtocolValue(
		Style\SymfonyStyle $io,
		Types\Protocol $protocol,
		bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null $value = null,
	): string|int|bool|float
	{
		$metadata = $this->loader->loadProtocols();

		if (!$metadata->offsetExists($protocol->getValue())) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for protocol: %s was not found',
				$protocol->getValue(),
			));
		}

		$protocolMetadata = $metadata->offsetGet($protocol->getValue());

		if (
			!$protocolMetadata instanceof Utils\ArrayHash
			|| !$protocolMetadata->offsetExists('data_type')
			|| !MetadataTypes\DataType::isValidValue($protocolMetadata->offsetGet('data_type'))
		) {
			throw new Exceptions\InvalidState('Protocol definition is missing required attributes');
		}

		$dataType = MetadataTypes\DataType::get($protocolMetadata->offsetGet('data_type'));

		if (
			$protocolMetadata->offsetExists('valid_values')
			&& $protocolMetadata->offsetGet('valid_values') instanceof Utils\ArrayHash
		) {
			$options = array_combine(
				array_values((array) $protocolMetadata->offsetGet('valid_values')),
				array_keys((array) $protocolMetadata->offsetGet('valid_values')),
			);

			$question = new Console\Question\ChoiceQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.value'),
				$options,
				$value !== null ? array_key_exists(
					strval(DevicesUtilities\ValueHelper::flattenValue($value)),
					$options,
				) : null,
			);
			$question->setErrorMessage(
				$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
			);
			$question->setValidator(function (string|int|null $answer) use ($options): string|int {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
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
						$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			});

			$value = $io->askQuestion($question);
			assert(is_string($value) || is_numeric($value));

			return $value;
		}

		if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
			$question = new Console\Question\ChoiceQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.value'),
				[
					$this->translator->translate('//ns-panel-connector.cmd.devices.answers.false'),
					$this->translator->translate('//ns-panel-connector.cmd.devices.answers.true'),
				],
				is_bool($value) ? ($value ? 0 : 1) : null,
			);
			$question->setErrorMessage(
				$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
			);
			$question->setValidator(function (string|int|null $answer): bool {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
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
			? floatval(
				$protocolMetadata->offsetGet('min_value'),
			)
			: null;
		$maxValue = $protocolMetadata->offsetExists('max_value')
			? floatval(
				$protocolMetadata->offsetGet('max_value'),
			)
			: null;
		$step = $protocolMetadata->offsetExists('step_value')
			? floatval(
				$protocolMetadata->offsetGet('step_value'),
			)
			: null;

		$question = new Console\Question\Question(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.provide.value'),
			is_object($value) ? strval($value) : $value,
		);
		$question->setValidator(
			function (string|int|null $answer) use ($dataType, $minValue, $maxValue, $step): string|int|float {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_STRING)) {
					return strval($answer);
				}

				if ($dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_FLOAT)) {
					if ($minValue !== null && floatval($answer) < $minValue) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
								$answer,
							),
						);
					}

					if ($maxValue !== null && floatval($answer) > $maxValue) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
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
								$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
								$answer,
							),
						);
					}

					return floatval($answer);
				}

				if (
					$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_CHAR)
					|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UCHAR)
					|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SHORT)
					|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_USHORT)
					|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_INT)
					|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_UINT)
				) {
					if ($minValue !== null && intval($answer) < $minValue) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
								$answer,
							),
						);
					}

					if ($maxValue !== null && intval($answer) > $maxValue) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
								$answer,
							),
						);
					}

					if ($step !== null && intval($answer) % $step !== 0) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//homekit-connector.cmd.base.messages.answerNotValid'),
								$answer,
							),
						);
					}

					return intval($answer);
				}

				throw new Exceptions\Runtime(
					sprintf(
						$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
						$answer,
					),
				);
			},
		);

		$value = $io->askQuestion($question);
		assert(is_string($value) || is_int($value) || is_float($value));

		return $value;
	}

	private function askWhichPanel(
		Style\SymfonyStyle $io,
		string $identifier,
		Entities\Devices\Gateway|null $gateway = null,
	): Entities\Commands\GatewayInfo
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.provide.address'),
			$gateway?->getName(),
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
	private function askWhichGateway(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
	): Entities\Devices\Gateway|null
	{
		$gateways = [];

		$findDevicesQuery = new DevicesQueries\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$connectorDevices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class);
		usort(
			$connectorDevices,
			static fn (DevicesEntities\Devices\Device $a, DevicesEntities\Devices\Device $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($connectorDevices as $gateway) {
			assert($gateway instanceof Entities\Devices\Gateway);

			$gateways[$gateway->getIdentifier()] = $gateway->getIdentifier()
				. ($gateway->getName() !== null ? ' [' . $gateway->getName() . ']' : '');
		}

		if (count($gateways) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.gateway'),
			array_values($gateways),
			count($gateways) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($connector, $gateways): Entities\Devices\Gateway {
				if ($answer === null) {
					throw new Exceptions\Runtime(
						sprintf(
							$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
							$answer,
						),
					);
				}

				if (array_key_exists($answer, array_values($gateways))) {
					$answer = array_values($gateways)[$answer];
				}

				$identifier = array_search($answer, $gateways, true);

				if ($identifier !== false) {
					$findDeviceQuery = new DevicesQueries\FindDevices();
					$findDeviceQuery->byIdentifier($identifier);
					$findDeviceQuery->forConnector($connector);

					$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Gateway::class);
					assert($device instanceof Entities\Devices\Gateway || $device === null);

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
		Entities\NsPanelConnector $connector,
	): Entities\Devices\Device|null
	{
		$devices = [];

		$findDevicesQuery = new DevicesQueries\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$connectorDevices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Device::class);
		usort(
			$connectorDevices,
			static fn (DevicesEntities\Devices\Device $a, DevicesEntities\Devices\Device $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($connectorDevices as $device) {
			assert($device instanceof Entities\Devices\Device);

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
		$question->setValidator(
			function (string|int|null $answer) use ($connector, $devices): Entities\Devices\Device {
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

					$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Device::class);
					assert($device instanceof Entities\Devices\Device || $device === null);

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
			},
		);

		$device = $io->askQuestion($question);
		assert($device instanceof Entities\Devices\Device);

		return $device;
	}

	/**
	 * @param array<string, string> $channels
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichCapability(
		Style\SymfonyStyle $io,
		Entities\Devices\Device $device,
		array $channels,
	): Entities\NsPanelChannel|null
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.capability'),
			array_values($channels),
			count($channels) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);

		$capabilityIdentifier = array_search($io->askQuestion($question), $channels, true);

		if ($capabilityIdentifier === false) {
			$io->error($this->translator->translate('//ns-panel-connector.cmd.devices.messages.capabilityNotFound'));

			$this->logger->alert(
				'Could not read capability identifier from console answer',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'devices-cmd',
				],
			);

			return null;
		}

		$findChannelQuery = new DevicesQueries\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier($capabilityIdentifier);

		$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\NsPanelChannel::class);

		if ($channel === null) {
			$io->error($this->translator->translate('//ns-panel-connector.cmd.devices.messages.capabilityNotFound'));

			$this->logger->alert(
				'Channel was not found',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'devices-cmd',
				],
			);

			return null;
		}

		assert($channel instanceof Entities\NsPanelChannel);

		return $channel;
	}

	/**
	 * @param array<string, string> $properties
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichProtocol(
		Style\SymfonyStyle $io,
		Entities\NsPanelChannel $channel,
		array $properties,
	): DevicesEntities\Channels\Properties\Variable|DevicesEntities\Channels\Properties\Mapped|null
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.protocol'),
			array_values($properties),
		);
		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);

		$protocolIdentifier = array_search($io->askQuestion($question), $properties, true);

		if ($protocolIdentifier === false) {
			$io->error($this->translator->translate('//ns-panel-connector.cmd.devices.messages.protocolNotFound'));

			$this->logger->alert(
				'Could not read protocol identifier from console answer',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'devices-cmd',
				],
			);

			return null;
		}

		$findPropertyQuery = new DevicesQueries\FindChannelProperties();
		$findPropertyQuery->forChannel($channel);
		$findPropertyQuery->byIdentifier($protocolIdentifier);

		$property = $this->channelsPropertiesRepository->findOneBy($findPropertyQuery);

		if ($property === null) {
			$io->error($this->translator->translate('//ns-panel-connector.cmd.devices.messages.protocolNotFound'));

			$this->logger->alert(
				'Property was not found',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'devices-cmd',
				],
			);

			return null;
		}

		assert(
			$property instanceof DevicesEntities\Channels\Properties\Variable || $property instanceof DevicesEntities\Channels\Properties\Mapped,
		);

		return $property;
	}

	/**
	 * @return array<string, string>
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	private function getCapabilitiesList(Entities\Devices\Device $device): array
	{
		$channels = [];

		$findChannelsQuery = new DevicesQueries\FindChannels();
		$findChannelsQuery->forDevice($device);

		$deviceChannels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\NsPanelChannel::class);
		usort(
			$deviceChannels,
			static function (DevicesEntities\Channels\Channel $a, DevicesEntities\Channels\Channel $b): int {
				if ($a->getIdentifier() === $b->getIdentifier()) {
					return $a->getName() <=> $b->getName();
				}

				return $a->getIdentifier() <=> $b->getIdentifier();
			},
		);

		foreach ($deviceChannels as $channel) {
			$channels[$channel->getIdentifier()] = sprintf(
				'%s%s',
				$channel->getIdentifier(),
				($channel->getName() !== null ? ' [' . $channel->getName() . ']' : ''),
			);
		}

		return $channels;
	}

	/**
	 * @return array<string, string>
	 *
	 * @throws DevicesExceptions\InvalidState
	 */
	private function getProtocolsList(Entities\NsPanelChannel $channel): array
	{
		$properties = [];

		$findPropertiesQuery = new DevicesQueries\FindChannelProperties();
		$findPropertiesQuery->forChannel($channel);

		$channelProperties = $this->channelsPropertiesRepository->findAllBy($findPropertiesQuery);
		usort(
			$channelProperties,
			static function (DevicesEntities\Channels\Properties\Property $a, DevicesEntities\Channels\Properties\Property $b): int {
				if ($a->getIdentifier() === $b->getIdentifier()) {
					return $a->getName() <=> $b->getName();
				}

				return $a->getIdentifier() <=> $b->getIdentifier();
			},
		);

		foreach ($channelProperties as $property) {
			$properties[$property->getIdentifier()] = sprintf(
				'%s%s',
				$property->getIdentifier(),
				($property->getName() !== null ? ' [' . $property->getName() . ']' : ''),
			);
		}

		return $properties;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	private function findNextChannelIdentifier(Entities\Devices\Device $device, string $type): string
	{
		for ($i = 1; $i <= 100; $i++) {
			$identifier = Helpers\Name::convertCapabilityToChannel(Types\Capability::get($type), $i);

			$findChannelQuery = new DevicesQueries\FindChannels();
			$findChannelQuery->forDevice($device);
			$findChannelQuery->byIdentifier($identifier);

			$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\NsPanelChannel::class);

			if ($channel === null) {
				return $identifier;
			}
		}

		throw new Exceptions\InvalidState('Could not find free channel identifier');
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
