<?php declare(strict_types = 1);

namespace FastyBird\Library\Bootstrap\Tests\Cases\Unit\DI;

use FastyBird\Bootstrap\Boot;
use PHPUnit\Framework\TestCase;

final class ExtensionTest extends TestCase
{

	public function testCompilersServices(): void
	{
		$configurator = Boot\Bootstrap::boot();

		$configurator->createContainer();

		$this->expectNotToPerformAssertions();
	}

}
