<?php declare(strict_types = 1);

/**
 * WsApiFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           08.01.23
 */

namespace FastyBird\Connector\Shelly\API;

/**
 * Websockets API factory
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface WsApiFactory
{

	public function create(
		string $identifier,
		string|null $ipAddress,
		string|null $domain,
		string|null $username,
		string|null $password,
	): WsApi;

}
