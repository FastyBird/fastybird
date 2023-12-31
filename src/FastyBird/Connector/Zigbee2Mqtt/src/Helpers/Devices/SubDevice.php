<?php declare(strict_types = 1);

/**
 * SubDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Helpers
 * @since          1.0.0
 *
 * @date           01.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Helpers\Devices;

use FastyBird\Connector\Zigbee2Mqtt\Entities;
use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;

/**
 * Sub device helper
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SubDevice
{

	public function __construct(
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 */
	public function getBridge(MetadataDocuments\DevicesModule\Device $device): MetadataDocuments\DevicesModule\Device
	{
		foreach ($device->getParents() as $parent) {
			$findDeviceQuery = new DevicesQueries\Configuration\FindDevices();
			$findDeviceQuery->byId($parent);
			$findDeviceQuery->byType(Entities\Devices\Bridge::TYPE);

			$parent = $this->devicesConfigurationRepository->findOneBy($findDeviceQuery);

			if ($parent !== null) {
				return $parent;
			}
		}

		throw new Exceptions\InvalidState('Sub-device have to have parent bridge defined');
	}

}
