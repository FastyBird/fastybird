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
use FastyBird\Module\Devices\Queries\FindChannelProperties;
use function boolval;
use function intval;
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
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function mapChannelToStatus(
		Entities\NsPanelChannel $channel,
	): Entities\API\Statuses\Status|null
	{
		switch ($channel->getCapability()->getValue()) {
			case Types\Capability::POWER:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::POWER_STATE));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return new Entities\API\Statuses\Power(Types\PowerPayload::get($value));
			case Types\Capability::TOGGLE:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::TOGGLE_STATE));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return new Entities\API\Statuses\Toggle(
					$channel->getIdentifier(),
					Types\TogglePayload::get($value),
				);
			case Types\Capability::BRIGHTNESS:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::BRIGHTNESS));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return new Entities\API\Statuses\Brightness(intval($value));
			case Types\Capability::COLOR_TEMPERATURE:
				$property = $this->findProtocolProperty(
					$channel,
					Types\Protocol::get(Types\Protocol::COLOR_TEMPERATURE),
				);

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return new Entities\API\Statuses\ColorTemperature(intval($value));
			case Types\Capability::COLOR_RGB:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::COLOR_RED));

				if ($property === null) {
					return null;
				}

				$red = $this->getPropertyValue($property);

				if ($red === null) {
					return null;
				}

				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::COLOR_GREEN));

				if ($property === null) {
					return null;
				}

				$green = $this->getPropertyValue($property);

				if ($green === null) {
					return null;
				}

				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::COLOR_BLUE));

				if ($property === null) {
					return null;
				}

				$blue = $this->getPropertyValue($property);

				if ($blue === null) {
					return null;
				}

				return new Entities\API\Statuses\ColorRgb(intval($red), intval($green), intval($blue));
			case Types\Capability::PERCENTAGE:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::PERCENTAGE));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return new Entities\API\Statuses\Percentage(intval($value));
			case Types\Capability::MOTOR_CONTROL:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::MOTOR_CONTROL));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return new Entities\API\Statuses\MotorControl(Types\MotorControlPayload::get($value));
			case Types\Capability::MOTOR_REVERSE:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::MOTOR_REVERSE));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return new Entities\API\Statuses\MotorReverse(boolval($value));
			case Types\Capability::MOTOR_CALIBRATION:
				$property = $this->findProtocolProperty(
					$channel,
					Types\Protocol::get(Types\Protocol::MOTOR_CALIBRATION),
				);

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return new Entities\API\Statuses\MotorCalibration(Types\MotorCalibrationPayload::get($value));
			case Types\Capability::STARTUP:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::STARTUP));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return new Entities\API\Statuses\Startup(
					Types\StartupPayload::get($value),
					$channel->getIdentifier(),
				);
			case Types\Capability::CAMERA_STREAM:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::STREAM_URL));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				return new Entities\API\Statuses\CameraStream(strval($value));
			case Types\Capability::DETECT:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::DETECT));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return new Entities\API\Statuses\Detect(boolval($value));
			case Types\Capability::HUMIDITY:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::HUMIDITY));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return new Entities\API\Statuses\Humidity(intval($value));
			case Types\Capability::TEMPERATURE:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::TEMPERATURE));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return new Entities\API\Statuses\Temperature(intval($value));
			case Types\Capability::BATTERY:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::BATTERY));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return new Entities\API\Statuses\Battery(intval($value));
			case Types\Capability::PRESS:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::PRESS));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return new Entities\API\Statuses\Press(Types\PressPayload::get($value));
			case Types\Capability::RSSI:
				$property = $this->findProtocolProperty($channel, Types\Protocol::get(Types\Protocol::RSSI));

				if ($property === null) {
					return null;
				}

				$value = $this->getPropertyValue($property);

				if ($value === null) {
					return null;
				}

				return new Entities\API\Statuses\Rssi(intval($value));
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
		$findChannelPropertyQuery = new FindChannelProperties();
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
