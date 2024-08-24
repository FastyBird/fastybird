<?php declare(strict_types = 1);

/**
 * Valve.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           23.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Protocol\Services;

use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;

/**
 * Shelly valve service
 *
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Valve extends HomeKitProtocol\Services\Generic
{

	public function recalculateValues(
		HomeKitProtocol\Characteristics\Characteristic $characteristic,
		bool $fromDevice,
	): void
	{
		$inUseCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::OUTLET_INUSE);

		if (is_numeric($inUseCharacteristic?->getValue())) {
			$inUseCharacteristic->setValue($inUseCharacteristic->getValue() > 0);
		} elseif ($inUseCharacteristic?->getValue() === null) {
			$inUseCharacteristic?->setValue(false);
		}
	}

}
