<?php declare(strict_types = 1);

/**
 * ClientFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           23.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt\API;

/**
 * Base client factory
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ClientFactory
{

	public function create(
		string $clientId,
		string $address,
		int $port,
		string|null $username = null,
		string|null $password = null,
	): Client;

}
