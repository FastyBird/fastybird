<?php declare(strict_types = 1);

/**
 * ClientFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           05.07.22
 */

namespace FastyBird\Connector\FbMqtt\API;

/**
 * Base client factory
 *
 * @package        FastyBird:FbMqttConnector!
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
