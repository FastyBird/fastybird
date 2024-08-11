<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Fixtures\Dummy;

use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[DOC\Document(entity: DummyConnectorEntity::class)]
#[DOC\DiscriminatorEntry(name: DummyConnectorEntity::TYPE)]
class DummyConnectorDocument extends DevicesDocuments\Connectors\Connector
{

	public static function getType(): string
	{
		return DummyConnectorEntity::TYPE;
	}

}
