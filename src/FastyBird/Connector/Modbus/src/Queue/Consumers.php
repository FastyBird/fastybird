<?php declare(strict_types = 1);

/**
 * Messages.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Queue
 * @since          1.0.0
 *
 * @date           18.01.23
 */

namespace FastyBird\Connector\Modbus\Queue;

use FastyBird\Connector\Modbus;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;
use SplObjectStorage;

/**
 * Clients message consumer proxy
 *
 * @package        FastyBird:ModbusConnector!
 * @subpackage     Queue
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Consumers
{

	use Nette\SmartObject;

	/** @var SplObjectStorage<Consumer, null> */
	private SplObjectStorage $consumers;

	/**
	 * @param array<Consumer> $consumers
	 */
	public function __construct(
		array $consumers,
		private readonly Queue $queue,
		private readonly Modbus\Logger $logger,
	)
	{
		$this->consumers = new SplObjectStorage();

		foreach ($consumers as $consumer) {
			$this->append($consumer);
		}
	}

	public function append(Consumer $consumer): void
	{
		$this->consumers->attach($consumer);

		$this->logger->debug(
			'Appended new messages consumer',
			[
				'source' => MetadataTypes\Sources\Connector::MODBUS,
				'type' => 'consumers',
			],
		);
	}

	public function consume(): void
	{
		$entity = $this->queue->dequeue();

		if ($entity === false) {
			return;
		}

		$this->consumers->rewind();

		if ($this->consumers->count() === 0) {
			$this->logger->error(
				'No consumer is registered, message could not be consumed',
				[
					'source' => MetadataTypes\Sources\Connector::MODBUS,
					'type' => 'consumers',
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
				'source' => MetadataTypes\Sources\Connector::MODBUS,
				'type' => 'consumers',
				'message' => $entity->toArray(),
			],
		);
	}

}
