<?php declare(strict_types = 1);

/**
 * Entity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 * @since          0.37.0
 *
 * @date           16.07.22
 */

namespace FastyBird\Connector\Shelly\Entities\Messages;

use FastyBird\Connector\Shelly\Types;

/**
 * Shelly base message data entity interface
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Properties
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Entity
{

	public function getSource(): Types\MessageSource;

	/**
	 * @return Array<string, mixed>
	 */
	public function toArray(): array;

}
