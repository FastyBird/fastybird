<?php declare(strict_types = 1);

/**
 * Queue.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           30.08.23
 */

namespace FastyBird\Connector\Modbus\Queue;

use FastyBird\Connector\Modbus;
use FastyBird\Connector\Modbus\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;
use SplQueue;

/**
 * Clients message consumer proxy
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Queue
{

	use Nette\SmartObject;

	/** @var SplQueue<Entities\Messages\Entity> */
	private SplQueue $queue;

	public function __construct(private readonly Modbus\Logger $logger)
	{
		$this->queue = new SplQueue();
	}

	public function append(Entities\Messages\Entity $entity): void
	{
		$this->queue->enqueue($entity);

		$this->logger->debug(
			'Appended new message into messages queue',
			[
				'source' => MetadataTypes\ConnectorSource::MODBUS,
				'type' => 'queue',
				'message' => $entity->toArray(),
			],
		);
	}

	public function dequeue(): Entities\Messages\Entity|false
	{
		$this->queue->rewind();

		if ($this->queue->isEmpty()) {
			return false;
		}

		return $this->queue->dequeue();
	}

	public function isEmpty(): bool
	{
		return $this->queue->isEmpty();
	}

}
