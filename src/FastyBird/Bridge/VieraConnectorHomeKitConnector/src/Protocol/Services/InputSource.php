<?php declare(strict_types = 1);

/**
 * InputSource.php
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
use FastyBird\Bridge\VieraConnectorHomeKitConnector\Exceptions;
use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use function assert;
use function intval;
use function is_int;
use function sprintf;

/**
 * Viera input source service
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class InputSource extends HomeKitProtocol\Services\Generic
{

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 */
	public function getCharacteristics(): array
	{
		$characteristics = parent::getCharacteristics();

		$identifier = $this->findCharacteristic(HomeKitTypes\CharacteristicType::IDENTIFIER);

		if ($identifier === null) {
			throw new Exceptions\InvalidState(sprintf(
				'Required characteristic: %s is missing in service: %s',
				HomeKitTypes\CharacteristicType::IDENTIFIER->value,
				$this->getType()->value,
			));
		}

		$type = $identifier->getValue();

		assert(is_int($type));

		$inputSource = $this->findCharacteristic(HomeKitTypes\CharacteristicType::INPUT_SOURCE);

		$currentVisibilityState = $this->findCharacteristic(HomeKitTypes\CharacteristicType::CURRENT_VISIBILITY_STATE);

		if ($currentVisibilityState !== null) {
			$currentVisibilityState->setValue(
				$inputSource?->getValue() === null
					? ($type === VieraConnectorHomeKitConnector\Constants::DEFAULT_ACTIVE_IDENTIFIER ? 0 : 1) // 0 = Shown, 1 = Hidden
					: (intval(
						MetadataUtilities\Value::toString($inputSource->getValue()),
					) === $type ? 0 : 1), // 0 = Shown, 1 = Hidden
			);
		}

		return $characteristics;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 */
	public function recalculateValues(
		HomeKitProtocol\Characteristics\Characteristic $characteristic,
		bool $fromDevice,
	): void
	{
		$identifier = $this->findCharacteristic(HomeKitTypes\CharacteristicType::IDENTIFIER);

		if ($identifier === null) {
			throw new Exceptions\InvalidState(sprintf(
				'Required characteristic: %s is missing in service: %s',
				HomeKitTypes\CharacteristicType::IDENTIFIER->value,
				$this->getType()->value,
			));
		}

		$type = $identifier->getValue();

		assert(is_int($type));

		if ($characteristic->getName() === HomeKitTypes\CharacteristicType::TARGET_VISIBILITY_STATE->value) {
			$inputSource = $this->findCharacteristic(HomeKitTypes\CharacteristicType::INPUT_SOURCE);

			if ($characteristic->getValue() === 0) {
				$inputSource?->setValue($type);
			}
		} elseif ($characteristic->getName() === HomeKitTypes\CharacteristicType::INPUT_SOURCE->value) {
			$currentVisibilityState = $this->findCharacteristic(
				HomeKitTypes\CharacteristicType::CURRENT_VISIBILITY_STATE,
			);

			if ($characteristic->getValue() === null) {
				$currentVisibilityState?->setValue(
					$type === VieraConnectorHomeKitConnector\Constants::DEFAULT_ACTIVE_IDENTIFIER ? 0 : 1,
				);

			} else {
				$currentVisibilityState?->setValue(
					intval(MetadataUtilities\Value::toString($characteristic->getValue())) === $type ? 0 : 1,
				);
			}
		}
	}

}
