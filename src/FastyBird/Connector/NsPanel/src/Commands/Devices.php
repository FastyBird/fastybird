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
use FastyBird\Connector\NsPanel\Queries;
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
use function array_combine;
use function array_filter;
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
		private readonly Helpers\Entity $entityHelper,
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
	 * @throws RuntimeException
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

		return Console\Command\Command::SUCCESS;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws RuntimeException
	 */
	private function createGateway(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
	): Entities\Devices\Gateway|null
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.provide.identifier'),
		);

		$question->setValidator(function (string|null $answer) {
			if ($answer !== '' && $answer !== null) {
				$findDeviceQuery = new Queries\FindDevices();
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

			$identifier = $this->findNextDeviceIdentifier($connector, $identifierPattern);
		}

		if ($identifier === '') {
			$io->error($this->translator->translate('//ns-panel-connector.cmd.devices.messages.identifier.missing'));

			return null;
		}

		assert(is_string($identifier));

		$name = $this->askDeviceName($io);

		$panelInfo = $this->askWhichPanel($io, $connector);

		$io->note($this->translator->translate('//ns-panel-connector.cmd.devices.messages.gateway.prepare'));

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
		} catch (Exceptions\LanApiCall $ex) {
			$this->logger->error(
				'Could not get NS Panel access token',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'devices-cmd',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'body' => $ex->getRequest()?->getBody()->getContents(),
					],
					'connector' => [
						'id' => $connector->getId()->toString(),
					],
				],
			);

			$io->error(
				$this->translator->translate('//ns-panel-connector.cmd.devices.messages.accessToken.error'),
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
				'identifier' => Types\DevicePropertyIdentifier::IP_ADDRESS,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IP_ADDRESS),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $panelInfo->getIpAddress(),
				'device' => $device,
			]));

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::DOMAIN,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::DOMAIN),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $panelInfo->getDomain(),
				'device' => $device,
			]));

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::MAC_ADDRESS,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::MAC_ADDRESS),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $panelInfo->getMacAddress(),
				'device' => $device,
			]));

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::FIRMWARE_VERSION,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::FIRMWARE_VERSION),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
				'value' => $panelInfo->getFirmwareVersion(),
				'device' => $device,
			]));

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::ACCESS_TOKEN,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::ACCESS_TOKEN),
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
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws RuntimeException
	 */
	private function editGateway(
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
				$this->createGateway($io, $connector);
			}

			return;
		}

		$name = $this->askDeviceName($io, $gateway);

		$panelInfo = $this->askWhichPanel($io, $connector, $gateway);

		/** @var DevicesQueries\FindDeviceProperties<DevicesEntities\Devices\Properties\Variable> $findDevicePropertyQuery */
		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($gateway);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IP_ADDRESS);

		$ipAddressProperty = $this->devicesPropertiesRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		/** @var DevicesQueries\FindDeviceProperties<DevicesEntities\Devices\Properties\Variable> $findDevicePropertyQuery */
		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($gateway);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::DOMAIN);

		$domainProperty = $this->devicesPropertiesRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		/** @var DevicesQueries\FindDeviceProperties<DevicesEntities\Devices\Properties\Variable> $findDevicePropertyQuery */
		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($gateway);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::MAC_ADDRESS);

		$macAddressProperty = $this->devicesPropertiesRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		/** @var DevicesQueries\FindDeviceProperties<DevicesEntities\Devices\Properties\Variable> $findDevicePropertyQuery */
		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($gateway);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::FIRMWARE_VERSION);

		$firmwareVersionProperty = $this->devicesPropertiesRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.regenerateAccessToken'),
			false,
		);

		$regenerate = (bool) $io->askQuestion($question);

		$accessToken = null;

		if ($regenerate) {
			$io->note($this->translator->translate('//ns-panel-connector.cmd.devices.messages.gateway.prepare'));

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
			} catch (Exceptions\LanApiCall $ex) {
				$this->logger->error(
					'Could not get NS Panel access token',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
						'type' => 'devices-cmd',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'request' => [
							'body' => $ex->getRequest()?->getBody()->getContents(),
						],
						'connector' => [
							'id' => $connector->getId()->toString(),
						],
					],
				);

				$io->error(
					$this->translator->translate('//ns-panel-connector.cmd.devices.messages.accessToken.error'),
				);
			}
		}

		/** @var DevicesQueries\FindDeviceProperties<DevicesEntities\Devices\Properties\Variable> $findDevicePropertyQuery */
		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($gateway);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::ACCESS_TOKEN);

		$accessTokenProperty = $this->devicesPropertiesRepository->findOneBy(
			$findDevicePropertyQuery,
			DevicesEntities\Devices\Properties\Variable::class,
		);

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			$gateway = $this->devicesManager->update($gateway, Utils\ArrayHash::from([
				'name' => $name,
			]));

			if ($ipAddressProperty === null) {
				$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Devices\Properties\Variable::class,
					'identifier' => Types\DevicePropertyIdentifier::IP_ADDRESS,
					'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IP_ADDRESS),
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
					'identifier' => Types\DevicePropertyIdentifier::DOMAIN,
					'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::DOMAIN),
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
					'identifier' => Types\DevicePropertyIdentifier::MAC_ADDRESS,
					'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::MAC_ADDRESS),
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
					'identifier' => Types\DevicePropertyIdentifier::FIRMWARE_VERSION,
					'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::FIRMWARE_VERSION),
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
						'identifier' => Types\DevicePropertyIdentifier::ACCESS_TOKEN,
						'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::ACCESS_TOKEN),
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
	private function deleteGateway(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
	): void
	{
		$gateway = $this->askWhichGateway($io, $connector);

		if ($gateway === null) {
			$io->warning($this->translator->translate('//ns-panel-connector.cmd.devices.messages.noGateways'));

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
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function listGateways(Style\SymfonyStyle $io, Entities\NsPanelConnector $connector): void
	{
		$findDevicesQuery = new Queries\FindGatewayDevices();
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
			$this->translator->translate('//ns-panel-connector.cmd.devices.data.name'),
			$this->translator->translate('//ns-panel-connector.cmd.devices.data.ipAddress'),
			$this->translator->translate('//ns-panel-connector.cmd.devices.data.devices'),
		]);

		foreach ($devices as $index => $device) {
			/** @var DevicesQueries\FindDeviceProperties<DevicesEntities\Devices\Properties\Variable> $findDevicePropertyQuery */
			$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
			$findDevicePropertyQuery->forDevice($device);
			$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IP_ADDRESS);

			$ipAddressProperty = $this->devicesPropertiesRepository->findOneBy(
				$findDevicePropertyQuery,
				DevicesEntities\Devices\Properties\Variable::class,
			);

			$findDevicesQuery = new Queries\FindDevices();
			$findDevicesQuery->forParent($device);

			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
				$ipAddressProperty?->getValue(),
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
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws RuntimeException
	 */
	private function manageGateway(
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
				$gateway = $this->createGateway($io, $connector);

				if ($gateway === null) {
					return;
				}
			} else {
				return;
			}
		}

		$this->askDeviceAction($io, $connector, $gateway);
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
	private function createDevice(
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
				$findDeviceQuery = new Queries\FindDevices();
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

			$identifier = $this->findNextDeviceIdentifier($connector, $identifierPattern);
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
				'entity' => Entities\Devices\ThirdPartyDevice::class,
				'connector' => $connector,
				'parent' => $gateway,
				'identifier' => $identifier,
				'name' => $name,
			]));
			assert($device instanceof Entities\Devices\ThirdPartyDevice);

			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'entity' => DevicesEntities\Devices\Properties\Variable::class,
				'identifier' => Types\DevicePropertyIdentifier::CATEGORY,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::CATEGORY),
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

		do {
			$channel = $this->createCapability($io, $device);

		} while ($channel !== null);
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
	private function editDevice(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
		Entities\Devices\Gateway $gateway,
	): void
	{
		$device = $this->askWhichDevice($io, $connector, $gateway);

		if ($device === null) {
			$io->warning($this->translator->translate(
				'//ns-panel-connector.cmd.devices.messages.noDevices',
				['name' => $gateway->getName() ?? $gateway->getIdentifier()],
			));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.create.device'),
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
			$this->getOrmConnection()->beginTransaction();

			$device = $this->devicesManager->update($device, Utils\ArrayHash::from([
				'name' => $name,
			]));

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
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function deleteDevice(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
		Entities\Devices\Gateway $gateway,
	): void
	{
		$device = $this->askWhichDevice($io, $connector, $gateway);

		if ($device === null) {
			$io->warning($this->translator->translate(
				'//ns-panel-connector.cmd.devices.messages.noDevices',
				['name' => $gateway->getName() ?? $gateway->getIdentifier()],
			));

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

		if (
			$device->getGatewayIdentifier() !== null
			&& $gateway->getIpAddress() !== null
			&& $gateway->getAccessToken() !== null
		) {
			$panelApi = $this->lanApiFactory->create($connector->getIdentifier());

			try {
				$result = $panelApi->removeDevice(
					$device->getGatewayIdentifier(),
					$gateway->getIpAddress(),
					$gateway->getAccessToken(),
					API\LanApi::GATEWAY_PORT,
					false,
				);

				if ($result !== true) {
					$io->error($this->translator->translate(
						'//ns-panel-connector.cmd.devices.messages.removeDeviceFromPanelFailed',
						[
							'name' => $device->getName() ?? $device->getIdentifier(),
							'panel' => $gateway->getName() ?? $gateway->getIdentifier(),
						],
					));
				}
			} catch (Throwable $ex) {
				// Log caught exception
				$this->logger->error(
					'Calling NS Panel api failed',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
						'type' => 'devices-cmd',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
					],
				);

				$io->error($this->translator->translate(
					'//ns-panel-connector.cmd.devices.messages.removeDeviceFromPanelFailed',
					[
						'name' => $device->getName() ?? $device->getIdentifier(),
						'panel' => $gateway->getName() ?? $gateway->getIdentifier(),
					],
				));
			}
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function listDevices(Style\SymfonyStyle $io, Entities\Devices\Gateway $gateway): void
	{
		$findDevicesQuery = new Queries\FindThirdPartyDevices();
		$findDevicesQuery->forParent($gateway);

		/** @var array<Entities\NsPanelDevice> $devices */
		$devices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\ThirdPartyDevice::class);
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
			$this->translator->translate('//ns-panel-connector.cmd.devices.data.name'),
			$this->translator->translate('//ns-panel-connector.cmd.devices.data.category'),
			$this->translator->translate('//ns-panel-connector.cmd.devices.data.capabilities'),
		]);

		foreach ($devices as $index => $device) {
			assert(
				$device instanceof Entities\Devices\ThirdPartyDevice || $device instanceof Entities\Devices\SubDevice,
			);

			$findChannelsQuery = new Queries\FindChannels();
			$findChannelsQuery->forDevice($device);

			$table->addRow([
				$index + 1,
				$device->getName() ?? $device->getIdentifier(),
				$device->getDisplayCategory()->getValue(),
				implode(
					', ',
					array_map(
						static fn (Entities\NsPanelChannel $channel): string => $channel->getCapability()->getValue(),
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
	private function manageDevice(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
		Entities\Devices\Gateway $gateway,
	): void
	{
		$device = $this->askWhichDevice($io, $connector, $gateway);

		if ($device === null) {
			$io->warning($this->translator->translate(
				'//ns-panel-connector.cmd.devices.messages.noDevices',
				['name' => $gateway->getName() ?? $gateway->getIdentifier()],
			));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.create.device'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createDevice($io, $connector, $gateway);
			}

			return;
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
	private function manageCapability(
		Style\SymfonyStyle $io,
		Entities\Devices\ThirdPartyDevice $device,
	): void
	{
		$capability = $this->askWhichCapability($io, $device);

		if ($capability === null) {
			$io->warning($this->translator->translate(
				'//ns-panel-connector.cmd.devices.messages.noCapabilities',
				['name' => $device->getName() ?? $device->getIdentifier()],
			));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.create.capability'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createCapability($io, $device);
			}

			return;
		}

		$this->askProtocolAction($io, $capability);
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
		Entities\Devices\ThirdPartyDevice $device,
	): Entities\NsPanelChannel|null
	{
		$capability = $this->askCapabilityType($io, $device);

		if ($capability === null) {
			return null;
		}

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
			|| !Types\Permission::isValidValue($capabilityMetadata->offsetGet('permission'))
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

			$findChannelQuery = new Queries\FindChannels();
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

				return null;
			}
		}

		try {
			// Start transaction connection to the database
			$this->getOrmConnection()->beginTransaction();

			preg_match(NsPanel\Constants::CHANNEL_IDENTIFIER, $identifier, $matches);

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

			do {
				$property = $this->createProtocol($io, $device, $channel);
			} while ($property !== null);

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

			return null;
		} finally {
			// Revert all changes when error occur
			if ($this->getOrmConnection()->isTransactionActive()) {
				$this->getOrmConnection()->rollBack();
			}
		}

		return $channel;
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
	private function editCapability(Style\SymfonyStyle $io, Entities\Devices\ThirdPartyDevice $device): void
	{
		$channel = $this->askWhichCapability($io, $device);

		if ($channel === null) {
			$io->warning($this->translator->translate(
				'//ns-panel-connector.cmd.devices.messages.noCapabilities',
				['name' => $device->getName() ?? $device->getIdentifier()],
			));

			$question = new Console\Question\ConfirmationQuestion(
				$this->translator->translate('//ns-panel-connector.cmd.devices.questions.create.capability'),
				false,
			);

			$continue = (bool) $io->askQuestion($question);

			if ($continue) {
				$this->createCapability($io, $device);
			}

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
			|| !Types\Permission::isValidValue($capabilityMetadata->offsetGet('permission'))
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
			$findPropertyQuery = new DevicesQueries\FindChannelVariableProperties();
			$findPropertyQuery->forChannel($channel);
			$findPropertyQuery->byIdentifier(
				Helpers\Name::convertProtocolToProperty(Types\Protocol::get($protocol)),
			);

			$property = $this->channelsPropertiesRepository->findOneBy(
				$findPropertyQuery,
				DevicesEntities\Channels\Properties\Variable::class,
			);

			if ($property === null) {
				$missingProtocols[] = $protocol;
			}
		}

		try {
			if (count($missingProtocols) > 0) {
				// Start transaction connection to the database
				$this->getOrmConnection()->beginTransaction();

				do {
					$property = $this->createProtocol($io, $device, $channel);
				} while ($property !== null);

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
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function deleteCapability(Style\SymfonyStyle $io, Entities\Devices\ThirdPartyDevice $device): void
	{
		$channel = $this->askWhichCapability($io, $device);

		if ($channel === null) {
			$io->warning($this->translator->translate(
				'//ns-panel-connector.cmd.devices.messages.noCapabilities',
				['name' => $device->getName() ?? $device->getIdentifier()],
			));

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
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	private function listCapabilities(Style\SymfonyStyle $io, Entities\Devices\ThirdPartyDevice $device): void
	{
		$findChannelsQuery = new Queries\FindChannels();
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
			$this->translator->translate('//ns-panel-connector.cmd.devices.data.name'),
			$this->translator->translate('//ns-panel-connector.cmd.devices.data.type'),
			$this->translator->translate('//ns-panel-connector.cmd.devices.data.protocols'),
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
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws Nette\IOException
	 * @throws DoctrineCrudExceptions\InvalidArgumentException
	 */
	private function createProtocol(
		Style\SymfonyStyle $io,
		Entities\Devices\ThirdPartyDevice $device,
		Entities\NsPanelChannel $channel,
	): DevicesEntities\Channels\Properties\Property|null
	{
		$protocol = $this->askProtocolType($io, $channel);

		if ($protocol === null) {
			return null;
		}

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

		$format = $this->askFormat($io, $protocol);

		$question = new Console\Question\ConfirmationQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.connectProtocol'),
			true,
		);

		$connect = (bool) $io->askQuestion($question);

		if ($connect) {
			$connectProperty = $this->askPropertyForConnect($io);

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
					'name' => $this->translator->translate(
						'//ns-panel-connector.cmd.base.protocol.' . $protocol->getValue(),
					),
					'channel' => $channel,
					'dataType' => $dataType,
					'format' => $format,
					'invalid' => $protocolMetadata->offsetExists('invalid_value')
						? $protocolMetadata->offsetGet('invalid_value')
						: null,
				]));
			}
		} else {
			$value = $this->provideProtocolValue($io, $protocol);

			return $this->channelsPropertiesManager->create(Utils\ArrayHash::from([
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
				'invalid' => $protocolMetadata->offsetExists('invalid_value')
					? $protocolMetadata->offsetGet('invalid_value')
					: null,
			]));
		}

		return null;
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
		$property = $this->askWhichProtocol($io, $channel);

		if ($property === null) {
			$io->warning($this->translator->translate('//ns-panel-connector.cmd.devices.messages.noProtocols'));

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
				$connectProperty = $this->askPropertyForConnect(
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
							'name' => $this->translator->translate(
								'//ns-panel-connector.cmd.base.protocol.' . $protocol->getValue(),
							),
							'channel' => $channel,
							'dataType' => $dataType,
							'format' => $format,
							'invalid' => $protocolMetadata->offsetExists('invalid_value')
								? $protocolMetadata->offsetGet('invalid_value')
								: null,
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
						'name' => $this->translator->translate(
							'//ns-panel-connector.cmd.base.protocol.' . $protocol->getValue(),
						),
						'channel' => $channel,
						'dataType' => $dataType,
						'format' => $format,
						'settable' => false,
						'queryable' => false,
						'value' => $value,
						'invalid' => $protocolMetadata->offsetExists('invalid_value')
							? $protocolMetadata->offsetGet('invalid_value')
							: null,
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
		$property = $this->askWhichProtocol($io, $channel);

		if ($property === null) {
			$io->warning($this->translator->translate('//ns-panel-connector.cmd.devices.messages.noProtocols'));

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
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
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
			static function (DevicesEntities\Property $a, DevicesEntities\Property $b): int {
				if ($a->getIdentifier() === $b->getIdentifier()) {
					return $a->getName() <=> $b->getName();
				}

				return $a->getIdentifier() <=> $b->getIdentifier();
			},
		);

		$table = new Console\Helper\Table($io);
		$table->setHeaders([
			'#',
			$this->translator->translate('//ns-panel-connector.cmd.devices.data.name'),
			$this->translator->translate('//ns-panel-connector.cmd.devices.data.type'),
			$this->translator->translate('//ns-panel-connector.cmd.devices.data.value'),
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
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function askCategory(
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
					$categories[$type] = $this->translator->translate(
						'//ns-panel-connector.cmd.base.deviceType.' . $type,
					);
				}
			}
		}

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
	 * @throws RuntimeException
	 */
	private function askGatewayAction(
		Style\SymfonyStyle $io,
		Entities\NsPanelConnector $connector,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.create.gateway'),
				1 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.update.gateway'),
				2 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.remove.gateway'),
				3 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.list.gateways'),
				4 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.manage.gateway'),
				5 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.nothing'),
			],
			5,
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
			$this->createGateway($io, $connector);

			$this->askGatewayAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.update.gateway',
			)
			|| $whatToDo === '1'
		) {
			$this->editGateway($io, $connector);

			$this->askGatewayAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.remove.gateway',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteGateway($io, $connector);

			$this->askGatewayAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.list.gateways',
			)
			|| $whatToDo === '3'
		) {
			$this->listGateways($io, $connector);

			$this->askGatewayAction($io, $connector);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.manage.gateway',
			)
			|| $whatToDo === '4'
		) {
			$this->manageGateway($io, $connector);

			$this->askGatewayAction($io, $connector);
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
				4 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.manage.device'),
				5 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.nothing'),
			],
			5,
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
			$this->createDevice($io, $connector, $gateway);

			$this->askDeviceAction($io, $connector, $gateway);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.update.device',
			)
			|| $whatToDo === '1'
		) {
			$this->editDevice($io, $connector, $gateway);

			$this->askDeviceAction($io, $connector, $gateway);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.remove.device',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteDevice($io, $connector, $gateway);

			$this->askDeviceAction($io, $connector, $gateway);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.list.devices',
			)
			|| $whatToDo === '3'
		) {
			$this->listDevices($io, $gateway);

			$this->askDeviceAction($io, $connector, $gateway);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.manage.device',
			)
			|| $whatToDo === '4'
		) {
			$this->manageDevice($io, $connector, $gateway);

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
		Entities\Devices\ThirdPartyDevice $device,
	): void
	{
		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.base.questions.whatToDo'),
			[
				0 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.create.capability'),
				1 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.update.capability'),
				2 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.remove.capability'),
				3 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.list.capabilities'),
				4 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.manage.capability'),
				5 => $this->translator->translate('//ns-panel-connector.cmd.devices.actions.nothing'),
			],
			5,
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
			$this->createCapability($io, $device);

			$this->askCapabilityAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.update.capability',
			)
			|| $whatToDo === '1'
		) {
			$this->editCapability($io, $device);

			$this->askCapabilityAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.remove.capability',
			)
			|| $whatToDo === '2'
		) {
			$this->deleteCapability($io, $device);

			$this->askCapabilityAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.list.capabilities',
			)
			|| $whatToDo === '3'
		) {
			$this->listCapabilities($io, $device);

			$this->askCapabilityAction($io, $device);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.manage.capability',
			)
			|| $whatToDo === '4'
		) {
			$this->manageCapability($io, $device);

			$this->askCapabilityAction($io, $device);
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

			$this->askProtocolAction($io, $channel);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.remove.protocol',
			)
			|| $whatToDo === '1'
		) {
			$this->deleteProtocol($io, $channel);

			$this->askProtocolAction($io, $channel);

		} elseif (
			$whatToDo === $this->translator->translate(
				'//ns-panel-connector.cmd.devices.actions.list.protocols',
			)
			|| $whatToDo === '2'
		) {
			$this->listProtocols($io, $channel);

			$this->askProtocolAction($io, $channel);
		}
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function askCapabilityType(
		Style\SymfonyStyle $io,
		Entities\Devices\ThirdPartyDevice $device,
	): Types\Capability|null
	{
		$metadata = $this->loader->loadCategories();

		if (!array_key_exists($device->getDisplayCategory()->getValue(), (array) $metadata)) {
			return null;
		}

		$categoryMetadata = $metadata[$device->getDisplayCategory()->getValue()];

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

					$findChannelQuery = new Queries\FindChannels();
					$findChannelQuery->forDevice($device);
					$findChannelQuery->byIdentifier(
						Helpers\Name::convertCapabilityToChannel(Types\Capability::get($type)),
					);

					$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\NsPanelChannel::class);

					if ($channel === null || $allowMultiple) {
						$capabilities[$type] = $this->translator->translate(
							'//ns-panel-connector.cmd.base.capability.' . Types\Capability::get($type)->getValue(),
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

						$findChannelQuery = new Queries\FindChannels();
						$findChannelQuery->forDevice($device);
						$findChannelQuery->byIdentifier(
							Helpers\Name::convertCapabilityToChannel(Types\Capability::get($type)),
						);

						$channel = $this->channelsRepository->findOneBy(
							$findChannelQuery,
							Entities\NsPanelChannel::class,
						);

						if ($channel === null || $allowMultiple) {
							$capabilities[$type] = $this->translator->translate(
								'//ns-panel-connector.cmd.base.capability.' . Types\Capability::get($type)->getValue(),
							);
						}
					}
				}

				if ($capabilities !== []) {
					$capabilities['none'] = $this->translator->translate(
						'//ns-panel-connector.cmd.devices.answers.none',
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
		$question->setValidator(function (string|null $answer) use ($capabilities): Types\Capability|null {
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

			if ($capability === 'none') {
				return null;
			}

			if ($capability !== false && Types\Capability::isValidValue($capability)) {
				return Types\Capability::get($capability);
			}

			throw new Exceptions\Runtime(
				sprintf($this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'), $answer),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\Capability || $answer === null);

		return $answer;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function askProtocolType(
		Style\SymfonyStyle $io,
		Entities\NsPanelChannel $channel,
	): Types\Protocol|null
	{
		$metadata = $this->loader->loadCapabilities();

		if (!$metadata->offsetExists($channel->getCapability()->getValue())) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for capability: %s was not found',
				$channel->getCapability()->getValue(),
			));
		}

		$capabilityMetadata = $metadata->offsetGet($channel->getCapability()->getValue());

		if (
			!$capabilityMetadata instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('permission')
			|| !is_string($capabilityMetadata->offsetGet('permission'))
			|| !Types\Permission::isValidValue($capabilityMetadata->offsetGet('permission'))
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
				$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
				$findChannelPropertyQuery->forChannel($channel);
				$findChannelPropertyQuery->byIdentifier(
					Helpers\Name::convertProtocolToProperty(Types\Protocol::get($type)),
				);

				$property = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

				if ($property === null) {
					$protocols[$type] = $this->translator->translate(
						'//ns-panel-connector.cmd.base.protocol.' . Types\Protocol::get($type)->getValue(),
					);
				}
			}
		}

		if ($protocols === []) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.protocolType'),
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
				sprintf($this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'), $answer),
			);
		});

		$answer = $io->askQuestion($question);
		assert($answer instanceof Types\Protocol);

		return $answer;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askPropertyForConnect(
		Style\SymfonyStyle $io,
		DevicesEntities\Channels\Properties\Dynamic|null $connectedProperty = null,
	): DevicesEntities\Channels\Properties\Dynamic|null
	{
		$devices = [];

		$connectedChannel = $connectedProperty?->getChannel();
		$connectedDevice = $connectedProperty?->getChannel()->getDevice();

		$findDevicesQuery = new DevicesQueries\FindDevices();

		$systemDevices = $this->devicesRepository->findAllBy($findDevicesQuery);
		usort(
			$systemDevices,
			static fn (DevicesEntities\Devices\Device $a, DevicesEntities\Devices\Device $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($systemDevices as $device) {
			if ($device instanceof Entities\Devices\ThirdPartyDevice) {
				continue;
			}

			$devices[$device->getId()->toString()] = $device->getIdentifier()
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

		$findDevicePropertiesQuery = new DevicesQueries\FindChannelDynamicProperties();
		$findDevicePropertiesQuery->forChannel($channel);

		$channelProperties = $this->channelsPropertiesRepository->findAllBy(
			$findDevicePropertiesQuery,
			DevicesEntities\Channels\Properties\Dynamic::class,
		);
		usort(
			$channelProperties,
			static function (DevicesEntities\Channels\Properties\Dynamic $a, DevicesEntities\Channels\Properties\Dynamic $b): int {
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
					$findPropertyQuery = new DevicesQueries\FindChannelDynamicProperties();
					$findPropertyQuery->byIdentifier($identifier);
					$findPropertyQuery->forChannel($channel);

					$property = $this->channelsPropertiesRepository->findOneBy(
						$findPropertyQuery,
						DevicesEntities\Channels\Properties\Dynamic::class,
					);

					if ($property !== null) {
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

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws Nette\IOException
	 */
	private function askFormat(
		Style\SymfonyStyle $io,
		Types\Protocol $protocol,
		DevicesEntities\Channels\Properties\Dynamic|null $connectProperty = null,
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
					(
						(
							$connectProperty->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)
							|| $connectProperty->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
							|| $connectProperty->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
						) && (
							$connectProperty->getFormat() instanceof MetadataValueObjects\StringEnumFormat
							|| $connectProperty->getFormat() instanceof MetadataValueObjects\CombinedEnumFormat
						)
					)
					|| $connectProperty->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)
				)
			) {
				$mappedFormat = [];

				foreach ($protocolMetadata->offsetGet('valid_values') as $name) {
					if ($connectProperty->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)) {
						$options = [
							'true',
							'false',
						];
					} else {
						assert(
							$connectProperty->getFormat() instanceof MetadataValueObjects\StringEnumFormat
							|| $connectProperty->getFormat() instanceof MetadataValueObjects\CombinedEnumFormat,
						);

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
					}

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
						[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, strval($name)],
						[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, strval($name)],
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
			? floatval($protocolMetadata->offsetGet('min_value'))
			: null;
		$maxValue = $protocolMetadata->offsetExists('max_value')
			? floatval($protocolMetadata->offsetGet('max_value'))
			: null;
		$step = $protocolMetadata->offsetExists('step_value')
			? floatval($protocolMetadata->offsetGet('step_value'))
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
								$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
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
								$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
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
		Entities\NsPanelConnector $connector,
		Entities\Devices\Gateway|null $gateway = null,
	): Entities\Commands\GatewayInfo
	{
		$question = new Console\Question\Question(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.provide.address'),
			$gateway?->getName(),
		);
		$question->setValidator(
			function (string|null $answer) use ($connector): Entities\Commands\GatewayInfo {
				if ($answer !== null && $answer !== '') {
					$panelApi = $this->lanApiFactory->create($connector->getIdentifier());

					try {
						$panelInfo = $panelApi->getGatewayInfo($answer, API\LanApi::GATEWAY_PORT, false);
					} catch (Exceptions\LanApiCall $ex) {
						$this->logger->error(
							'Could not get NS Panel basic information',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
								'type' => 'devices-cmd',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'request' => [
									'body' => $ex->getRequest()?->getBody()->getContents(),
								],
								'connector' => [
									'id' => $connector->getId()->toString(),
								],
							],
						);

						throw new Exceptions\Runtime(
							$this->translator->translate(
								'//ns-panel-connector.cmd.devices.messages.addressNotReachable',
								['address' => $answer],
							),
						);
					}

					try {
						return $this->entityHelper->create(
							Entities\Commands\GatewayInfo::class,
							$panelInfo->getData()->toArray(),
						);
					} catch (Exceptions\Runtime) {
						throw new Exceptions\Runtime(
							sprintf(
								$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
								$answer,
							),
						);
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

		$findConnectorsQuery = new Queries\FindConnectors();

		$systemConnectors = $this->connectorsRepository->findAllBy(
			$findConnectorsQuery,
			Entities\NsPanelConnector::class,
		);
		usort(
			$systemConnectors,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			static fn (Entities\NsPanelConnector $a, Entities\NsPanelConnector $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($systemConnectors as $connector) {
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
				$findConnectorQuery = new Queries\FindConnectors();
				$findConnectorQuery->byIdentifier($identifier);

				$connector = $this->connectorsRepository->findOneBy(
					$findConnectorQuery,
					Entities\NsPanelConnector::class,
				);

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

		$findDevicesQuery = new Queries\FindGatewayDevices();
		$findDevicesQuery->forConnector($connector);

		$connectorDevices = $this->devicesRepository->findAllBy($findDevicesQuery, Entities\Devices\Gateway::class);
		usort(
			$connectorDevices,
			static fn (DevicesEntities\Devices\Device $a, DevicesEntities\Devices\Device $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($connectorDevices as $gateway) {
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
					$findDeviceQuery = new Queries\FindGatewayDevices();
					$findDeviceQuery->byIdentifier($identifier);
					$findDeviceQuery->forConnector($connector);

					$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\Gateway::class);

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
		Entities\Devices\Gateway $gateway,
	): Entities\Devices\ThirdPartyDevice|null
	{
		$devices = [];

		$findDevicesQuery = new Queries\FindThirdPartyDevices();
		$findDevicesQuery->forConnector($connector);
		$findDevicesQuery->forParent($gateway);

		$connectorDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\Devices\ThirdPartyDevice::class,
		);
		usort(
			$connectorDevices,
			static fn (DevicesEntities\Devices\Device $a, DevicesEntities\Devices\Device $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($connectorDevices as $device) {
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
			function (string|int|null $answer) use ($connector, $gateway, $devices): Entities\Devices\ThirdPartyDevice {
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
					$findDeviceQuery = new Queries\FindThirdPartyDevices();
					$findDeviceQuery->byIdentifier($identifier);
					$findDeviceQuery->forConnector($connector);
					$findDeviceQuery->forParent($gateway);

					$device = $this->devicesRepository->findOneBy(
						$findDeviceQuery,
						Entities\Devices\ThirdPartyDevice::class,
					);

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
		assert($device instanceof Entities\Devices\ThirdPartyDevice);

		return $device;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichCapability(
		Style\SymfonyStyle $io,
		Entities\Devices\ThirdPartyDevice $device,
	): Entities\NsPanelChannel|null
	{
		$channels = [];

		$findChannelsQuery = new Queries\FindChannels();
		$findChannelsQuery->forDevice($device);

		$deviceChannels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\NsPanelChannel::class);
		usort(
			$deviceChannels,
			static fn (Entities\NsPanelChannel $a, Entities\NsPanelChannel $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($deviceChannels as $channel) {
			$channels[$channel->getIdentifier()] = $channel->getIdentifier()
				. ($channel->getName() !== null ? ' [' . $channel->getName() . ']' : '');
		}

		if (count($channels) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.capability'),
			array_values($channels),
			count($channels) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			function (string|int|null $answer) use ($device, $channels): Entities\NsPanelChannel {
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
					$findChannelQuery = new Queries\FindChannels();
					$findChannelQuery->forDevice($device);
					$findChannelQuery->byIdentifier($identifier);

					$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\NsPanelChannel::class);

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
		assert($channel instanceof Entities\NsPanelChannel);

		return $channel;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function askWhichProtocol(
		Style\SymfonyStyle $io,
		Entities\NsPanelChannel $channel,
	): DevicesEntities\Channels\Properties\Variable|DevicesEntities\Channels\Properties\Mapped|null
	{
		$properties = [];

		$findChannelPropertiesQuery = new DevicesQueries\FindChannelProperties();
		$findChannelPropertiesQuery->forChannel($channel);

		$channelProperties = $this->channelsPropertiesRepository->findAllBy($findChannelPropertiesQuery);
		usort(
			$channelProperties,
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			static fn (DevicesEntities\Channels\Properties\Property $a, DevicesEntities\Channels\Properties\Property $b): int => $a->getIdentifier() <=> $b->getIdentifier()
		);

		foreach ($channelProperties as $property) {
			$properties[$property->getIdentifier()] = $property->getIdentifier()
				. ($property->getName() !== null ? ' [' . $property->getName() . ']' : '');
		}

		if (count($properties) === 0) {
			return null;
		}

		$question = new Console\Question\ChoiceQuestion(
			$this->translator->translate('//ns-panel-connector.cmd.devices.questions.select.protocol'),
			array_values($properties),
			count($properties) === 1 ? 0 : null,
		);
		$question->setErrorMessage(
			$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
		);
		$question->setValidator(
			// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			function (string|int|null $answer) use ($channel, $properties): DevicesEntities\Channels\Properties\Variable|DevicesEntities\Channels\Properties\Mapped {
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
					$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
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
						$this->translator->translate('//ns-panel-connector.cmd.base.messages.answerNotValid'),
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
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	private function findNextDeviceIdentifier(Entities\NsPanelConnector $connector, string $pattern): string
	{
		for ($i = 1; $i <= 100; $i++) {
			$identifier = sprintf($pattern, $i);

			$findDeviceQuery = new Queries\FindDevices();
			$findDeviceQuery->forConnector($connector);
			$findDeviceQuery->byIdentifier($identifier);

			$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\NsPanelDevice::class);

			if ($device === null) {
				return $identifier;
			}
		}

		throw new Exceptions\InvalidState('Could not find free device identifier');
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	private function findNextChannelIdentifier(Entities\Devices\ThirdPartyDevice $device, string $type): string
	{
		for ($i = 1; $i <= 100; $i++) {
			$identifier = Helpers\Name::convertCapabilityToChannel(Types\Capability::get($type), $i);

			$findChannelQuery = new Queries\FindChannels();
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
