<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\Metadata\Entities;
use Ramsey\Uuid;
use Tester\Assert;

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/../../BaseTestCase.php';

/**
 * @testCase
 */
final class ChannelPropertyEntityTest extends BaseTestCase
{

	/**
	 * @param string $data
	 * @param string $class
	 * @param array $fixture
	 *
	 * @dataProvider ./../../../../fixtures/Entities/Modules/channel.property.php
	 */
	public function testCreateEntity(string $data, string $class, array $fixture): void
	{
		/** @var \FastyBird\Metadata\Entities\DevicesModule\ChannelPropertyEntityFactory $factory */
		$factory = $this->container->getByType(Entities\DevicesModule\ChannelPropertyEntityFactory::class);

		$entity = $factory->create($data);

		Assert::true($entity instanceof $class);
		Assert::type(Uuid\UuidInterface::class, $entity->getChannel());
		Assert::equal($fixture, $entity->toArray());
	}

	/**
	 * @param string $data
	 *
	 * @dataProvider ./../../../../fixtures/Entities/Modules/channel.property.invalid.php
	 *
	 * @throws FastyBird\Metadata\Exceptions\Exception
	 */
	public function testCreateEntityInvalid(string $data): void
	{
		/** @var \FastyBird\Metadata\Entities\DevicesModule\ChannelPropertyEntityFactory $factory */
		$factory = $this->container->getByType(Entities\DevicesModule\ChannelPropertyEntityFactory::class);

		$factory->create($data);
	}

}

$test_case = new ChannelPropertyEntityTest();
$test_case->run();
