<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Fixtures\Dummy;

use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[DOC\Document(entity: DummyDeviceEntity::class)]
#[DOC\DiscriminatorEntry(name: DummyDeviceEntity::TYPE)]
class DummyDeviceDocument extends DevicesDocuments\Devices\Device
{

	public static function getType(): string
	{
		return DummyDeviceEntity::TYPE;
	}

}
