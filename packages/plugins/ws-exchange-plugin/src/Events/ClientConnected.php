<?php declare(strict_types = 1);

/**
 * ClientConnected.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Events
 * @since          0.2.0
 *
 * @date           15.01.22
 */

namespace FastyBird\WsExchangePlugin\Events;

use IPub\WebSockets;

/**
 * WS client connected to server event
 *
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ClientConnected
{

	public function __construct(
		private readonly WebSockets\Entities\Clients\IClient $client,
		private readonly WebSockets\Http\IRequest $httpRequest,
	)
	{
	}

	public function getClient(): WebSockets\Entities\Clients\IClient
	{
		return $this->client;
	}

	public function getHttpRequest(): WebSockets\Http\IRequest
	{
		return $this->httpRequest;
	}

}
