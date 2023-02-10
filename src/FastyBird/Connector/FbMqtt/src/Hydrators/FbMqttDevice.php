<?php declare(strict_types = 1);

/**
 * FbMqttDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           05.02.22
 */

namespace FastyBird\Connector\FbMqtt\Hydrators;

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * FastyBird MQTT device entity hydrator
 *
 * @extends DevicesHydrators\Devices\Device<Entities\FbMqttDevice>
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class FbMqttDevice extends DevicesHydrators\Devices\Device
{

	public function getEntityName(): string
	{
		return Entities\FbMqttDevice::class;
	}

}
