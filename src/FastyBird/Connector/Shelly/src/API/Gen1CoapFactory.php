<?php declare(strict_types = 1);

/**
 * Gen1CoapFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           25.08.23
 */

namespace FastyBird\Connector\Shelly\API;

/**
 * Generation 1 device CoAP factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Gen1CoapFactory
{

	public function create(): Gen1Coap;

}
