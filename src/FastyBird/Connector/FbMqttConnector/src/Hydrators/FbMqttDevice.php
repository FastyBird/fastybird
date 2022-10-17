<?php declare(strict_types = 1);

/**
 * FbMqttDevice.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Hydrators
 * @since          0.4.0
 *
 * @date           05.02.22
 */

namespace FastyBird\FbMqttConnector\Hydrators;

use FastyBird\DevicesModule\Hydrators as DevicesModuleHydrators;
use FastyBird\FbMqttConnector\Entities;

/**
 * FastyBird MQTT device entity hydrator
 *
 * @phpstan-extends DevicesModuleHydrators\Devices\Device<Entities\FbMqttDevice>
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class FbMqttDevice extends DevicesModuleHydrators\Devices\Device
{

	public function getEntityName(): string
	{
		return Entities\FbMqttDevice::class;
	}

}
