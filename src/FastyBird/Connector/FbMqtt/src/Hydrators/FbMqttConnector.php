<?php declare(strict_types = 1);

/**
 * FbMqtt.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           07.12.21
 */

namespace FastyBird\Connector\FbMqtt\Hydrators;

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * FastyBird MQTT Connector entity hydrator
 *
 * @extends DevicesHydrators\Connectors\Connector<Entities\FbMqttConnector>
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class FbMqttConnector extends DevicesHydrators\Connectors\Connector
{

	public function getEntityName(): string
	{
		return Entities\FbMqttConnector::class;
	}

}
