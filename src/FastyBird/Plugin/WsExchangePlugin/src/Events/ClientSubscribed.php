<?php declare(strict_types = 1);

/**
 * ClientSubscribed.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Events
 * @since          0.11.0
 *
 * @date           08.06.22
 */

namespace FastyBird\WsExchangePlugin\Events;

use IPub\WebSocketsWAMP;

/**
 * WS client subscribed to topic event
 *
 * @package        FastyBird:WsExchangePlugin!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ClientSubscribed
{

	/**
	 * @phpstan-param WebSocketsWAMP\Entities\Topics\ITopic<mixed> $topic
	 */
	public function __construct(
		private readonly WebSocketsWAMP\Entities\Clients\IClient $client,
		private readonly WebSocketsWAMP\Entities\Topics\ITopic $topic,
	)
	{
	}

	public function getClient(): WebSocketsWAMP\Entities\Clients\IClient
	{
		return $this->client;
	}

	/**
	 * @return WebSocketsWAMP\Entities\Topics\ITopic<mixed>
	 */
	public function getTopic(): WebSocketsWAMP\Entities\Topics\ITopic
	{
		return $this->topic;
	}

}
