<?php declare(strict_types = 1);

/**
 * Connector.php
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

namespace FastyBird\Connector\FbMqtt\Hydrators\Connectors;

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * FastyBird MQTT connector entity hydrator
 *
 * @extends DevicesHydrators\Connectors\Connector<Entities\Connectors\Connector>
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector extends DevicesHydrators\Connectors\Connector
{

	public function getEntityName(): string
	{
		return Entities\Connectors\Connector::class;
	}

}
