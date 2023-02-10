<?php declare(strict_types = 1);

/**
 * Writer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           18.01.23
 */

namespace FastyBird\Connector\FbMqtt\Writers;

use FastyBird\Connector\FbMqtt\Clients;
use FastyBird\Connector\FbMqtt\Entities;

/**
 * Properties writer interface
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Writer
{

	public function connect(
		Entities\FbMqttConnector $connector,
		Clients\Client $client,
	): void;

	public function disconnect(
		Entities\FbMqttConnector $connector,
		Clients\Client $client,
	): void;

}
