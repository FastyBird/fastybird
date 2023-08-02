<?php declare(strict_types = 1);

/**
 * Category.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Types;

use Consistence;
use function strval;

/**
 * Device category types
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Category extends Consistence\Enum\Enum
{

	/**
	 * Define types
	 */
	public const UNKNOWN = 'unknown';

	public const PLUG = 'plug';

	public const SWITCH = 'switch';

	public const LIGHT = 'light';

	public const CURTAIN = 'curtain';

	public const CONTACT_SENSOR = 'contactSensor';

	public const MOTION_SENSOR = 'motionSensor';

	public const TEMPERATURE_SENSOR = 'temperatureSensor';

	public const HUMIDITY_SENSOR = 'humiditySensor';

	public const TEMPERATURE_HUMIDITY_SENSOR = 'temperatureAndHumiditySensor';

	public const WATTER_LEAK_DETECTOR = 'waterLeakDetector';

	public const SMOKE_DETECTOR = 'smokeDetector';

	public const BUTTON = 'button';

	public const CAMERA = 'camera';

	public const SENSOR = 'sensor';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	/**
	 * @return array<string>
	 */
	public static function getValues(): array
	{
		/** @var iterable<string> $availableValues */
		$availableValues = parent::getAvailableValues();

		return (array) $availableValues;
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
