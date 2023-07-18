<?php declare(strict_types = 1);

/**
 * Device.php
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

use DateTimeInterface;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
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
 */
trait TPropertiesMapper
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function mapPropertyToState(
		DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped $property,
		bool|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\CoverPayload|MetadataTypes\SwitchPayload|float|int|string|null $value = null,
	): Entities\API\Statuses\Status
	{
		$value = API\Transformer::transformValueToDevice(
			$property->getDataType(),
			$property->getFormat(),
			$value,
		);

		switch ($property->getIdentifier()) {
			case Types\Capability::POWER:
				return new Entities\API\Statuses\Power(Types\PowerPayload::get($value));
			case Types\Capability::TOGGLE:
				return new Entities\API\Statuses\Toggle(
					$property->getChannel()->getIdentifier(),
					Types\TogglePayload::get($value),
				);
			case Types\Capability::BRIGHTNESS:
				return new Entities\API\Statuses\Brightness(intval($value));
			case Types\Capability::COLOR_TEMPERATURE:
				return new Entities\API\Statuses\ColorTemperature(intval($value));
			case Types\Capability::COLOR_RGB:
				throw new Exceptions\InvalidArgument('Color RGB type is not supported');
			case Types\Capability::PERCENTAGE:
				return new Entities\API\Statuses\Percentage(intval($value));
			case Types\Capability::MOTOR_CONTROL:
				return new Entities\API\Statuses\MotorControl(Types\MotorControlPayload::get($value));
			case Types\Capability::MOTOR_REVERSE:
				return new Entities\API\Statuses\MotorReverse(boolval($value));
			case Types\Capability::MOTOR_CALIBRATION:
				return new Entities\API\Statuses\MotorCalibration(Types\MotorCalibrationPayload::get($value));
			case Types\Capability::STARTUP:
				return new Entities\API\Statuses\Startup(
					Types\StartupPayload::get($value),
					$property->getChannel()->getIdentifier(),
				);
			case Types\Capability::CAMERA_STREAM:
				return new Entities\API\Statuses\CameraStream(strval($value));
			case Types\Capability::DETECT:
				return new Entities\API\Statuses\Detect(boolval($value));
			case Types\Capability::HUMIDITY:
				return new Entities\API\Statuses\Humidity(intval($value));
			case Types\Capability::TEMPERATURE:
				return new Entities\API\Statuses\Temperature(intval($value));
			case Types\Capability::BATTERY:
				return new Entities\API\Statuses\Battery(intval($value));
			case Types\Capability::PRESS:
				return new Entities\API\Statuses\Press(Types\PressPayload::get($value));
			case Types\Capability::RSSI:
				return new Entities\API\Statuses\Rssi(intval($value));
		}

		throw new Exceptions\InvalidArgument('Provided property type is not supported');
	}

}
