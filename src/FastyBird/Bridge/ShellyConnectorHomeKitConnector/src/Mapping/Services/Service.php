<?php declare(strict_types = 1);

/**
 * Service.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Mapping
 * @since          1.0.0
 *
 * @date           19.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping\Services;

use FastyBird\Bridge\ShellyConnectorHomeKitConnector\Mapping;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;

/**
 * Basic service interface
 *
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Mapping
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Service extends Mapping\Mapping
{

	public function getType(): HomeKitTypes\ServiceType;

	/**
	 * @return class-string
	 */
	public function getClass(): string;

	public function getCategory(): HomeKitTypes\AccessoryCategory;

	public function getChannel(): string|null;

	public function getIndexStart(): int|null;

	public function isMultiple(): bool;

	/**
	 * @return array<Mapping\Characteristics\Characteristic>
	 */
	public function getCharacteristics(): array;

}
