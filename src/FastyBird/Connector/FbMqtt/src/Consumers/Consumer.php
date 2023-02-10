<?php declare(strict_types = 1);

/**
 * Consumer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           05.02.22
 */

namespace FastyBird\Connector\FbMqtt\Consumers;

use FastyBird\Connector\FbMqtt\Entities;

/**
 * Exchange messages consumer interface
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Consumer
{

	public function consume(Entities\Messages\Entity $entity): bool;

}
