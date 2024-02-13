<?php declare(strict_types = 1);

namespace FastyBird\Addon\VirtualThermostat\Tests\Fixtures\Dummy;

use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Module\Devices\Documents;

#[DOC\Document(entity: DummyDeviceEntity::class)]
#[DOC\DiscriminatorEntry(name: DummyDeviceEntity::TYPE)]
class DummyDeviceDocument extends Documents\Devices\Device
{

	public static function getType(): string
	{
		return DummyDeviceEntity::TYPE;
	}

}
