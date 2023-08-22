<?php declare(strict_types = 1);

/**
 * Discovery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\Clients;

use Clue\React\Multicast;
use Evenement;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Consumers;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Storages;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\Datagram;
use React\Dns;
use React\EventLoop;
use SplObjectStorage;
use Throwable;
use function array_key_exists;
use function array_map;
use function array_merge;
use function assert;
use function count;
use function explode;
use function is_array;
use function is_string;
use function preg_match;
use function strval;

/**
 * Devices discovery client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Discovery implements Evenement\EventEmitterInterface
{

	use Nette\SmartObject;
	use Evenement\EventEmitterTrait;

	private const MDNS_ADDRESS = '224.0.0.251';

	private const MDNS_PORT = 5_353;

	private const MDNS_SEARCH_TIMEOUT = 30;

	private const MATCH_NAME = '/^(?P<type>shelly.+)-(?P<id>[0-9A-Fa-f]+)._(http|shelly)._tcp.local$/';

	private const MATCH_DOMAIN = '/^(?P<type>[0-9A-Za-z]+)-(?P<id>[0-9A-Fa-f]+).local$/';
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	private const MATCH_IP_ADDRESS = '/^((?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])[.]){3}(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/';

	/** @var SplObjectStorage<Entities\Clients\DiscoveredLocalDevice, null> */
	private SplObjectStorage $discoveredLocalDevices;

	private Storages\MdnsResultStorage $searchResult;

	private Dns\Protocol\Parser $parser;

	private Dns\Protocol\BinaryDumper $dumper;

	private Datagram\SocketInterface|null $server = null;

	public function __construct(
		private readonly Entities\ShellyConnector $connector,
		private readonly API\Gen1HttpApiFactory $gen1HttpApiFactory,
		private readonly API\Gen2HttpApiFactory $gen2HttpApiFactory,
		private readonly Consumers\Messages $consumer,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
		$this->searchResult = new Storages\MdnsResultStorage();

		$this->parser = new Dns\Protocol\Parser();
		$this->dumper = new Dns\Protocol\BinaryDumper();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function discover(): void
	{
		$this->discoveredLocalDevices = new SplObjectStorage();

		$mode = $this->connector->getClientMode();

		if ($mode->equalsValue(Types\ClientMode::CLOUD)) {
			$this->discoverCloudDevices();

		} elseif ($mode->equalsValue(Types\ClientMode::LOCAL)) {
			$this->discoverLocalDevices();
		}
	}

	public function disconnect(): void
	{
		$this->server?->close();
	}

	private function discoverLocalDevices(): void
	{
		$factory = new Multicast\Factory($this->eventLoop);

		try {
			$server = $this->server = $factory->createReceiver(self::MDNS_ADDRESS . ':' . self::MDNS_PORT);
		} catch (Throwable $ex) {
			$this->logger->error(
				'Invalid mDNS question response received',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'discovery-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getPlainId(),
					],
				],
			);

			return;
		}

		$this->server->on('message', function ($message): void {
			try {
				$response = $this->parser->parseMessage($message);

			} catch (InvalidArgumentException) {
				$this->logger->warning(
					'Invalid mDNS question response received',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'discovery-client',
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
					],
				);

				return;
			}

			if ($response->tc) {
				$this->logger->warning(
					'The server set the truncated bit although we issued a TCP request',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'discovery-client',
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
					],
				);

				return;
			}

			$serviceIpAddress = null;
			$serviceDomain = null;
			$serviceName = null;
			$serviceData = [];

			foreach ($response->answers as $answer) {
				if (
					$answer->type === Dns\Model\Message::TYPE_A
					&& preg_match(self::MATCH_DOMAIN, $answer->name) === 1
					&& is_string($answer->data)
					&& preg_match(self::MATCH_IP_ADDRESS, $answer->data) === 1
					&& $serviceIpAddress === null
				) {
					$serviceIpAddress = $answer->data;
				}

				if (
					$answer->type === Dns\Model\Message::TYPE_SRV
					&& preg_match(self::MATCH_NAME, $answer->name) === 1
					&& is_array($answer->data)
					&& array_key_exists('target', $answer->data)
					&& $serviceDomain === null
				) {
					$serviceDomain = $answer->data['target'];
				}

				if (
					$answer->type === Dns\Model\Message::TYPE_PTR
					&& is_string($answer->data)
					&& preg_match(self::MATCH_NAME, $answer->data) === 1
					&& $serviceName === null
				) {
					$serviceName = $answer->data;
				}

				if (
					$answer->type === Dns\Model\Message::TYPE_TXT
					&& preg_match(self::MATCH_NAME, $answer->name) === 1
					&& is_array($answer->data)
				) {
					foreach ($answer->data as $dataRow) {
						[$key, $value] = explode('=', $dataRow) + [null, null];

						$serviceData[$key] = $value;
					}
				}
			}

			if ($serviceIpAddress !== null && $serviceName !== null) {
				$serviceResult = new Entities\Clients\MdnsResult($serviceIpAddress, $serviceName, $serviceData);

				if (!$this->searchResult->contains($serviceResult)) {
					$this->searchResult->attach($serviceResult);

					if (preg_match(self::MATCH_NAME, $serviceName, $matches) === 1) {
						$generation = array_key_exists('gen', $serviceData) && strval($serviceData['gen']) === '2'
							? Types\DeviceGeneration::get(Types\DeviceGeneration::GENERATION_2)
							: Types\DeviceGeneration::get(
								Types\DeviceGeneration::GENERATION_1,
							);

						$this->discoveredLocalDevices->attach(new Entities\Clients\DiscoveredLocalDevice(
							$generation,
							Utils\Strings::lower($matches['id']),
							Utils\Strings::lower($matches['type']),
							$serviceIpAddress,
							$serviceDomain,
						));
					}
				}
			}
		});

		$this->eventLoop->futureTick(function () use ($server): void {
			$query = new Dns\Query\Query(
				'_http._tcp.local',
				Dns\Model\Message::TYPE_PTR,
				Dns\Model\Message::CLASS_IN,
			);

			$request = $this->dumper->toBinary(Dns\Model\Message::createRequestForQuery($query));

			$server->send($request, self::MDNS_ADDRESS . ':' . self::MDNS_PORT);
		});

		// Searching timeout
		$this->eventLoop->addTimer(
			self::MDNS_SEARCH_TIMEOUT,
			function () use ($server): void {
				$server->close();

				$this->discoveredLocalDevices->rewind();

				$devices = [];

				foreach ($this->discoveredLocalDevices as $device) {
					$devices[] = $device;
				}

				$this->discoveredLocalDevices = new SplObjectStorage();

				if (count($devices) > 0) {
					$devices = $this->handleFoundLocalDevices($devices);
				}

				$this->emit('finished', [$devices]);
			},
		);
	}

	private function discoverCloudDevices(): void
	{
		// TODO: Implement cloud discovery
	}

	/**
	 * @param array<Entities\Clients\DiscoveredLocalDevice> $devices
	 *
	 * @return array<Entities\Messages\DiscoveredLocalDevice>
	 */
	private function handleFoundLocalDevices(array $devices): array
	{
		$processedDevices = [];

		$gen1HttpApi = $this->gen1HttpApiFactory->create();
		$gen2HttpApi = $this->gen2HttpApiFactory->create();

		foreach ($devices as $device) {
			try {
				if ($device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_1)) {
					$deviceInformation = $gen1HttpApi->getDeviceInformation($device->getIpAddress(), false);
					assert($deviceInformation instanceof Entities\API\Gen1\DeviceInformation);
				} elseif ($device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_2)) {
					$deviceInformation = $gen2HttpApi->getDeviceInformation($device->getIpAddress(), false);
					assert($deviceInformation instanceof Entities\API\Gen2\DeviceInformation);
				} else {
					continue;
				}
			} catch (Throwable $ex) {
				$this->logger->error(
					'Could not load device basic information',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'discovery-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
						'device' => [
							'identifier' => $device->getIdentifier(),
							'ip_address' => $device->getIpAddress(),
							'domain' => $device->getDomain(),
							'generation' => $device->getGeneration()->getValue(),
						],
					],
				);

				continue;
			}

			$deviceDescription = $deviceConfiguration = $deviceStatus = null;

			try {
				if ($device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_1)) {
					$deviceDescription = $gen1HttpApi->getDeviceDescription(
						$device->getIpAddress(),
						null,
						null,
						false,
					);
					assert($deviceDescription instanceof Entities\API\Gen1\DeviceDescription);
				} elseif ($device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_2)) {
					$deviceConfiguration = $gen2HttpApi->getDeviceConfiguration(
						$device->getIpAddress(),
						null,
						null,
						false,
					);
					assert($deviceConfiguration instanceof Entities\API\Gen2\DeviceConfiguration);
					$deviceStatus = $gen2HttpApi->getDeviceStatus($device->getIpAddress(), null, null, false);
					assert($deviceStatus instanceof Entities\API\Gen2\DeviceStatus);
				} else {
					continue;
				}
			} catch (Throwable $ex) {
				if (
					$ex instanceof Exceptions\HttpApiCall
					&& $ex->getCode() === StatusCodeInterface::STATUS_UNAUTHORIZED
				) {
					$this->logger->error(
						'Device is password protected and could not be accessed',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'discovery-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
							'device' => [
								'identifier' => $device->getIdentifier(),
								'ip_address' => $device->getIpAddress(),
								'domain' => $device->getDomain(),
								'generation' => $device->getGeneration()->getValue(),
							],
						],
					);
				} else {
					$this->logger->error(
						'Could not load device description or configuration',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'discovery-client',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'connector' => [
								'id' => $this->connector->getPlainId(),
							],
							'device' => [
								'identifier' => $device->getIdentifier(),
								'ip_address' => $device->getIpAddress(),
								'domain' => $device->getDomain(),
								'generation' => $device->getGeneration()->getValue(),
							],
						],
					);
				}

				continue;
			}

			try {
				if (
					$device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_1)
					&& $deviceDescription !== null
				) {
					$message = new Entities\Messages\DiscoveredLocalDevice(
						$this->connector->getId(),
						$device->getIdentifier(),
						$device->getGeneration(),
						$device->getIpAddress(),
						$device->getDomain(),
						$deviceInformation->getModel(),
						$deviceInformation->getMacAddress(),
						$deviceInformation->hasAuthentication(),
						$deviceInformation->getFirmware(),
						array_map(
							static function (Entities\API\Gen1\DeviceBlockDescription $block): Entities\Messages\ChannelDescription {
								$channel = new Entities\Messages\ChannelDescription(
									$block->getIdentifier() . '_' . $block->getDescription(),
									Helpers\Name::createName($block->getDescription()),
								);

								foreach ($block->getSensors() as $sensor) {
									$property = new Entities\Messages\PropertyDescription(
										(
											$sensor->getIdentifier()
											. '_'
											. $sensor->getType()->getValue()
											. '_'
											. $sensor->getDescription()
										),
										Helpers\Name::createName($sensor->getDescription()),
										$sensor->getDataType(),
										$sensor->getUnit(),
										$sensor->getFormat(),
										$sensor->getInvalid(),
										$sensor->isQueryable(),
										$sensor->isSettable(),
									);

									$channel->addProperty($property);
								}

								return $channel;
							},
							$deviceDescription->getBlocks(),
						),
					);
				} elseif (
					$device->getGeneration()->equalsValue(Types\DeviceGeneration::GENERATION_2)
					&& $deviceConfiguration !== null
				) {
					$message = new Entities\Messages\DiscoveredLocalDevice(
						$this->connector->getId(),
						$device->getIdentifier(),
						$device->getGeneration(),
						$device->getIpAddress(),
						$device->getDomain(),
						$deviceInformation->getModel(),
						$deviceInformation->getMacAddress(),
						$deviceInformation->hasAuthentication(),
						$deviceInformation->getFirmware(),
						array_map(
							static function ($component) use ($deviceStatus): Entities\Messages\ChannelDescription {
								$channel = new Entities\Messages\ChannelDescription(
									$component->getType()->getValue() . '_' . $component->getId(),
									$component->getName() ?? Helpers\Name::createName(
										strval($component->getType()->getValue()),
									),
								);

								if ($component instanceof Entities\API\Gen2\DeviceSwitchConfiguration) {
									$status = $deviceStatus?->findSwitch($component->getId());

									if (
										$status === null
										|| $status->getOutput() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::ON
											),
											Helpers\Name::createName(Types\ComponentAttributeType::ON),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
											null,
											null,
											null,
											true,
											true,
										));
									}

									if (
										$status === null
										|| $status->getActivePower() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::ACTIVE_POWER
											),
											Helpers\Name::createName(
												Types\ComponentAttributeType::ACTIVE_POWER,
											),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
											'W',
											null,
											null,
											true,
											false,
										));
									}

									if (
										$status === null
										|| $status->getPowerFactor() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::POWER_FACTOR
											),
											Helpers\Name::createName(
												Types\ComponentAttributeType::POWER_FACTOR,
											),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
											null,
											null,
											null,
											true,
											false,
										));
									}

									if (
										$status === null
										|| $status->getActiveEnergy() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::ACTIVE_ENERGY
											),
											Helpers\Name::createName(
												Types\ComponentAttributeType::ACTIVE_ENERGY,
											),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
											'Wh',
											null,
											null,
											true,
											false,
										));
									}

									if (
										$status === null
										|| $status->getCurrent() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::CURRENT
											),
											Helpers\Name::createName(Types\ComponentAttributeType::CURRENT),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
											'A',
											null,
											null,
											true,
											false,
										));
									}

									if (
										$status === null
										|| $status->getVoltage() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::VOLTAGE
											),
											Helpers\Name::createName(Types\ComponentAttributeType::VOLTAGE),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
											'V',
											null,
											null,
											true,
											false,
										));
									}

									if (
										$status === null
										|| $status->getTemperature() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::CELSIUS
											),
											Helpers\Name::createName(Types\ComponentAttributeType::CELSIUS),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
											'°C',
											null,
											null,
											true,
											false,
										));
									}
								} elseif ($component instanceof Entities\API\Gen2\DeviceCoverConfiguration) {
									$status = $deviceStatus?->findCover($component->getId());

									if (
										$status === null
										|| $status->getState() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::STATE
											),
											Helpers\Name::createName(Types\ComponentAttributeType::STATE),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
											null,
											[
												Types\CoverPayload::OPEN,
												Types\CoverPayload::CLOSED,
												Types\CoverPayload::OPENING,
												Types\CoverPayload::CLOSING,
												Types\CoverPayload::STOPPED,
												Types\CoverPayload::CALIBRATING,
											],
											null,
											true,
											false,
										));
									}

									$channel->addProperty(new Entities\Messages\PropertyDescription(
										(
											$component->getType()->getValue()
											. '_'
											. $component->getId()
											. '_'
											. Types\ComponentAttributeType::POSITION
										),
										Helpers\Name::createName(Types\ComponentAttributeType::POSITION),
										MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
										null,
										[0, 100],
										null,
										true,
										true,
									));

									if (
										$status === null
										|| $status->getActivePower() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::ACTIVE_POWER
											),
											Helpers\Name::createName(
												Types\ComponentAttributeType::ACTIVE_POWER,
											),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
											'W',
											null,
											null,
											true,
											false,
										));
									}

									if (
										$status === null
										|| $status->getPowerFactor() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::POWER_FACTOR
											),
											Helpers\Name::createName(
												Types\ComponentAttributeType::POWER_FACTOR,
											),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
											null,
											null,
											null,
											true,
											false,
										));
									}

									if (
										$status === null
										|| $status->getActiveEnergy() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::ACTIVE_ENERGY
											),
											Helpers\Name::createName(
												Types\ComponentAttributeType::ACTIVE_ENERGY,
											),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
											'Wh',
											null,
											null,
											true,
											false,
										));
									}

									if (
										$status === null
										|| $status->getCurrent() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::CURRENT
											),
											Helpers\Name::createName(Types\ComponentAttributeType::CURRENT),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
											'A',
											null,
											null,
											true,
											false,
										));
									}

									if (
										$status === null
										|| $status->getVoltage() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::VOLTAGE
											),
											Helpers\Name::createName(Types\ComponentAttributeType::VOLTAGE),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
											'V',
											null,
											null,
											true,
											false,
										));
									}

									if (
										$status === null
										|| $status->getTemperature() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::CELSIUS
											),
											Helpers\Name::createName(Types\ComponentAttributeType::CELSIUS),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
											'°C',
											null,
											null,
											true,
											false,
										));
									}
								} elseif ($component instanceof Entities\API\Gen2\DeviceLightConfiguration) {
									$status = $deviceStatus?->findLight($component->getId());

									if (
										$status === null
										|| $status->getOutput() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::ON
											),
											Helpers\Name::createName(Types\ComponentAttributeType::ON),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
											null,
											null,
											null,
											true,
											true,
										));
									}

									if (
										$status === null
										|| $status->getBrightness() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::BRIGHTNESS
											),
											Helpers\Name::createName(
												Types\ComponentAttributeType::BRIGHTNESS,
											),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
											null,
											[0, 100],
											null,
											true,
											true,
										));
									}
								} elseif ($component instanceof Entities\API\Gen2\DeviceInputConfiguration) {
									if ($component->getInputType()->equalsValue(Types\InputType::SWITCH)) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
											),
											null,
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
											null,
											null,
											null,
											true,
											false,
										));
									} elseif ($component->getInputType()->equalsValue(Types\InputType::BUTTON)) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
											),
											null,
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
											null,
											[
												Types\InputPayload::PRESS,
												Types\InputPayload::RELEASE,
												Types\InputPayload::SINGLE_PUSH,
												Types\InputPayload::DOUBLE_PUSH,
												Types\InputPayload::LONG_PUSH,
											],
											null,
											true,
											false,
										));
									} elseif ($component->getInputType()->equalsValue(Types\InputType::ANALOG)) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
											),
											null,
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
											null,
											[0, 100],
											null,
											true,
											false,
										));
									}
								} elseif ($component instanceof Entities\API\Gen2\DeviceTemperatureConfiguration) {
									$status = $deviceStatus?->findTemperature($component->getId());

									if (
										$status === null
										|| $status->getTemperatureCelsius() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::CELSIUS
											),
											Helpers\Name::createName(Types\ComponentAttributeType::CELSIUS),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
											'°C',
											null,
											null,
											true,
											false,
										));
									}

									if (
										$status === null
										|| $status->getTemperatureFahrenheit() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
												. '_'
												. Types\ComponentAttributeType::FAHRENHEIT
											),
											Helpers\Name::createName(
												Types\ComponentAttributeType::FAHRENHEIT,
											),
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
											'°F',
											null,
											null,
											true,
											false,
										));
									}
								} else {
									$status = $deviceStatus?->findHumidity($component->getId());

									if (
										$status === null
										|| $status->getRelativeHumidity() !== Shelly\Constants::VALUE_NOT_AVAILABLE
									) {
										$channel->addProperty(new Entities\Messages\PropertyDescription(
											(
												$component->getType()->getValue()
												. '_'
												. $component->getId()
											),
											null,
											MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
											'%',
											null,
											null,
											true,
											false,
										));
									}
								}

								return $channel;
							},
							array_merge(
								$deviceConfiguration->getSwitches(),
								$deviceConfiguration->getCovers(),
								$deviceConfiguration->getLights(),
								$deviceConfiguration->getInputs(),
								$deviceConfiguration->getTemperature(),
								$deviceConfiguration->getHumidity(),
							),
						),
					);
				} else {
					continue;
				}

				$processedDevices[] = $message;

				$this->consumer->append($message);
			} catch (Throwable $ex) {
				$this->logger->error(
					'Could not create discovered device',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
						'type' => 'discovery-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getPlainId(),
						],
						'device' => [
							'identifier' => $device->getIdentifier(),
							'ip_address' => $device->getIpAddress(),
							'domain' => $device->getDomain(),
							'generation' => $device->getGeneration()->getValue(),
						],
					],
				);

				continue;
			}
		}

		return $processedDevices;
	}

}
