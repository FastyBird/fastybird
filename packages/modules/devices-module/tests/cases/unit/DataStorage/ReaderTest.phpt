<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\DevicesModule\DataStorage;
use FastyBird\DevicesModule\Models;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../DbTestCase.php';

/**
 * @testCase
 */
final class ReaderTest extends DbTestCase
{

	public function setUp(): void
	{
		parent::setUp();

		$writer = $this->getContainer()->getByType(DataStorage\Writer::class);
		$reader = $this->getContainer()->getByType(DataStorage\Reader::class);

		$writer->write();
		$reader->read();
	}

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

		Assert::count(2, $connectorsRepository);
		Assert::count(0, $connectorPropertiesRepository);
		Assert::count(1, $connectorControlsRepository);

		Assert::count(4, $devicesRepository);
		Assert::count(5, $devicePropertiesRepository);
		Assert::count(1, $deviceControlsRepository);
		Assert::count(10, $deviceAttributesRepository);

		Assert::count(3, $channelsRepository);
		Assert::count(3, $channelPropertiesRepository);
		Assert::count(2, $channelControlsRepository);
	}

}

$test_case = new ReaderTest();
$test_case->run();
