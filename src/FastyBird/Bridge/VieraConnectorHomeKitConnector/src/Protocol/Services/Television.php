<?php declare(strict_types = 1);

/**
 * Television.php
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

use FastyBird\Bridge\VieraConnectorHomeKitConnector;
use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Connector\Viera\Types as VieraTypes;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use function intval;

/**
 * Viera television service
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Television extends HomeKitProtocol\Services\Generic
{

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 */
	public function getCharacteristics(): array
	{
		$characteristics = parent::getCharacteristics();

		foreach ($characteristics as $characteristic) {
			if ($characteristic->getName() === HomeKitTypes\CharacteristicType::ACTIVE_IDENTIFIER->value) {
				if ($characteristic->getValue() === null) {
					$characteristic->setValue(
						VieraConnectorHomeKitConnector\Constants::DEFAULT_ACTIVE_IDENTIFIER,
					);
				} else {
					$characteristic->setValue(
						intval(MetadataUtilities\Value::toString($characteristic->getValue(), true)),
					);
				}
			}
		}

		return $characteristics;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 */
	public function recalculateValues(
		HomeKitProtocol\Characteristics\Characteristic $characteristic,
		bool $fromDevice,
	): void
	{
		if ($characteristic->getName() === HomeKitTypes\CharacteristicType::POWER_MODE_SELECTION->value) {
			if (MetadataUtilities\Value::toString($characteristic->getValue()) === '0') {
				$characteristic->setValue('0');
			} else {
				$characteristic->setValue(null);
			}
		} elseif ($characteristic->getName() === HomeKitTypes\CharacteristicType::REMOTE_KEY->value) {
			if ($characteristic->getValue() === 0) {
				$characteristic->setValue(VieraTypes\ActionKey::REWIND->value);

			} elseif ($characteristic->getValue() === 1) {
				$characteristic->setValue(VieraTypes\ActionKey::FAST_FORWARD->value);

			} elseif ($characteristic->getValue() === 2) {
				$characteristic->setValue(VieraTypes\ActionKey::SKIP_NEXT->value);

			} elseif ($characteristic->getValue() === 3) {
				$characteristic->setValue(VieraTypes\ActionKey::SKIP_PREV->value);

			} elseif ($characteristic->getValue() === 4) {
				$characteristic->setValue(VieraTypes\ActionKey::UP->value);

			} elseif ($characteristic->getValue() === 5) {
				$characteristic->setValue(VieraTypes\ActionKey::DOWN->value);

			} elseif ($characteristic->getValue() === 6) {
				$characteristic->setValue(VieraTypes\ActionKey::LEFT->value);

			} elseif ($characteristic->getValue() === 7) {
				$characteristic->setValue(VieraTypes\ActionKey::RIGHT->value);

			} elseif ($characteristic->getValue() === 8) {
				$characteristic->setValue(VieraTypes\ActionKey::ENTER->value);

			} elseif ($characteristic->getValue() === 9) {
				$characteristic->setValue(VieraTypes\ActionKey::CANCEL->value);

			} elseif ($characteristic->getValue() === 10) {
				$characteristic->setValue(VieraTypes\ActionKey::CANCEL->value);

			} elseif ($characteristic->getValue() === 11) {
				$characteristic->setValue(VieraTypes\ActionKey::PLAY->value);

			} elseif ($characteristic->getValue() === 15) {
				$characteristic->setValue(VieraTypes\ActionKey::INFO->value);

			} else {
				$characteristic->setValue(null);
			}
		}
	}

}
