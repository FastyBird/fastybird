<?php declare(strict_types = 1);

/**
 * Thermostat.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           12.04.23
 */

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Protocol\Services;

use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;

/**
 * HAP thermostat service
 *
 * @package        FastyBird:VirtualThermostatAddonHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Thermostat extends HomeKitProtocol\Services\Generic
{

	public function getCharacteristics(): array
	{
		$characteristics = parent::getCharacteristics();

		foreach ($characteristics as $characteristic) {
			if (
				$characteristic->getName() === HomeKitTypes\CharacteristicType::TEMPERATURE_DISPLAY_UNITS->value
				&& $characteristic->getValue() === null
			) {
				$validValues = $characteristic->getValidValues();

				$characteristic->setActualValue($validValues !== null ? $validValues[0] : null);
			}
		}

		return $characteristics;
	}

}
