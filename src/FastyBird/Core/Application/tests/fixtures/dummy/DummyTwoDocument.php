<?php declare(strict_types = 1);

namespace FastyBird\Core\Application\Tests\Fixtures\Dummy;

use FastyBird\Core\Application\Documents;

#[Documents\Mapping\Document]
#[Documents\Mapping\DiscriminatorEntry(name: self::TYPE)]
class DummyTwoDocument extends DummyDocument
{

	public const TYPE = 'two';

}
