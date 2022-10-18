<?php declare(strict_types = 1);

/**
 * CoapFactory.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          0.37.0
 *
 * @date           05.07.22
 */

namespace FastyBird\Connector\Shelly\Clients\Gen1;

use FastyBird\Library\Metadata\Entities as MetadataEntities;

/**
 * CoAP client factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface CoapFactory
{

	public function create(MetadataEntities\DevicesModule\Connector $connector): Coap;

}
