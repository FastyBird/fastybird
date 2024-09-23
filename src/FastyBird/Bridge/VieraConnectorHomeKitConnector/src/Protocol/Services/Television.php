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

use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use TypeError;
use ValueError;
use function array_unique;

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
	public function recalculateValues(HomeKitProtocol\Characteristics\Characteristic $characteristic): void
	{
		if ($characteristic->getName() === HomeKitTypes\CharacteristicType::POWER_MODE_SELECTION->value) {
			if ($characteristic->getValue() === MetadataTypes\Payloads\Button::CLICKED->value) {
				$characteristic->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);
			} else {
				$characteristic->setExpectedValue(null);
			}
		} elseif ($characteristic->getName() === HomeKitTypes\CharacteristicType::REMOTE_KEY->value) {
			if ($characteristic->getValue() !== null) {
				if (MetadataUtilities\Value::toString($characteristic->getValue()) === '0') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_REWIND,
					);
					$remoteKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);

				} elseif (MetadataUtilities\Value::toString($characteristic->getValue()) === '1') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_FAST_FORWARD,
					);
					$remoteKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);

				} elseif (MetadataUtilities\Value::toString($characteristic->getValue()) === '2') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_NEXT_TRACK,
					);
					$remoteKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);

				} elseif (MetadataUtilities\Value::toString($characteristic->getValue()) === '3') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_PREVIOUS_TRACK,
					);
					$remoteKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);

				} elseif (MetadataUtilities\Value::toString($characteristic->getValue()) === '4') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_ARROW_UP,
					);
					$remoteKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);

				} elseif (MetadataUtilities\Value::toString($characteristic->getValue()) === '5') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_ARROW_DOWN,
					);
					$remoteKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);

				} elseif (MetadataUtilities\Value::toString($characteristic->getValue()) === '6') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_ARROW_LEFT,
					);
					$remoteKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);

				} elseif (MetadataUtilities\Value::toString($characteristic->getValue()) === '7') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_ARROW_RIGHT,
					);
					$remoteKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);

				} elseif (MetadataUtilities\Value::toString($characteristic->getValue()) === '8') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_SELECT,
					);
					$remoteKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);

				} elseif (MetadataUtilities\Value::toString($characteristic->getValue()) === '9') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_EXIT,
					);
					$remoteKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);

				} elseif (MetadataUtilities\Value::toString($characteristic->getValue()) === '10') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_EXIT,
					);
					$remoteKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);

				} elseif (MetadataUtilities\Value::toString($characteristic->getValue()) === '11') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_PLAY_PAUSE,
					);
					$remoteKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);

				} elseif (MetadataUtilities\Value::toString($characteristic->getValue()) === '15') {
					$remoteKeyCharacteristic = $this->findCharacteristic(
						HomeKitTypes\CharacteristicType::REMOTE_KEY_INFORMATION,
					);
					$remoteKeyCharacteristic?->setExpectedValue(MetadataTypes\Payloads\Button::CLICKED->value);
				}
			}

			$characteristic->setActualValue(null);
			$characteristic->setExpectedValue(null);
		}
	}

	/**
	 * Create a HAP representation of Television Service
	 *
	 * @return array<string, array<array<string, array<int|string>|bool|float|int|string|null>|int|null>|bool|int|string|null>
	 *
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function toHap(): array
	{
		$hapRepresentation = parent::toHap();

		$linkedServices = [];

		$inputSources = $this->getAccessory()->findServices(HomeKitTypes\ServiceType::INPUT_SOURCE);

		foreach ($inputSources as $inputSource) {
			$linkedServices[] = $this->getAccessory()->getIidManager()->getIid($inputSource);
		}

		$speakers = $this->getAccessory()->findServices(HomeKitTypes\ServiceType::TELEVISION_SPEAKER);

		foreach ($speakers as $speaker) {
			$linkedServices[] = $this->getAccessory()->getIidManager()->getIid($speaker);
		}

		$hapRepresentation[HomeKitTypes\Representation::LINKED->value] = array_unique($linkedServices);

		return $hapRepresentation;
	}

}
