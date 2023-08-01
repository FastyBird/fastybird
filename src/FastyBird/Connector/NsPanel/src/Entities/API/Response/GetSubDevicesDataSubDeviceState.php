<?php declare(strict_types = 1);

/**
 * GetSubDevicesDataSubDeviceState.php
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

namespace FastyBird\Connector\NsPanel\Entities\API\Response;

use FastyBird\Connector\NsPanel\Consumers\Messages\Status;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;
use function Clue\StreamFilter\fun;

/**
 * Sub-device state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class GetSubDevicesDataSubDeviceState implements Entities\API\Entity, ObjectMapper\MappedObject
{

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
			new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\Power::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName(Types\Capability::POWER)]
		private readonly Entities\API\Statuses\Power|null $power = null,
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
			item: new ObjectMapper\Rules\MappedObjectValue(Entities\API\Statuses\Toggle::class),
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
	 * @return array<Entities\API\Statuses\NamedStatus>
	 */
	public function getStatuses(): array
	{
		$statuses = [];

		foreach (Types\Capability::getAvailableValues() as $capability) {
			assert(is_string($capability));

			if ($capability === Types\Capability::TOGGLE) {
				foreach ($this->toggle as $name => $status) {
					$statuses[] = new Entities\API\Statuses\NamedStatus(strval($name), $status);
				}
			} else {
				if (property_exists($this, $capability) && $this->{$capability} instanceof Entities\API\Statuses\Status) {
					$statuses[] = new Entities\API\Statuses\NamedStatus(null, $this->{$capability});
				}
			}
		}

		return $statuses;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_map(function (Entities\API\Statuses\Status $status): array {
			return $status->toArray();
		}, $this->getStatuses());
	}

	public function toJson(): object
	{
		$json = new stdClass();

		foreach (Types\Capability::getAvailableValues() as $capability) {
			assert(is_string($capability));

			if (property_exists($this, $capability) && $this->{$capability} instanceof Entities\API\Statuses\Status) {
				$json->{$capability} = $this->{$capability}->toJson();
			}
		}

		return $json;
	}

}
