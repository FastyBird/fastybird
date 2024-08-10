<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Fixtures\Dummy;

use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use FastyBird\Module\Devices\Documents as DevicesDocuments;

#[DOC\Document(entity: DummyChannelEntity::class)]
#[DOC\DiscriminatorEntry(name: DummyChannelEntity::TYPE)]
class DummyChannelDocument extends DevicesDocuments\Channels\Channel
{

	public static function getType(): string
	{
		return DummyChannelEntity::TYPE;
	}

}
