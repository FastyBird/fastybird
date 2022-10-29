<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\DataStorage;

use Exception;
use FastyBird\Module\Devices\DataStorage;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Tests\Cases\Unit\DbTestCase;
use League\Flysystem;
use Nette;
use RuntimeException;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ReaderTest extends DbTestCase
{

	/**
	 * @throws Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Flysystem\FilesystemException
	 * @throws Nette\DI\MissingServiceException
	 * @throws Nette\Utils\JsonException
	 * @throws RuntimeException
	 */
	public function setUp(): void
	{
		parent::setUp();

		$writer = $this->getContainer()->getByType(DataStorage\Writer::class);
		$reader = $this->getContainer()->getByType(DataStorage\Reader::class);

		$writer->write();
		$reader->read();
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 */
	public function testReadConfiguration(): void
	{
		$connectorsRepository = $this->getContainer()->getByType(Models\DataStorage\ConnectorsRepository::class);
		$connectorPropertiesRepository = $this->getContainer()
			->getByType(Models\DataStorage\ConnectorPropertiesRepository::class);

		$devicePropertiesRepository = $this->getContainer()
			->getByType(Models\DataStorage\DevicePropertiesRepository::class);

		$channelPropertiesRepository = $this->getContainer()
			->getByType(Models\DataStorage\ChannelPropertiesRepository::class);

		self::assertCount(2, $connectorsRepository);
		self::assertCount(0, $connectorPropertiesRepository);

		self::assertCount(5, $devicePropertiesRepository);

		self::assertCount(3, $channelPropertiesRepository);
	}

}
