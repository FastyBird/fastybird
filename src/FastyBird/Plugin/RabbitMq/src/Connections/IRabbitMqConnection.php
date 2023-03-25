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

	/**
	 * @return string
	 */
	public function getHost(): string;

	/**
	 * @return int
	 */
	public function getPort(): int;

	/**
	 * @return string
	 */
	public function getVhost(): string;

	/**
	 * @return string
	 */
	public function getUsername(): string;

	/**
	 * @return string
	 */
	public function getPassword(): string;

	/**
	 * @param bool $force
	 *
	 * @return Bunny\Client
	 */
	public function getClient(bool $force = false): Bunny\Client;

	/**
	 * @param bool $force
	 *
	 * @return Bunny\Async\Client
	 */
	public function getAsyncClient(bool $force = false): Bunny\Async\Client;

	/**
	 * @param Bunny\Channel $channel
	 *
	 * @return void
	 */
	public function setChannel(Bunny\Channel $channel): void;

	/**
	 * @return Bunny\Channel
	 */
	public function getChannel(): Bunny\Channel;

}
