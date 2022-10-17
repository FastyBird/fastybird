<?php declare(strict_types = 1);

/**
 * Consumer.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Consumers
 * @since          0.37.0
 *
 * @date           16.07.22
 */

namespace FastyBird\Connector\Shelly\Consumers;

use FastyBird\Connector\Shelly\Entities;

/**
 * Clients messages consumer interface
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Consumer
{

	public function consume(Entities\Messages\Entity $entity): bool;

}
