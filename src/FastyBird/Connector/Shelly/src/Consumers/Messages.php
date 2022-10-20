<?php declare(strict_types = 1);

/**
 * Messages.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Consumers
 * @since          0.37.0
 *
 * @date           16.07.22
 */

namespace FastyBird\Connector\Shelly\Consumers;

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Library\Metadata;
use Nette;
use Psr\Log;
use SplObjectStorage;
use SplQueue;
use function count;
use function sprintf;

/**
 * Clients message consumer proxy
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Messages
{

	use Nette\SmartObject;

	/** @var SplObjectStorage<Consumer, null> */
	private SplObjectStorage $consumers;

	/** @var SplQueue<Entities\Messages\Entity> */
	private SplQueue $queue;

	private Log\LoggerInterface $logger;

	/**
	 * @param Array<Consumer> $consumers
	 */
	public function __construct(
		array $consumers,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->consumers = new SplObjectStorage();
		$this->queue = new SplQueue();

		$this->logger = $logger ?? new Log\NullLogger();

		foreach ($consumers as $consumer) {
			$this->consumers->attach($consumer);
		}

		$this->logger->debug(
			sprintf('Registered %d messages consumers', count($this->consumers)),
			[
				'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
				'type' => 'consumer',
			],
		);
	}

	public function append(Entities\Messages\Entity $entity): void
	{
		$this->queue->enqueue($entity);

		$this->logger->debug(
			'Appended new message into consumers queue',
			[
				'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
				'type' => 'consumer',
				'message' => $entity->toArray(),
			],
		);
	}

	public function consume(): void
	{
		$this->queue->rewind();

		if ($this->queue->isEmpty()) {
			return;
		}

		$this->consumers->rewind();

		if ($this->consumers->count() === 0) {
			$this->logger->error(
				'No consumer is registered, messages could not be consumed',
				[
					'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
					'type' => 'consumer',
				],
			);

			return;
		}

		$entity = $this->queue->dequeue();

		foreach ($this->consumers as $consumer) {
			if ($consumer->consume($entity) === true) {
				return;
			}
		}

		$this->logger->error(
			'Message could not be consumed',
			[
				'source' => Metadata\Constants::CONNECTOR_SHELLY_SOURCE,
				'type' => 'consumer',
				'message' => $entity->toArray(),
			],
		);
	}

	public function isEmpty(): bool
	{
		return $this->queue->isEmpty();
	}

}
