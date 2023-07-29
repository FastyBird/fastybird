<?php declare(strict_types = 1);

namespace FastyBird\Automator\DevicesModule\Tests\Cases\Unit\DI;

use FastyBird\Automator\DevicesModule\Exceptions;
use FastyBird\Automator\DevicesModule\Hydrators;
use FastyBird\Automator\DevicesModule\Schemas;
use FastyBird\Automator\DevicesModule\Subscribers;
use FastyBird\Automator\DevicesModule\Tests\Cases\Unit\DbTestCase;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use Nette;
use RuntimeException;

final class DevicesModuleExtensionTests extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->getContainer()->getByType(Schemas\Actions\DevicePropertyAction::class, false));
		self::assertNotNull($this->getContainer()->getByType(Schemas\Actions\ChannelPropertyAction::class, false));
		self::assertNotNull(
			$this->getContainer()->getByType(Schemas\Conditions\ChannelPropertyCondition::class, false),
		);
		self::assertNotNull($this->getContainer()->getByType(Schemas\Conditions\DevicePropertyCondition::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Actions\DevicePropertyAction::class, false));
		self::assertNotNull($this->getContainer()->getByType(Hydrators\Actions\ChannelPropertyAction::class, false));
		self::assertNotNull(
			$this->getContainer()->getByType(Hydrators\Conditions\ChannelPropertyCondition::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Hydrators\Conditions\DevicePropertyCondition::class, false),
		);

		self::assertNotNull($this->getContainer()->getByType(Subscribers\ActionEntity::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\ConditionEntity::class, false));
	}

}
