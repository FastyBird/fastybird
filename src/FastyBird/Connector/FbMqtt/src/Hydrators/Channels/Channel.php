<?php declare(strict_types = 1);

/**
 * Channel.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           07.01.24
 */

namespace FastyBird\Connector\FbMqtt\Hydrators\Channels;

use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Module\Devices\Hydrators as DevicesHydrators;

/**
 * FastyBird MQTT channel entity hydrator
 *
 * @extends DevicesHydrators\Channels\Channel<Entities\Channels\Channel>
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Channel extends DevicesHydrators\Channels\Channel
{

	public function getEntityName(): string
	{
		return Entities\Channels\Channel::class;
	}

}
