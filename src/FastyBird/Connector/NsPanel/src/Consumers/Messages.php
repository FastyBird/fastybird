<?php declare(strict_types = 1);

/**
 * Messages.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Consumers;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;
use Psr\Log;
use SplObjectStorage;
use SplQueue;
use function count;
use function sprintf;

/**
 * Clients message consumer proxy
 *
 * @package        FastyBird:NsPanelConnector!
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
	 * @param array<Consumer> $consumers
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
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
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
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
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

		$entity = $this->queue->dequeue();

		$this->consumers->rewind();

		if ($this->consumers->count() === 0) {
			$this->logger->error(
				'No consumer is registered, messages could not be consumed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
					'type' => 'consumer',
				],
			);

			return;
		}

		foreach ($this->consumers as $consumer) {
			if ($consumer->consume($entity) === true) {
				return;
			}
		}

		$this->logger->error(
			'Message could not be consumed',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_VIERA,
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
