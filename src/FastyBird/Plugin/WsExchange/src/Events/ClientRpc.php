<?php declare(strict_types = 1);

/**
 * ClientRpc.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsExchange!
 * @subpackage     Events
 * @since          0.12.0
 *
 * @date           07.07.22
 */

namespace FastyBird\Plugin\WsExchange\Events;

use IPub\WebSocketsWAMP;

/**
 * WS client called RPC to topic event
 *
 * @package        FastyBird:WsExchange!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ClientRpc
{

	/**
	 * @phpstan-param WebSocketsWAMP\Entities\Topics\ITopic<mixed> $topic
	 * @phpstan-param Array<mixed> $args
	 */
	public function __construct(
		private readonly WebSocketsWAMP\Entities\Clients\IClient $client,
		private readonly WebSocketsWAMP\Entities\Topics\ITopic $topic,
		private readonly array $args,
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

	/**
	 * @return Array<mixed>
	 */
	public function getArgs(): array
	{
		return $this->args;
	}

}
