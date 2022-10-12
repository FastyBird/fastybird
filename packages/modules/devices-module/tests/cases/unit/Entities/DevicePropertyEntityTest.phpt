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
final class DevicePropertyEntityTest extends DbTestCase
{

	public function testAddChildProperty(): void
	{
		/** @var Models\Devices\Properties\PropertiesManager $manager */
		$manager = $this->getContainer()->getByType(Models\Devices\Properties\PropertiesManager::class);

		/** @var Models\Devices\Properties\PropertiesRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Devices\Properties\PropertiesRepository::class);

		$findQuery = new Queries\FindDeviceProperties();
		$findQuery->byIdentifier('status_led');

		$parent = $repository->findOneBy($findQuery);

		Assert::true(is_object($parent));
		Assert::type(Entities\Devices\Properties\Variable::class, $parent);
		Assert::same('status_led', $parent->getIdentifier());

		$child = $manager->create(Utils\ArrayHash::from([
			'entity'     => Entities\Devices\Properties\Variable::class,
			'identifier' => 'new-child-property',
			'device'     => $parent->getDevice(),
			'parent'     => $parent,
		]));

		Assert::true(is_object($child));
		Assert::type(Entities\Devices\Properties\Variable::class, $child);
		Assert::same('new-child-property', $child->getIdentifier());
		Assert::same($parent, $child->getParent());
	}

	public function testRemoveChildProperty(): void
	{
		/** @var Models\Devices\Properties\PropertiesManager $manager */
		$manager = $this->getContainer()->getByType(Models\Devices\Properties\PropertiesManager::class);

		/** @var Models\Devices\Properties\PropertiesRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Devices\Properties\PropertiesRepository::class);

		$findQuery = new Queries\FindDeviceProperties();
		$findQuery->byIdentifier('status_led');

		$parent = $repository->findOneBy($findQuery);

		Assert::true(is_object($parent));
		Assert::type(Entities\Devices\Properties\Variable::class, $parent);
		Assert::same('status_led', $parent->getIdentifier());

		$child = $manager->create(Utils\ArrayHash::from([
			'entity'     => Entities\Devices\Properties\Variable::class,
			'identifier' => 'new-child-property',
			'device'     => $parent->getDevice(),
			'parent'     => $parent,
		]));

		Assert::true(is_object($child));
		Assert::type(Entities\Devices\Properties\Variable::class, $child);
		Assert::same('new-child-property', $child->getIdentifier());
		Assert::same($parent, $child->getParent());

		$findQuery = new Queries\FindDeviceProperties();
		$findQuery->byIdentifier('status_led');

		$parent = $repository->findOneBy($findQuery);

		Assert::count(1, $parent->getChildren());

		$manager->delete($child);

		$findQuery = new Queries\FindDeviceProperties();
		$findQuery->byIdentifier('status_led');

		$parent = $repository->findOneBy($findQuery);

		Assert::true(is_object($parent));
		Assert::type(Entities\Devices\Properties\Variable::class, $parent);
		Assert::same('status_led', $parent->getIdentifier());
		Assert::count(0, $parent->getChildren());
	}

	public function testRemoveParentProperty(): void
	{
		/** @var Models\Devices\Properties\PropertiesManager $manager */
		$manager = $this->getContainer()->getByType(Models\Devices\Properties\PropertiesManager::class);

		/** @var Models\Devices\Properties\PropertiesRepository $repository */
		$repository = $this->getContainer()->getByType(Models\Devices\Properties\PropertiesRepository::class);

		$findQuery = new Queries\FindDeviceProperties();
		$findQuery->byIdentifier('status_led');

		$parent = $repository->findOneBy($findQuery);

		Assert::true(is_object($parent));
		Assert::type(Entities\Devices\Properties\Variable::class, $parent);
		Assert::same('status_led', $parent->getIdentifier());

		$child = $manager->create(Utils\ArrayHash::from([
			'entity'     => Entities\Devices\Properties\Variable::class,
			'identifier' => 'new-child-property',
			'device'     => $parent->getDevice(),
			'parent'     => $parent,
		]));

		Assert::true(is_object($child));
		Assert::type(Entities\Devices\Properties\Variable::class, $child);
		Assert::same('new-child-property', $child->getIdentifier());
		Assert::same($parent, $child->getParent());

		$findQuery = new Queries\FindDeviceProperties();
		$findQuery->byIdentifier('new-child-property');

		$child = $repository->findOneBy($findQuery);

		Assert::true(is_object($child));
		Assert::type(Entities\Devices\Properties\Variable::class, $child);

		$findQuery = new Queries\FindDeviceProperties();
		$findQuery->byIdentifier('status_led');

		$parent = $repository->findOneBy($findQuery);

		Assert::count(1, $parent->getChildren());

		$manager->delete($parent);

		$findQuery = new Queries\FindDeviceProperties();
		$findQuery->byIdentifier('new-child-property');

		$child = $repository->findOneBy($findQuery);

		Assert::false(is_object($child));
	}

}

$test_case = new DevicePropertyEntityTest();
$test_case->run();
