<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\DevicesModule\DataStorage;
use FastyBird\DevicesModule\Models;
use FastyBird\Metadata\Entities as MetadataEntities;
use Ramsey\Uuid;
use Tester\Assert;

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../DbTestCase.php';

/**
 * @testCase
 */
final class ConnectorsRepositoryTest extends DbTestCase
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

		Assert::count(2, $connectorsRepository);

		$connector = $connectorsRepository->findById(Uuid\Uuid::fromString('17c59dfa-2edd-438e-8c49-faa4e38e5a5e'));

		Assert::type(MetadataEntities\DevicesModule\Connector::class, $connector);
		Assert::same('Blank', $connector->getName());
		Assert::same('blank', $connector->getType());
	}

}

$test_case = new ConnectorsRepositoryTest();
$test_case->run();
