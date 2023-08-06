<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;
use function array_map;
use function assert;
use function is_string;
use function sprintf;
use function strval;

/**
 * Third-party device & Sub-device status definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class State implements Entity
{

	/**
	 * @param array<Entities\API\Statuses\ToggleState> $toggle
	 */
	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\Battery::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::BATTERY)]
		private readonly Entities\API\Statuses\Battery|null $battery = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\Brightness::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::BRIGHTNESS)]
		private readonly Entities\API\Statuses\Brightness|null $brightness = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\CameraStream::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::CAMERA_STREAM)]
		private readonly Entities\API\Statuses\CameraStream|null $cameraStream = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\ColorRgb::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::COLOR_RGB)]
		private readonly Entities\API\Statuses\ColorRgb|null $colorRgb = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\ColorTemperature::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::COLOR_TEMPERATURE)]
		private readonly Entities\API\Statuses\ColorTemperature|null $colorTemperature = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\Detect::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::DETECT)]
		private readonly Entities\API\Statuses\Detect|null $detect = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\Humidity::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::HUMIDITY)]
		private readonly Entities\API\Statuses\Humidity|null $humidity = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\MotorCalibration::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::MOTOR_CALIBRATION)]
		private readonly Entities\API\Statuses\MotorCalibration|null $motorCalibration = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\MotorControl::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::MOTOR_CONTROL)]
		private readonly Entities\API\Statuses\MotorControl|null $motorControl = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\MotorReverse::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::MOTOR_REVERSE)]
		private readonly Entities\API\Statuses\MotorReverse|null $motorReverse = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\Percentage::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::PERCENTAGE)]
		private readonly Entities\API\Statuses\Percentage|null $percentage = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\PowerState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::POWER)]
		private readonly Entities\API\Statuses\PowerState|null $power = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\Press::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::PRESS)]
		private readonly Entities\API\Statuses\Press|null $press = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\Rssi::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::RSSI)]
		private readonly Entities\API\Statuses\Rssi|null $rssi = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\Startup::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::STARTUP)]
		private readonly Entities\API\Statuses\Startup|null $startup = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\Temperature::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::TEMPERATURE)]
		private readonly Entities\API\Statuses\Temperature|null $temperature = null,
		#[ObjectMapper\Rules\ArrayOf(
			item: new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\ToggleState::class),
			key: new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\StringValue(),
				new ObjectMapper\Rules\IntValue(),
			]),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::TOGGLE)]
		private readonly array $toggle = [],
	)
	{
	}

	/**
	 * @return array<string, Entities\API\Statuses\Status>
	 */
	public function getStatuses(): array
	{
		$statuses = [];

		foreach (Types\Capability::getAvailableValues() as $capability) {
			assert(is_string($capability));

			switch ($capability) {
				case Types\Capability::POWER:
					if ($this->power !== null) {
						$statuses[$capability] = $this->power;
					}

					break;
				case Types\Capability::TOGGLE:
					foreach ($this->toggle as $identifier => $status) {
						$statuses[sprintf('%s_%s', $capability, strval($identifier))] = $status;
					}

					break;
				case Types\Capability::BRIGHTNESS:
					if ($this->brightness !== null) {
						$statuses[$capability] = $this->brightness;
					}

					break;
				case Types\Capability::COLOR_TEMPERATURE:
					if ($this->colorTemperature !== null) {
						$statuses[$capability] = $this->colorTemperature;
					}

					break;
				case Types\Capability::COLOR_RGB:
					if ($this->colorRgb !== null) {
						$statuses[$capability] = $this->colorRgb;
					}

					break;
				case Types\Capability::PERCENTAGE:
					if ($this->percentage !== null) {
						$statuses[$capability] = $this->percentage;
					}

					break;
				case Types\Capability::MOTOR_CONTROL:
					if ($this->motorControl !== null) {
						$statuses[$capability] = $this->motorControl;
					}

					break;
				case Types\Capability::MOTOR_REVERSE:
					if ($this->motorReverse !== null) {
						$statuses[$capability] = $this->motorReverse;
					}

					break;
				case Types\Capability::MOTOR_CALIBRATION:
					if ($this->motorCalibration !== null) {
						$statuses[$capability] = $this->motorCalibration;
					}

					break;
				case Types\Capability::STARTUP:
					if ($this->startup !== null) {
						$statuses[$capability] = $this->startup;
					}

					break;
				case Types\Capability::CAMERA_STREAM:
					if ($this->cameraStream !== null) {
						$statuses[$capability] = $this->cameraStream;
					}

					break;
				case Types\Capability::DETECT:
					if ($this->detect !== null) {
						$statuses[$capability] = $this->detect;
					}

					break;
				case Types\Capability::HUMIDITY:
					if ($this->humidity !== null) {
						$statuses[$capability] = $this->humidity;
					}

					break;
				case Types\Capability::TEMPERATURE:
					if ($this->temperature !== null) {
						$statuses[$capability] = $this->temperature;
					}

					break;
				case Types\Capability::BATTERY:
					if ($this->battery !== null) {
						$statuses[$capability] = $this->battery;
					}

					break;
				case Types\Capability::PRESS:
					if ($this->press !== null) {
						$statuses[$capability] = $this->press;
					}

					break;
				case Types\Capability::RSSI:
					if ($this->rssi !== null) {
						$statuses[$capability] = $this->rssi;
					}

					break;
			}
		}

		return $statuses;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_map(
			static fn (Entities\API\Statuses\Status $status): array => $status->toArray(),
			$this->getStatuses(),
		);
	}

	public function toJson(): object
	{
		$json = new stdClass();

		foreach (Types\Capability::getAvailableValues() as $capability) {
			assert(is_string($capability));

			switch ($capability) {
				case Types\Capability::POWER:
					if ($this->power !== null) {
						$json->{$capability} = $this->power->toJson();
					}

					break;
				case Types\Capability::TOGGLE:
					if ($this->toggle !== []) {
						$json->{$capability} = new stdClass();

						foreach ($this->toggle as $name => $status) {
							$json->{$capability}->{$name} = $status->toJson();
						}
					}

					break;
				case Types\Capability::BRIGHTNESS:
					if ($this->brightness !== null) {
						$json->{$capability} = $this->brightness->toJson();
					}

					break;
				case Types\Capability::COLOR_TEMPERATURE:
					if ($this->colorTemperature !== null) {
						$json->{$capability} = $this->colorTemperature->toJson();
					}

					break;
				case Types\Capability::COLOR_RGB:
					if ($this->colorRgb !== null) {
						$json->{$capability} = $this->colorRgb->toJson();
					}

					break;
				case Types\Capability::PERCENTAGE:
					if ($this->percentage !== null) {
						$json->{$capability} = $this->percentage->toJson();
					}

					break;
				case Types\Capability::MOTOR_CONTROL:
					if ($this->motorControl !== null) {
						$json->{$capability} = $this->motorControl->toJson();
					}

					break;
				case Types\Capability::MOTOR_REVERSE:
					if ($this->motorReverse !== null) {
						$json->{$capability} = $this->motorReverse->toJson();
					}

					break;
				case Types\Capability::MOTOR_CALIBRATION:
					if ($this->motorCalibration !== null) {
						$json->{$capability} = $this->motorCalibration->toJson();
					}

					break;
				case Types\Capability::STARTUP:
					if ($this->startup !== null) {
						$json->{$capability} = $this->startup->toJson();
					}

					break;
				case Types\Capability::CAMERA_STREAM:
					if ($this->cameraStream !== null) {
						$json->{$capability} = $this->cameraStream->toJson();
					}

					break;
				case Types\Capability::DETECT:
					if ($this->detect !== null) {
						$json->{$capability} = $this->detect->toJson();
					}

					break;
				case Types\Capability::HUMIDITY:
					if ($this->humidity !== null) {
						$json->{$capability} = $this->humidity->toJson();
					}

					break;
				case Types\Capability::TEMPERATURE:
					if ($this->temperature !== null) {
						$json->{$capability} = $this->temperature->toJson();
					}

					break;
				case Types\Capability::BATTERY:
					if ($this->battery !== null) {
						$json->{$capability} = $this->battery->toJson();
					}

					break;
				case Types\Capability::PRESS:
					if ($this->press !== null) {
						$json->{$capability} = $this->press->toJson();
					}

					break;
				case Types\Capability::RSSI:
					if ($this->rssi !== null) {
						$json->{$capability} = $this->rssi->toJson();
					}

					break;
			}
		}

		return $json;
	}

}