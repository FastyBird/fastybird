<?php declare(strict_types = 1);

/**
 * State.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\API\Messages;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;
use function array_map;
use function assert;
use function is_string;
use function sprintf;
use function strval;

/**
 * Third-party device & Sub-device state definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class State implements Message
{

	/**
	 * @param array<States\ToggleState> $toggle
	 */
	public function __construct(
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Battery::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::BATTERY)]
		private States\Battery|null $battery = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Brightness::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::BRIGHTNESS)]
		private States\Brightness|null $brightness = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\CameraStream::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::CAMERA_STREAM)]
		private States\CameraStream|null $cameraStream = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\ColorRgb::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::COLOR_RGB)]
		private States\ColorRgb|null $colorRgb = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(
				States\ColorTemperature::class,
			),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::COLOR_TEMPERATURE)]
		private States\ColorTemperature|null $colorTemperature = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Detect::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::DETECT)]
		private States\Detect|null $detect = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Humidity::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::HUMIDITY)]
		private States\Humidity|null $humidity = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(
				States\MotorCalibration::class,
			),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::MOTOR_CALIBRATION)]
		private States\MotorCalibration|null $motorCalibration = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\MotorControl::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::MOTOR_CONTROL)]
		private States\MotorControl|null $motorControl = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\MotorReverse::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::MOTOR_REVERSE)]
		private States\MotorReverse|null $motorReverse = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Percentage::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::PERCENTAGE)]
		private States\Percentage|null $percentage = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\PowerState::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::POWER)]
		private States\PowerState|null $power = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Press::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::PRESS)]
		private States\Press|null $press = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Rssi::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::RSSI)]
		private States\Rssi|null $rssi = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Startup::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::STARTUP)]
		private States\Startup|null $startup = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(States\Temperature::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::TEMPERATURE)]
		private States\Temperature|null $temperature = null,
		#[ObjectMapper\Rules\ArrayOf(
			item: new ObjectMapper\Rules\MappedObjectValue(States\ToggleState::class),
			key: new ObjectMapper\Rules\AnyOf([
				new ObjectMapper\Rules\StringValue(),
				new ObjectMapper\Rules\IntValue(),
			]),
		)]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::TOGGLE)]
		private array $toggle = [],
	)
	{
	}

	/**
	 * @return array<string, States\State>
	 */
	public function getStates(): array
	{
		$states = [];

		foreach (Types\Capability::getAvailableValues() as $capability) {
			assert(is_string($capability));

			switch ($capability) {
				case Types\Capability::POWER:
					if ($this->power !== null) {
						$states[$capability] = $this->power;
					}

					break;
				case Types\Capability::TOGGLE:
					foreach ($this->toggle as $identifier => $state) {
						$states[sprintf('%s_%s', $capability, strval($identifier))] = $state;
					}

					break;
				case Types\Capability::BRIGHTNESS:
					if ($this->brightness !== null) {
						$states[$capability] = $this->brightness;
					}

					break;
				case Types\Capability::COLOR_TEMPERATURE:
					if ($this->colorTemperature !== null) {
						$states[$capability] = $this->colorTemperature;
					}

					break;
				case Types\Capability::COLOR_RGB:
					if ($this->colorRgb !== null) {
						$states[$capability] = $this->colorRgb;
					}

					break;
				case Types\Capability::PERCENTAGE:
					if ($this->percentage !== null) {
						$states[$capability] = $this->percentage;
					}

					break;
				case Types\Capability::MOTOR_CONTROL:
					if ($this->motorControl !== null) {
						$states[$capability] = $this->motorControl;
					}

					break;
				case Types\Capability::MOTOR_REVERSE:
					if ($this->motorReverse !== null) {
						$states[$capability] = $this->motorReverse;
					}

					break;
				case Types\Capability::MOTOR_CALIBRATION:
					if ($this->motorCalibration !== null) {
						$states[$capability] = $this->motorCalibration;
					}

					break;
				case Types\Capability::STARTUP:
					if ($this->startup !== null) {
						$states[$capability] = $this->startup;
					}

					break;
				case Types\Capability::CAMERA_STREAM:
					if ($this->cameraStream !== null) {
						$states[$capability] = $this->cameraStream;
					}

					break;
				case Types\Capability::DETECT:
					if ($this->detect !== null) {
						$states[$capability] = $this->detect;
					}

					break;
				case Types\Capability::HUMIDITY:
					if ($this->humidity !== null) {
						$states[$capability] = $this->humidity;
					}

					break;
				case Types\Capability::TEMPERATURE:
					if ($this->temperature !== null) {
						$states[$capability] = $this->temperature;
					}

					break;
				case Types\Capability::BATTERY:
					if ($this->battery !== null) {
						$states[$capability] = $this->battery;
					}

					break;
				case Types\Capability::PRESS:
					if ($this->press !== null) {
						$states[$capability] = $this->press;
					}

					break;
				case Types\Capability::RSSI:
					if ($this->rssi !== null) {
						$states[$capability] = $this->rssi;
					}

					break;
			}
		}

		return $states;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_map(
			static fn (States\State $state): array => $state->toArray(),
			$this->getStates(),
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

						foreach ($this->toggle as $name => $state) {
							$json->{$capability}->{$name} = $state->toJson();
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
