<?php declare(strict_types = 1);

/**
 * Http.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           18.07.22
 */

namespace FastyBird\Connector\Shelly\Clients\Local;

use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Consumers;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\Http as ReactHttp;
use React\Promise;
use RuntimeException;
use Throwable;
use function assert;
use function count;
use function gethostbyname;
use function is_bool;
use function is_numeric;

/**
 * HTTP api client
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Http
{

	use Gen2 {
		processDeviceStatus as processGen2DeviceStatus;
	}
	use Nette\SmartObject;

	private API\Gen1HttpApi|null $gen1httpApi = null;

	private API\Gen2HttpApi|null $gen2httpApi = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly API\Gen1HttpApiFactory $gen1HttpApiFactory,
		private readonly API\Gen2HttpApiFactory $gen2HttpApiFactory,
		private readonly Consumers\Messages $consumer,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function connect(): void
	{
		$this->gen1httpApi = $this->gen1HttpApiFactory->create();
		$this->gen2httpApi = $this->gen2HttpApiFactory->create();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeChannelProperty(
		Entities\ShellyDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic $property,
		bool|float|int|string $value,
	): Promise\PromiseInterface
	{
		$address = $this->getDeviceAddress($device);

		if ($address === null) {
			$this->consumer->append(
				new Entities\Messages\DeviceState(
					$device->getConnector()->getId(),
					$device->getIdentifier(),
					MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_STOPPED),
				),
			);

			return Promise\reject(new Exceptions\InvalidState('Device ip address or domain is not configured'));
		}

		$generation = $device->getGeneration();

		if ($generation->equalsValue(Types\DeviceGeneration::GENERATION_1)) {
			$result = $this->gen1httpApi?->setDeviceStatus(
				$address,
				$device->getUsername(),
				$device->getPassword(),
				$channel->getIdentifier(),
				$property->getIdentifier(),
				$value,
			);
			assert($result instanceof Promise\ExtendedPromiseInterface);

		} elseif ($generation->equalsValue(Types\DeviceGeneration::GENERATION_2)) {
			$result = $this->gen2httpApi?->setDeviceStatus(
				$address,
				$device->getUsername(),
				$device->getPassword(),
				$property->getIdentifier(),
				$value,
			);
			assert($result instanceof Promise\ExtendedPromiseInterface);

		} else {
			return Promise\reject(new Exceptions\InvalidState('Unsupported device generation'));
		}

		$result
			->otherwise(function (Throwable $ex) use ($device): void {
				if ($ex instanceof ReactHttp\Message\ResponseException) {
					if (
						$ex->getCode() >= StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
						&& $ex->getCode() < StatusCodeInterface::STATUS_NETWORK_AUTHENTICATION_REQUIRED
					) {
						$this->consumer->append(
							new Entities\Messages\DeviceState(
								$device->getConnector()->getId(),
								$device->getIdentifier(),
								MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
							),
						);
					}
				}

				if ($ex instanceof Exceptions\Runtime) {
					$this->consumer->append(
						new Entities\Messages\DeviceState(
							$device->getConnector()->getId(),
							$device->getIdentifier(),
							MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
						),
					);
				}
			});

		return $result;
	}

	/**
	 * @param callable(Entities\API\Gen1\DeviceStatus|Entities\API\Gen2\DeviceStatus $status): void|null $onFulfilled
	 * @param callable(Throwable $ex): void|null $onRejected
	 *
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	public function readDeviceStates(
		Entities\ShellyDevice $device,
		callable|null $onFulfilled = null,
		callable|null $onRejected = null,
	): bool
	{
		$address = $this->getDeviceAddress($device);

		if ($address === null) {
			$this->consumer->append(
				new Entities\Messages\DeviceState(
					$device->getConnector()->getId(),
					$device->getIdentifier(),
					MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_STOPPED),
				),
			);

			return false;
		}

		$generation = $device->getGeneration();

		if ($generation->equalsValue(Types\DeviceGeneration::GENERATION_1)) {
			$result = $this->gen1httpApi?->getDeviceStatus(
				$address,
				$device->getUsername(),
				$device->getPassword(),
			);
			assert($result instanceof Promise\PromiseInterface);

		} elseif ($generation->equalsValue(Types\DeviceGeneration::GENERATION_2)) {
			$result = $this->gen2httpApi?->getDeviceStatus(
				$address,
				$device->getUsername(),
				$device->getPassword(),
			);
			assert($result instanceof Promise\PromiseInterface);

		} else {
			return false;
		}

		$result
			->then(
				function (Entities\API\Gen1\DeviceStatus|Entities\API\Gen2\DeviceStatus $status) use ($device, $onFulfilled): void {
					$this->consumer->append(
						new Entities\Messages\DeviceState(
							$device->getConnector()->getId(),
							$device->getIdentifier(),
							MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_CONNECTED),
						),
					);

					if ($status instanceof Entities\API\Gen1\DeviceStatus) {
						$this->processGen1DeviceStatus($device, $status);
					} else {
						$this->processGen2DeviceStatus($device, $status);
					}

					if ($onFulfilled !== null) {
						$onFulfilled($status);
					}
				},
			)
			->otherwise(function (Throwable $ex) use ($device, $onRejected): void {
				if (
					$ex instanceof ReactHttp\Message\ResponseException
					|| $ex instanceof Exceptions\HttpApiCall
				) {
					if (
						$ex->getCode() >= StatusCodeInterface::STATUS_BAD_REQUEST
						&& $ex->getCode() < StatusCodeInterface::STATUS_UNAVAILABLE_FOR_LEGAL_REASONS
					) {
						$this->consumer->append(
							new Entities\Messages\DeviceState(
								$device->getConnector()->getId(),
								$device->getIdentifier(),
								MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_STOPPED),
							),
						);

					} elseif (
						$ex->getCode() >= StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
						&& $ex->getCode() < StatusCodeInterface::STATUS_NETWORK_AUTHENTICATION_REQUIRED
					) {
						$this->consumer->append(
							new Entities\Messages\DeviceState(
								$device->getConnector()->getId(),
								$device->getIdentifier(),
								MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
							),
						);

					} else {
						$this->consumer->append(
							new Entities\Messages\DeviceState(
								$device->getConnector()->getId(),
								$device->getIdentifier(),
								MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_UNKNOWN),
							),
						);
					}
				}

				if ($ex instanceof Exceptions\Runtime) {
					$this->consumer->append(
						new Entities\Messages\DeviceState(
							$device->getConnector()->getId(),
							$device->getIdentifier(),
							MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_LOST),
						),
					);
				}

				if ($onRejected !== null) {
					$onRejected($ex);
				}
			});

		return true;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function getDeviceAddress(Entities\ShellyDevice $device): string|null
	{
		$domain = $device->getDomain();

		if ($domain !== null) {
			return gethostbyname($domain);
		}

		$ipAddress = $device->getIpAddress();

		if ($ipAddress !== null) {
			return $ipAddress;
		}

		$this->logger->error(
			'Device ip address or domain is not configured',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
				'type' => 'http-client',
				'device' => [
					'id' => $device->getPlainId(),
				],
			],
		);

		return null;
	}

	/**
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 */
	private function processGen1DeviceStatus(
		Entities\ShellyDevice $device,
		Entities\API\Gen1\DeviceStatus $status,
	): void
	{
		$statuses = [];

		foreach ($status->getInputs() as $index => $input) {
			foreach ($device->getChannels() as $channel) {
				if (Utils\Strings::endsWith($channel->getIdentifier(), '_' . $index)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_INPUT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$input->getInput(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_INPUT_EVENT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$input->getEvent(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_INPUT_EVENT_COUNT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$input->getEventCnt(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$statuses[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
					}

					break;
				}
			}
		}

		foreach ($status->getMeters() as $index => $meter) {
			foreach ($device->getChannels() as $channel) {
				if (Utils\Strings::endsWith($channel->getIdentifier(), '_' . $index)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (
							Utils\Strings::endsWith(
								$property->getIdentifier(),
								'_' . Types\SensorDescription::DESC_ACTIVE_POWER,
							)
							|| Utils\Strings::endsWith(
								$property->getIdentifier(),
								'_' . Types\SensorDescription::DESC_ROLLER_POWER,
							)
						) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$meter->getPower(),
								),
							);
						} elseif (
							(
								Utils\Strings::endsWith(
									$property->getIdentifier(),
									'_' . Types\SensorDescription::DESC_OVERPOWER,
								)
								|| Utils\Strings::endsWith(
									$property->getIdentifier(),
									'_' . Types\SensorDescription::DESC_OVERPOWER_VALUE,
								)
							)
						) {
							if (
								(
									$property->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)
									&& is_bool($meter->getOverpower())
								) || (
									!$property->getDataType()->equalsValue(MetadataTypes\DataType::DATA_TYPE_BOOLEAN)
									&& is_numeric($meter->getOverpower())
								)
							) {
								$result[] = new Entities\Messages\PropertyStatus(
									$property->getIdentifier(),
									API\Transformer::transformValueFromDevice(
										$property->getDataType(),
										$property->getFormat(),
										$meter->getOverpower(),
									),
								);
							}
						} elseif (
							Utils\Strings::endsWith(
								$property->getIdentifier(),
								'_' . Types\SensorDescription::DESC_ENERGY,
							)
							|| Utils\Strings::endsWith(
								$property->getIdentifier(),
								'_' . Types\SensorDescription::DESC_ROLLER_ENERGY,
							)
						) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$meter->getTotal(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$statuses[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
					}

					break;
				}
			}
		}

		foreach ($status->getRelays() as $index => $relay) {
			foreach ($device->getChannels() as $channel) {
				if (Utils\Strings::endsWith(
					$channel->getIdentifier(),
					Types\BlockDescription::DESC_RELAY . '_' . $index,
				)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_OUTPUT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$relay->getState(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_OVERPOWER,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$relay->hasOverpower(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$statuses[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
					}
				} elseif (Utils\Strings::endsWith($channel->getIdentifier(), Types\BlockDescription::DESC_DEVICE)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_OVERTEMPERATURE,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$relay->hasOvertemperature(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$statuses[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
					}
				}
			}
		}

		foreach ($status->getRollers() as $index => $roller) {
			foreach ($device->getChannels() as $channel) {
				if (Utils\Strings::endsWith(
					$channel->getIdentifier(),
					Types\BlockDescription::DESC_ROLLER . '_' . $index,
				)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_ROLLER,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->getState(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_ROLLER_POSITION,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->getCurrentPosition(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_ROLLER_STOP_REASON,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->getStopReason(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$statuses[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
					}
				} elseif (Utils\Strings::endsWith($channel->getIdentifier(), Types\BlockDescription::DESC_DEVICE)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_OVERTEMPERATURE,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$roller->hasOvertemperature(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$statuses[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
					}
				}
			}
		}

		foreach ($status->getLights() as $index => $light) {
			foreach ($device->getChannels() as $channel) {
				if (Utils\Strings::endsWith(
					$channel->getIdentifier(),
					Types\BlockDescription::DESC_LIGHT . '_' . $index,
				)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_RED,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getRed(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_GREEN,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getGreen(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_BLUE,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getBlue(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_GAIN,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getGain(),
								),
							);
						} elseif (
							Utils\Strings::endsWith(
								$property->getIdentifier(),
								'_' . Types\SensorDescription::DESC_WHITE,
							)
							|| Utils\Strings::endsWith(
								$property->getIdentifier(),
								'_' . Types\SensorDescription::DESC_WHITE_LEVEL,
							)
						) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getWhite(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_EFFECT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getEffect(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_BRIGHTNESS,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getBrightness(),
								),
							);
						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_OUTPUT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$light->getState(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$statuses[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
					}

					break;
				}
			}
		}

		foreach ($status->getEmeters() as $index => $emeter) {
			foreach ($device->getChannels() as $channel) {
				if (Utils\Strings::endsWith(
					$channel->getIdentifier(),
					Types\BlockDescription::DESC_EMETER . '_' . $index,
				)) {
					$result = [];

					foreach ($channel->getProperties() as $property) {
						if (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_ACTIVE_POWER,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getActivePower(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_REACTIVE_POWER,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getReactivePower(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_POWER_FACTOR,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getPowerFactor(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_CURRENT,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getCurrent(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_VOLTAGE,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getVoltage(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_ENERGY,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getTotal(),
								),
							);

						} elseif (Utils\Strings::endsWith(
							$property->getIdentifier(),
							'_' . Types\SensorDescription::DESC_ENERGY_RETURNED,
						)) {
							$result[] = new Entities\Messages\PropertyStatus(
								$property->getIdentifier(),
								API\Transformer::transformValueFromDevice(
									$property->getDataType(),
									$property->getFormat(),
									$emeter->getTotalReturned(),
								),
							);
						}
					}

					if (count($result) > 0) {
						$statuses[] = new Entities\Messages\ChannelStatus(
							$channel->getIdentifier(),
							$result,
						);
					}

					break;
				}
			}
		}

		if (count($statuses) > 0) {
			$this->consumer->append(
				new Entities\Messages\DeviceStatus(
					$device->getConnector()->getId(),
					$device->getIdentifier(),
					$status->getWifi()?->getIp(),
					$statuses,
				),
			);
		}
	}

}
