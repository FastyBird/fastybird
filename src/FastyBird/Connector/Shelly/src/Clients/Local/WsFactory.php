<?php declare(strict_types = 1);

/**
 * WsFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           10.01.23
 */

namespace FastyBird\Connector\Shelly\Clients\Local;

/**
 * Websockets client factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface WsFactory
{

	public function create(): Ws;

}
