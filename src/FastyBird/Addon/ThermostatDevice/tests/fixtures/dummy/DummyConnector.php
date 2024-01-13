<?php declare(strict_types = 1);

namespace FastyBird\Addon\ThermostatDevice\Tests\Fixtures\Dummy;

use FastyBird\Module\Devices\Connectors as DevicesConnectors;
use Ramsey\Uuid;

class DummyConnector implements DevicesConnectors\Connector
{

	public function getId(): Uuid\UuidInterface
	{
		return Uuid\Uuid::fromString('bda37bc7-9bd7-4083–a925-386ac5522325');
	}

	public function execute(): void
	{
		// NOT IMPLEMENTED
	}

	public function discover(): void
	{
		// NOT IMPLEMENTED
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
