<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\Queries;
use IPub\DoctrineOrmQuery;
use Tester\Assert;

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../DbTestCase.php';

/**
 * @testCase
 */
final class DevicesRepositoryTest extends DbTestCase
{

	public function testReadOne(): void
	{
		/** @var Models\Devices\DevicesRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Devices\DevicesRepository::class);

		$findQuery = new Queries\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		Assert::true(is_object($parent));
		Assert::type(Entities\Devices\Device::class, $parent);
		Assert::same('first-device', $parent->getIdentifier());

		$findQuery = new Queries\FindDevices();
		$findQuery->forParent($parent);

		$entity = $repository->findOneBy($findQuery);

		Assert::true(is_object($entity));
		Assert::type(Entities\Devices\Device::class, $entity);
		Assert::same('child-device', $entity->getIdentifier());
	}

	public function testReadResultSet(): void
	{
		/** @var Models\Devices\DevicesRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Devices\DevicesRepository::class);

		$findQuery = new Queries\FindDevices();

		$resultSet = $repository->getResultSet($findQuery);

		Assert::type(DoctrineOrmQuery\ResultSet::class, $resultSet);
		Assert::same(4, $resultSet->getTotalCount());
	}

}

$test_case = new DevicesRepositoryTest();
$test_case->run();
