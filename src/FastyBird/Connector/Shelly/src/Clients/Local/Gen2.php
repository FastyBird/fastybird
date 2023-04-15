<?php declare(strict_types = 1);

/**
 * Gen2.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Shelly\Clients\Local;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Consumers;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use function array_filter;
use function array_map;
use function array_merge;
use function strval;

/**
 * Generation 2 devices clients helpers
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read Consumers\Messages $consumer
 * @property-read DevicesModels\Devices\Properties\PropertiesRepository $devicePropertiesRepository
 * @property-read DevicesModels\Channels\ChannelsRepository $channelsRepository
 */
trait Gen2
{

	private function processDeviceStatus(
		Entities\ShellyDevice $device,
		Entities\API\Gen2\DeviceStatus $status,
	): void
	{
		$statuses = array_map(
			function ($component) use ($device): array {
				$result = [];

				if ($component instanceof Entities\API\Gen2\DeviceSwitchStatus) {
					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_ON
						),
					);

					if ($property !== null && $component->getOutput() !== Shelly\Constants::VALUE_NOT_AVAILABLE) {
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getOutput(),
							),
						);
					}
				} elseif ($component instanceof Entities\API\Gen2\DeviceCoverStatus) {
					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_STATE
						),
					);

					if ($property !== null && $component->getState() instanceof Types\CoverPayload) {
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								strval($component->getState()->getValue()),
							),
						);
					}

					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_POSITION
						),
					);

					if ($property !== null) {
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getCurrentPosition(),
							),
						);
					}
				} elseif ($component instanceof Entities\API\Gen2\DeviceLightStatus) {
					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_ON
						),
					);

					if ($property !== null && $component->getOutput() !== Shelly\Constants::VALUE_NOT_AVAILABLE) {
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getOutput(),
							),
						);
					}

					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_BRIGHTNESS
						),
					);

					if ($property !== null && $component->getBrightness() !== Shelly\Constants::VALUE_NOT_AVAILABLE) {
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getBrightness(),
							),
						);
					}
				} elseif ($component instanceof Entities\API\Gen2\DeviceInputStatus) {
					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
						),
					);

					if ($property !== null) {
						if ($component->getState() instanceof Types\InputPayload) {
							$value = strval($component->getState()->getValue());
						} elseif ($component->getState() !== null) {
							$value = $component->getState();
						} else {
							$value = $component->getPercent();
						}

						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$value,
							),
						);
					}
				} elseif ($component instanceof Entities\API\Gen2\DeviceTemperatureStatus) {
					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_CELSIUS
						),
					);

					if (
						$property !== null
						&& $component->getTemperatureCelsius() !== Shelly\Constants::VALUE_NOT_AVAILABLE
					) {
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getTemperatureCelsius(),
							),
						);
					}

					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_FAHRENHEIT
						),
					);

					if (
						$property !== null
						&& $component->getTemperatureFahrenheit() !== Shelly\Constants::VALUE_NOT_AVAILABLE
					) {
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getTemperatureFahrenheit(),
							),
						);
					}
				} else {
					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
						),
					);

					if (
						$property !== null
						&& $component->getRelativeHumidity() !== Shelly\Constants::VALUE_NOT_AVAILABLE
					) {
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getRelativeHumidity(),
							),
						);
					}
				}

				if (
					$component instanceof Entities\API\Gen2\DeviceSwitchStatus
					|| $component instanceof Entities\API\Gen2\DeviceCoverStatus
				) {
					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_ACTIVE_POWER
						),
					);

					if ($property !== null && $component->getActivePower() !== Shelly\Constants::VALUE_NOT_AVAILABLE) {
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getActivePower(),
							),
						);
					}

					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_POWER_FACTOR
						),
					);

					if ($property !== null && $component->getPowerFactor() !== Shelly\Constants::VALUE_NOT_AVAILABLE) {
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getPowerFactor(),
							),
						);
					}

					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_ACTIVE_ENERGY
						),
					);

					if (
						$property !== null
						&& $component->getActiveEnergy() instanceof Entities\API\Gen2\ActiveEnergyStatusBlock
					) {
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getActiveEnergy()->getTotal(),
							),
						);
					}

					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_CURRENT
						),
					);

					if ($property !== null && $component->getCurrent() !== Shelly\Constants::VALUE_NOT_AVAILABLE) {
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getCurrent(),
							),
						);
					}

					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_VOLTAGE
						),
					);

					if ($property !== null && $component->getVoltage() !== Shelly\Constants::VALUE_NOT_AVAILABLE) {
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getVoltage(),
							),
						);
					}

					$property = $this->findProperty(
						$device,
						(
							$component->getType()->getValue()
							. '_'
							. $component->getId()
							. '_'
							. Types\ComponentAttributeType::ATTRIBUTE_CELSIUS
						),
					);

					if (
						$property !== null
						&& $component->getTemperature() instanceof Entities\API\Gen2\TemperatureBlockStatus
					) {
						$result[] = new Entities\Messages\PropertyStatus(
							$property->getIdentifier(),
							API\Transformer::transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								$component->getTemperature()->getTemperatureCelsius(),
							),
						);
					}
				}

				return $result;
			},
			array_merge(
				$status->getSwitches(),
				$status->getCovers(),
				$status->getInputs(),
				$status->getLights(),
				$status->getTemperature(),
				$status->getHumidity(),
			),
		);

		$statuses = array_filter($statuses, static fn (array $item): bool => $item !== []);
		$statuses = array_merge([], ...$statuses);

		$this->consumer->append(
			new Entities\Messages\DeviceStatus(
				$device->getConnector()->getId(),
				$device->getIdentifier(),
				$status->getEthernet()?->getIp() ?? $status->getWifi()?->getStaIp(),
				$statuses,
			),
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function findProperty(
		Entities\ShellyDevice $device,
		string $propertyIdentifier,
	): DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Channels\Properties\Dynamic|null
	{
		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier($propertyIdentifier);

		$property = $this->devicePropertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($property instanceof DevicesEntities\Devices\Properties\Dynamic) {
			return $property;
		}

		$findChannelsQuery = new DevicesQueries\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $this->channelsRepository->findAllBy($findChannelsQuery);

		foreach ($channels as $channel) {
			$property = $channel->findProperty($propertyIdentifier);

			if ($property instanceof DevicesEntities\Channels\Properties\Dynamic) {
				return $property;
			}
		}

		return null;
	}

}
