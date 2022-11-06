<?php declare(strict_types = 1);

namespace FastyBird\Automator\DateTime\Tests\Cases\Unit\DI;

use FastyBird\Automator\DateTime\Exceptions;
use FastyBird\Automator\DateTime\Hydrators;
use FastyBird\Automator\DateTime\Schemas;
use FastyBird\Automator\DateTime\Tests\Cases\Unit\DbTestCase;
use Nette;
use RuntimeException;

final class DateTimeExtensionTests extends DbTestCase
{

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->getContainer()->getByType(Schemas\Conditions\DateCondition::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Conditions\TimeCondition::class, false));

		self::assertNotNull($this->getContainer()->getByType(Hydrators\Conditions\DataCondition::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Conditions\TimeCondition::class, false));
	}

}
