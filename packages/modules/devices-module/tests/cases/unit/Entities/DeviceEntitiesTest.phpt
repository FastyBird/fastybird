<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Models;
use FastyBird\DevicesModule\Queries;
use Nette\Utils;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../DbTestCase.php';

/**
 * @testCase
 */
final class DeviceEntitiesTest extends DbTestCase
{

	public function testFindChildren(): void
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

	public function testCreateChild(): void
	{
		/** @var Models\Devices\DevicesManager $manager */
		$manager = $this->getContainer()->getByType(Models\Devices\DevicesManager::class);

		/** @var Models\Devices\DevicesRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Devices\DevicesRepository::class);

		$findQuery = new Queries\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		Assert::true(is_object($parent));
		Assert::type(Entities\Devices\Device::class, $parent);
		Assert::same('first-device', $parent->getIdentifier());

		$child = $manager->create(Utils\ArrayHash::from([
			'entity'     => Entities\Devices\Blank::class,
			'identifier' => 'new-child-device',
			'connector'  => $parent->getConnector(),
			'name'       => 'New child device',
			'parents'    => [
				$parent,
			],
		]));

		Assert::true(is_object($child));
		Assert::type(Entities\Devices\Device::class, $child);
		Assert::same('new-child-device', $child->getIdentifier());
		Assert::count(1, $child->getParents());
	}

	public function testRemoveParent(): void
	{
		/** @var Models\Devices\DevicesManager $manager */
		$manager = $this->getContainer()->getByType(Models\Devices\DevicesManager::class);

		/** @var Models\Devices\DevicesRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Devices\DevicesRepository::class);

		$findQuery = new Queries\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		Assert::true(is_object($parent));
		Assert::type(Entities\Devices\Device::class, $parent);
		Assert::same('first-device', $parent->getIdentifier());

		$manager->delete($parent);

		$findQuery = new Queries\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		Assert::false(is_object($parent));

		$findQuery = new Queries\FindDevices();
		$findQuery->byIdentifier('child-device');

		$entity = $repository->findOneBy($findQuery);

		Assert::false(is_object($entity));
	}

	public function testChildParent(): void
	{
		/** @var Models\Devices\DevicesManager $manager */
		$manager = $this->getContainer()->getByType(Models\Devices\DevicesManager::class);

		/** @var Models\Devices\DevicesRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Devices\DevicesRepository::class);

		$findQuery = new Queries\FindDevices();
		$findQuery->byIdentifier('child-device');

		$child = $repository->findOneBy($findQuery);

		Assert::true(is_object($child));
		Assert::type(Entities\Devices\Device::class, $child);
		Assert::same('child-device', $child->getIdentifier());

		$manager->delete($child);

		$findQuery = new Queries\FindDevices();
		$findQuery->byIdentifier('first-device');

		$parent = $repository->findOneBy($findQuery);

		Assert::true(is_object($parent));
		Assert::type(Entities\Devices\Device::class, $parent);
		Assert::same('first-device', $parent->getIdentifier());
	}

}

$test_case = new DeviceEntitiesTest();
$test_case->run();
