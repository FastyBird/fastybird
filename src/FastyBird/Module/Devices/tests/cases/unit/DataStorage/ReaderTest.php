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
		$connectorControlsRepository = $this->getContainer()
			->getByType(Models\DataStorage\ConnectorControlsRepository::class);

		$devicesRepository = $this->getContainer()->getByType(Models\DataStorage\DevicesRepository::class);
		$devicePropertiesRepository = $this->getContainer()
			->getByType(Models\DataStorage\DevicePropertiesRepository::class);
		$deviceControlsRepository = $this->getContainer()
			->getByType(Models\DataStorage\DeviceControlsRepository::class);
		$deviceAttributesRepository = $this->getContainer()
			->getByType(Models\DataStorage\DeviceAttributesRepository::class);

		$channelsRepository = $this->getContainer()->getByType(Models\DataStorage\ChannelsRepository::class);
		$channelPropertiesRepository = $this->getContainer()
			->getByType(Models\DataStorage\ChannelPropertiesRepository::class);
		$channelControlsRepository = $this->getContainer()
			->getByType(Models\DataStorage\ChannelControlsRepository::class);

		self::assertCount(2, $connectorsRepository);
		self::assertCount(0, $connectorPropertiesRepository);
		self::assertCount(1, $connectorControlsRepository);

		self::assertCount(4, $devicesRepository);
		self::assertCount(5, $devicePropertiesRepository);
		self::assertCount(1, $deviceControlsRepository);
		self::assertCount(10, $deviceAttributesRepository);

		self::assertCount(3, $channelsRepository);
		self::assertCount(3, $channelPropertiesRepository);
		self::assertCount(2, $channelControlsRepository);
	}

}
