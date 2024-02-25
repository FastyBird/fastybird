<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Fixtures\Dummy;

use FastyBird\Library\Metadata\Documents\Mapping as DOC;

#[DOC\Document]
#[DOC\DiscriminatorEntry(name: self::TYPE)]
class DummyTwoDocument extends DummyDocument
{

	public const TYPE = 'two';

}
