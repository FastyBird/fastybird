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
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;

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

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 */
	public function recalculateValues(HomeKitProtocol\Characteristics\Characteristic $characteristic): void
	{
		if ($characteristic->getName() === HomeKitTypes\CharacteristicType::VOLUME_SELECTOR->value) {
			if ($characteristic->getValue() !== null) {
				if (MetadataUtilities\Value::toString($characteristic->getValue(), true) === '0') {
					$volumeKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_VOLUME_UP,
					);
					$volumeKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);

				} elseif (MetadataUtilities\Value::toString($characteristic->getValue()) === '1') {
					$volumeKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_VOLUME_DOWN,
					);
					$volumeKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);

				} else {
					$characteristic->setActualValue(null);
					$characteristic->setExpectedValue(null);
				}
			}
		}
	}

}
