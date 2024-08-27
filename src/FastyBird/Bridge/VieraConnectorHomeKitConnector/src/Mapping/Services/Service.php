<?php declare(strict_types = 1);

/**
 * Service.php
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

namespace FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping\Services;

use FastyBird\Bridge\VieraConnectorHomeKitConnector\Mapping;
use FastyBird\Connector\HomeKit\Types as HomeKitTypes;

/**
 * Basic service interface
 *
 * @package        FastyBird:VieraConnectorHomeKitConnectorBridge!
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

	public function getChannel(): string|null;

	public function isMultiple(): bool;

	/**
	 * @return array<Mapping\Characteristics\Characteristic>
	 */
	public function getCharacteristics(): array;

}
