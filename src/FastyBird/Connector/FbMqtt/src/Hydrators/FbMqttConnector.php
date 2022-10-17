<?php declare(strict_types = 1);

/**
 * FbMqtt.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqtt!
 * @subpackage     Hydrators
 * @since          0.4.0
 *
 * @date           07.12.21
 */

namespace FastyBird\Connector\FbMqtt\Hydrators;

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\DevicesModule\Hydrators as DevicesModuleHydrators;

/**
 * FastyBird MQTT Connector entity hydrator
 *
 * @phpstan-extends DevicesModuleHydrators\Connectors\Connector<Entities\FbMqttConnector>
 *
 * @package        FastyBird:FbMqtt!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class FbMqttConnector extends DevicesModuleHydrators\Connectors\Connector
{

	public function getEntityName(): string
	{
		return Entities\FbMqttConnector::class;
	}

}
