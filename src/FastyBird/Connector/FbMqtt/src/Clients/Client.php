<?php declare(strict_types = 1);

/**
 * Client.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           03.12.23
 */

namespace FastyBird\Connector\FbMqtt\Clients;

/**
 * Base device client interface
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Client
{

	/**
	 * Create client
	 */
	public function connect(): void;

	/**
	 * Destroy client
	 */
	public function disconnect(): void;

}
