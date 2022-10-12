<?php declare(strict_types = 1);

/**
 * FbMqttConnector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Hydrators
 * @since          0.4.0
 *
 * @date           07.12.21
 */

namespace FastyBird\FbMqttConnector\Hydrators;

use FastyBird\DevicesModule\Hydrators as DevicesModuleHydrators;
use FastyBird\FbMqttConnector\Entities;

/**
 * FastyBird MQTT Connector entity hydrator
 *
 * @phpstan-extends DevicesModuleHydrators\Connectors\Connector<Entities\FbMqttConnector>
 *
 * @package        FastyBird:FbMqttConnector!
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
