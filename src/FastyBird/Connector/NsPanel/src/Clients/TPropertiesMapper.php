<?php declare(strict_types = 1);

/**
 * TPropertiesMapper.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           16.07.23
 */

namespace FastyBird\Connector\NsPanel\Clients;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use function is_bool;
use function is_float;
use function is_int;
use function strval;

/**
 * Third-party device & sub-device property to state mapper
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read Helpers\Property $propertyStateHelper
 * @property-read DevicesModels\Channels\Properties\PropertiesRepository $channelsPropertiesRepository
 */
trait TPropertiesMapper
{

	/**
	 * @return array<mixed>|null
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function mapChannelToStatus(
		Entities\NsPanelChannel $channel,
	): array|null
	{
		switch ($channel->getCapability()->getValue()) {
			case Types\Capability::POWER:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::POWER_STATE));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null || !Types\PowerPayload::isValidValue($value)) {
					$value = Types\PowerPayload::OFF;
				}

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::POWER_STATE => $value,
					],
				];
			case Types\Capability::TOGGLE:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::TOGGLE_STATE));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null || !Types\TogglePayload::isValidValue($value)) {
					$value = Types\TogglePayload::OFF;
				}

				return [
					$channel->getCapability()->getValue() => [
						$channel->getIdentifier() => [
							Types\Protocol::TOGGLE_STATE => $value,
						],
					],
				];
			case Types\Capability::BRIGHTNESS:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::BRIGHTNESS));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if (!is_int($value)) {
					$value = 0;
				}

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::BRIGHTNESS => $value,
					],
				];
			case Types\Capability::COLOR_TEMPERATURE:
				$property = $this->findProtocolProperty(
					$channel,
					Types\Protocol::get(Types\Protocol::COLOR_TEMPERATURE),
				);

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if (!is_int($value)) {
					$value = 0;
				}

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::COLOR_TEMPERATURE => $value,
					],
				];
			case Types\Capability::COLOR_RGB:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::COLOR_RED));

				if ($property === null) {
					return null;
				}

				$red = $this->getPropertyValue($property);

				if (!is_int($red)) {
					$red = 0;
				}

				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::COLOR_GREEN));

				if ($property === null) {
					return null;
				}

				$green = $this->getPropertyValue($property);

				if (!is_int($green)) {
					$green = 0;
				}

				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::COLOR_BLUE));

				if ($property === null) {
					return null;
				}

				$blue = $this->getPropertyValue($property);

				if (!is_int($blue)) {
					$blue = 0;
				}

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::COLOR_RED => $red,
						Types\Protocol::COLOR_GREEN => $green,
						Types\Protocol::COLOR_BLUE => $blue,
					],
				];
			case Types\Capability::PERCENTAGE:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::PERCENTAGE));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if (!is_int($value)) {
					$value = 0;
				}

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::PERCENTAGE => $value,
					],
				];
			case Types\Capability::MOTOR_CONTROL:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::MOTOR_CONTROL));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null || !Types\MotorControlPayload::isValidValue($value)) {
					$value = Types\MotorControlPayload::STOP;
				}

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::MOTOR_CONTROL => $value,
					],
				];
			case Types\Capability::MOTOR_REVERSE:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::MOTOR_REVERSE));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if (!is_bool($value)) {
					$value = false;
				}

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::MOTOR_REVERSE => $value,
					],
				];
			case Types\Capability::MOTOR_CALIBRATION:
				$property = $this->findProtocolProperty(
					$channel,
					Types\Protocol::get(Types\Protocol::MOTOR_CALIBRATION),
				);

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null || !Types\MotorCalibrationPayload::isValidValue($value)) {
					$value = Types\MotorCalibrationPayload::NORMAL;
				}

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::MOTOR_CALIBRATION => $value,
					],
				];
			case Types\Capability::STARTUP:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::STARTUP));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null || !Types\StartupPayload::isValidValue($value)) {
					$value = Types\StartupPayload::OFF;
				}

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::STARTUP => $value,
					],
				];
			case Types\Capability::CAMERA_STREAM:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::STREAM_URL));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::CONFIGURATION => [
							Types\Protocol::STREAM_URL => strval($value),
						],
					],
				];
			case Types\Capability::DETECT:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::DETECT));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if (!is_bool($value)) {
					$value = false;
				}

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::DETECT => $value,
					],
				];
			case Types\Capability::HUMIDITY:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::HUMIDITY));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if (!is_int($value)) {
					$value = 0;
				}

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::HUMIDITY => $value,
					],
				];
			case Types\Capability::TEMPERATURE:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::TEMPERATURE));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if (!is_float($value)) {
					$value = 0.0;
				}

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::TEMPERATURE => $value,
					],
				];
			case Types\Capability::BATTERY:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::BATTERY));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if (!is_int($value)) {
					$value = 0;
				}

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::BATTERY => $value,
					],
				];
			case Types\Capability::PRESS:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::PRESS));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null || Types\PressPayload::isValidValue($value)) {
					return null;
				}

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::PRESS => $value,
					],
				];
			case Types\Capability::RSSI:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::RSSI));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if (!is_int($value)) {
					$value = 0;
				}

				return [
					$channel->getCapability()->getValue() => [
						Types\Protocol::RSSI => $value,
					],
				];
		}

		throw new Exceptions\InvalidArgument('Provided property type is not supported');
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function getPropertyValue(
		DevicesEntities\Channels\Properties\Mapped|DevicesEntities\Channels\Properties\Variable $property,
	): string|int|float|bool|null
	{
		if ($property instanceof DevicesEntities\Channels\Properties\Mapped) {
			$actualValue = $this->propertyStateHelper->getActualValue($property);
			$expectedValue = $this->propertyStateHelper->getExpectedValue($property);

			$value = $expectedValue ?? $actualValue;
		} else {
			$value = $property->getValue();
		}

		return Helpers\Transformer::transformValueToDevice(
			$property->getDataType(),
			$property->getFormat(),
			$value,
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	private function findProtocolProperty(
		Entities\NsPanelChannel $channel,
		Types\Protocol $protocol,
	): DevicesEntities\Channels\Properties\Mapped|DevicesEntities\Channels\Properties\Variable|null
	{
		$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byIdentifier(Helpers\Name::convertProtocolToProperty($protocol));

		$property = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

		if ($property === null) {
			return null;
		}

		if (
			!$property instanceof DevicesEntities\Channels\Properties\Mapped
			&& !$property instanceof DevicesEntities\Channels\Properties\Variable
		) {
			return null;
		}

		return $property;
	}

}
