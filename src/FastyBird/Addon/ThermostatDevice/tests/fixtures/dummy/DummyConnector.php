<?php declare(strict_types = 1);

namespace FastyBird\Addon\ThermostatDevice\Tests\Fixtures\Dummy;

use Evenement;
use FastyBird\Addon\ThermostatDevice\Exceptions;
use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use Ramsey\Uuid;
use React\Promise;

class DummyConnector implements DevicesConnectors\Connector
{

	use Evenement\EventEmitterTrait;

	public function getId(): Uuid\UuidInterface
	{
		return Uuid\Uuid::fromString('bda37bc7-9bd7-4083–a925-386ac5522325');
	}

	public function execute(): void
	{
		// NOT IMPLEMENTED
	}

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function discover(): Promise\PromiseInterface
	{
		return Promise\reject(new Exceptions\InvalidState('Not implemented'));
	}

	public function terminate(): void
	{
		// NOT IMPLEMENTED
	}

	public function hasUnfinishedTasks(): bool
	{
		return false;
	}

}
