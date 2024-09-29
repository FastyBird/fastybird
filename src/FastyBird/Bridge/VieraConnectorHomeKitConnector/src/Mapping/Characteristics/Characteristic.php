<?php declare(strict_types = 1);

/**
 * Characteristic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           25.08.24
 */

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping\Characteristics;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;

/**
 * Basic characteristic interface
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
 * @subpackage     Mapping
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Characteristic extends Mapping\Mapping
{

	public function getType(): HomeKitTypes\CharacteristicType;

	public function getChannel(): string|null;

	public function getProperty(): string|null;

	public function isNullable(): bool;

	/**
	 * @return array<HomeKitTypes\CharacteristicType>
	 */
	public function getRequire(): array;

	/**
	 * @return string|array<int, string>|array<int, bool|string|int|float|array<int, bool|string|int|float>|null>|array<int, array<int, string|array<int, string|int|float|bool>|null>>|null
	 */
	public function getFormat(): string|array|null;

	public function getValue(): float|bool|int|string|null;

}
