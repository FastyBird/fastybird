<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\DevicesModule;
use FastyBird\DevicesModule\DataStorage as DevicesModuleDataStorage;
use FastyBird\FbMqttConnector\Connector;
use Ramsey\Uuid;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../DbTestCase.php';

/**
 * @testCase
 */
final class ConnectorFactoryTest extends DbTestCase
{

	public function setUp(): void
	{
		parent::setUp();

		/** @var DevicesModuleDataStorage\Writer $writer */
		$writer = $this->getContainer()->getByType(DevicesModuleDataStorage\Writer::class);
		/** @var DevicesModuleDataStorage\Reader $reader */
		$reader = $this->getContainer()->getByType(DevicesModuleDataStorage\Reader::class);

		$writer->write();
		$reader->read();
	}

	public function testCreateConnector(): void
	{
		/** @var DevicesModule\Models\DataStorage\ConnectorsRepository $connectorsRepository */
		$connectorsRepository = $this->getContainer()->getByType(DevicesModule\Models\DataStorage\ConnectorsRepository::class);

		/** @var Connector\ConnectorFactory $factory */
		$factory = $this->getContainer()->getByType(Connector\ConnectorFactory::class);

		$connector = $factory->create($connectorsRepository->findById(Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e')));

		Assert::type(Connector\Connector::class, $connector);
	}

}

$test_case = new ConnectorFactoryTest();
$test_case->run();
