<?php declare(strict_types = 1);

/**
 * Relay.php
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
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use function is_numeric;

/**
 * Shelly switch service
 *
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Relay extends HomeKitProtocol\Services\Generic
{

	public function recalculateCharacteristics(): void
	{
		$inUseCharacteristic = $this->findCharacteristic(HomeKitTypes\CharacteristicType::OUTLET_INUSE);

		if ($inUseCharacteristic !== null) {
			if ($inUseCharacteristic->getProperty() instanceof DevicesDocuments\Channels\Properties\Variable) {
				$inUseCharacteristic->setActualValue(true);
				$inUseCharacteristic->setExpectedValue(null);
			} else {
				if (is_numeric($inUseCharacteristic->getValue())) {
					$inUseCharacteristic->setActualValue($inUseCharacteristic->getValue() > 0);
					$inUseCharacteristic->setExpectedValue(null);
				} else {
					$inUseCharacteristic->setActualValue(false);
					$inUseCharacteristic->setExpectedValue(null);
				}
			}
		}
	}

}
