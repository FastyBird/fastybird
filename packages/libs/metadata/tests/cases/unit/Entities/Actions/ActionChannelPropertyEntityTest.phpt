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
final class ActionChannelPropertyEntityTest extends BaseTestCase
{

	/**
	 * @param string $data
	 * @param string $class
	 *
	 * @dataProvider ./../../../../fixtures/Entities/Actions/channel.property.php
	 */
	public function testCreateEntity(string $data, string $class): void
	{
		/** @var Entities\Actions\ActionChannelPropertyEntityFactory $factory */
		$factory = $this->container->getByType(Entities\Actions\ActionChannelPropertyEntityFactory::class);

		$entity = $factory->create($data);

		Assert::true($entity instanceof $class);
		Assert::type(Uuid\UuidInterface::class, $entity->getDevice());
		Assert::type(Uuid\UuidInterface::class, $entity->getChannel());
		Assert::type(Uuid\UuidInterface::class, $entity->getProperty());
	}

	/**
	 * @param string $data
	 *
	 * @dataProvider ./../../../../fixtures/Entities/Actions/channel.property.invalid.php
	 *
	 * @throws FastyBird\Metadata\Exceptions\InvalidData
	 */
	public function testCreateEntityInvalid(string $data): void
	{
		/** @var Entities\Actions\ActionChannelPropertyEntityFactory $factory */
		$factory = $this->container->getByType(Entities\Actions\ActionChannelPropertyEntityFactory::class);

		$factory->create($data);
	}

}

$test_case = new ActionChannelPropertyEntityTest();
$test_case->run();
