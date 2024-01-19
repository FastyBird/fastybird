<?php declare(strict_types = 1);

/**
 * Factory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Exchange
 * @since          1.0.0
 *
 * @date           09.10.22
 */

namespace FastyBird\Plugin\RedisDb\Exchange;

use FastyBird\Library\Exchange\Exchange as ExchangeExchange;
use FastyBird\Plugin\RedisDb\Clients;

/**
 * Redis DB exchange factory
 *
 * @package        FastyBird:RedisDbPlugin!
 * @subpackage     Exchange
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Factory implements ExchangeExchange\Factory
{

	public function __construct(
		private readonly string $channel,
		private readonly Clients\Async\Client $client,
		private readonly Handler $messagesHandler,
	)
	{
	}

	public function create(): void
	{
		$this->client->subscribe(
			$this->channel,
			function (string $channel, string $payload): void {
				$this->messagesHandler->handle($payload);
			},
		);
	}

}
