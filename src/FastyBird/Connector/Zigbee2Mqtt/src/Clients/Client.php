<?php declare(strict_types = 1);

/**
 * Client.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           23.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\Clients;

use React\Promise;

/**
 * Base device client interface
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Client
{

	/**
	 * Create client
	 *
	 * @return Promise\PromiseInterface<mixed>
	 */
	public function connect(): Promise\PromiseInterface;

	/**
	 * Destroy client
	 *
	 * @return Promise\PromiseInterface<mixed>
	 */
	public function disconnect(): Promise\PromiseInterface;

}
