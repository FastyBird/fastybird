<?php declare(strict_types = 1);

/**
 * IRabbitMqConnection.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Connections
 * @since          1.0.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Plugin\RabbitMq\Connections;

use Bunny;

/**
 * RabbitMQ connection configuration interface
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Connections
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IRabbitMqConnection
{

	public function getHost(): string;

	public function getPort(): int;

	public function getVhost(): string;

	public function getUsername(): string;

	public function getPassword(): string;

	public function getClient(bool $force = false): Bunny\Client;

	public function getAsyncClient(bool $force = false): Bunny\Async\Client;

	public function setChannel(Bunny\Channel $channel): void;

	public function getChannel(): Bunny\Channel;

}
