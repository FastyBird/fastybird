<?php declare(strict_types = 1);

/**
 * TelevisionSpeaker.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           26.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Protocol\Services;

use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Connector\Viera\Types as VieraTypes;

/**
 * Viera television speaker service
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class TelevisionSpeaker extends HomeKitProtocol\Services\Generic
{

	public function recalculateValues(
		HomeKitProtocol\Characteristics\Characteristic $characteristic,
		bool $fromDevice,
	): void
	{
		if ($characteristic->getName() === HomeKitTypes\CharacteristicType::VOLUME_SELECTOR->value) {
			if ($characteristic->getValue() === '0') {
				$characteristic->setValue(VieraTypes\ActionKey::VOLUME_UP->value);

			} elseif ($characteristic->getValue() === '1') {
				$characteristic->setValue(VieraTypes\ActionKey::VOLUME_DOWN->value);

			} else {
				$characteristic->setValue(null);
			}
		}
	}

}
