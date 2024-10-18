<?php declare(strict_types = 1);

/**
 * DeviceFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 * @since          1.0.0
 *
 * @date           04.10.24
 */

namespace FastyBird\Connector\NsPanel\Protocol\Devices;

use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Mapping;

/**
 * NS panel device factory interface
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Protocol
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface DeviceFactory
{

	public function create(
		Documents\Connectors\Connector $connector,
		Documents\Devices\Gateway $gateway,
		Documents\Devices\Device $device,
		Mapping\Categories\Category $categoryMetadata,
	): Device;

	/**
	 * @return class-string<Entities\Devices\Device>
	 */
	public function getEntityClass(): string;

}
