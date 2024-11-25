<?php declare(strict_types = 1);

namespace FastyBird\Bridge\DevicesModuleUiModule\Tests\Cases\Unit\DI;

use Error;
use FastyBird\Bridge\DevicesModuleUiModule\Exceptions;
use FastyBird\Bridge\DevicesModuleUiModule\Hydrators;
use FastyBird\Bridge\DevicesModuleUiModule\Schemas;
use FastyBird\Bridge\DevicesModuleUiModule\Subscribers;
use FastyBird\Bridge\DevicesModuleUiModule\Tests;
use FastyBird\Core\Application\Exceptions as ApplicationExceptions;
use Nette;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class DevicesModuleUiModuleExtensionTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testServicesRegistration(): void
	{
		self::assertNotNull($this->getContainer()->getByType(Subscribers\ModuleEntities::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\StateEntities::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\DocumentsMapper::class, false));
		self::assertNotNull($this->getContainer()->getByType(Subscribers\ActionCommand::class, false));

		self::assertNotNull(
			$this->getContainer()->getByType(Schemas\Widgets\DataSources\ConnectorProperty::class, false),
		);
		self::assertNotNull($this->getContainer()->getByType(Schemas\Widgets\DataSources\DeviceProperty::class, false));
		self::assertNotNull(
			$this->getContainer()->getByType(Schemas\Widgets\DataSources\ChannelProperty::class, false),
		);

		self::assertNotNull(
			$this->getContainer()->getByType(Hydrators\Widgets\DataSources\ConnectorProperty::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Hydrators\Widgets\DataSources\DeviceProperty::class, false),
		);
		self::assertNotNull(
			$this->getContainer()->getByType(Hydrators\Widgets\DataSources\ChannelProperty::class, false),
		);
	}

}
