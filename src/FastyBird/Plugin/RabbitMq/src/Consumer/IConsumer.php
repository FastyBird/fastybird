<?php declare(strict_types = 1);

/**
 * IConsumer.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Consumer
 * @since          1.0.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Plugin\RabbitMq\Consumer;

use Bunny;
use FastyBird\ApplicationExchange\Consumer as ApplicationExchangeConsumer;

/**
 * Exchange messages consumer interface
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Consumer
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IConsumer
{

	public const MESSAGE_ACK = 1;

	public const MESSAGE_NACK = 2;

	public const MESSAGE_REJECT = 3;

	public const MESSAGE_REJECT_AND_TERMINATE = 4;

	public function setQueueName(string|null $queueName): void;

	public function getQueueName(): string|null;

	public function registerConsumer(ApplicationExchangeConsumer\IConsumer $consumer): void;

	public function consume(Bunny\Message $message): int;

}
