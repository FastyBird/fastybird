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

use FastyBird\Connector\HomeKit\Protocol as HomeKitProtocol;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;
use function assert;

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

	public function recalculateValues(HomeKitProtocol\Characteristics\Characteristic $characteristic): void
	{
		if ($characteristic->getName() === HomeKitTypes\CharacteristicType::TARGET_VISIBILITY_STATE->value) {
			$currentVisibilityState = $this->findCharacteristic(
				HomeKitTypes\CharacteristicType::CURRENT_VISIBILITY_STATE,
			);
			assert($currentVisibilityState instanceof HomeKitProtocol\Characteristics\Characteristic);

			$currentVisibilityState->setExpectedValue($characteristic->getValue());
		}
	}

}
