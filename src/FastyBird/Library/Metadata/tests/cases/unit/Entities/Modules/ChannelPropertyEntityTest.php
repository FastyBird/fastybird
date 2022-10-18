<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\Entities\Modules;

use FastyBird\Library\Metadata\Entities;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Tests\Cases\Unit\BaseTestCase;
use Nette;
use Ramsey\Uuid;
use Throwable;
use function file_get_contents;
use function method_exists;

final class ChannelPropertyEntityTest extends BaseTestCase
{

	/**
	 * @param Array<string, mixed> $fixture
	 *
	 * @throws Exceptions\FileNotFound
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidData
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Logic
	 * @throws Exceptions\MalformedInput
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider channelProperty
	 */
	public function testCreateEntity(string $data, string $class, array $fixture): void
	{
		$factory = $this->container->getByType(Entities\DevicesModule\ChannelPropertyEntityFactory::class);

		$entity = $factory->create($data);

		self::assertTrue($entity instanceof $class);
		self::assertTrue(method_exists($entity, 'getChannel'));
		self::assertTrue($entity->getChannel() instanceof Uuid\UuidInterface);
		self::assertEquals($fixture, $entity->toArray());
	}

	/**
	 * @throws Exceptions\FileNotFound
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidData
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Logic
	 * @throws Exceptions\MalformedInput
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider channelPropertyInvalid
	 */
	public function testCreateEntityInvalid(string $data): void
	{
		$factory = $this->container->getByType(Entities\DevicesModule\ChannelPropertyEntityFactory::class);

		/** @var class-string<Throwable> $exception */
		$exception = Exceptions\Exception::class;

		$this->expectException($exception);

		$factory->create($data);
	}

	/**
	 * @return Array<string, Array<string|bool|Array<string, mixed>>>
	 */
	public function channelProperty(): array
	{
		return [
			'dynamic' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Entities/Modules/channel.property.dynamic.json'),
				Entities\DevicesModule\ChannelDynamicProperty::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => 'dynamic',
					'identifier' => 'property-identifier',
					'name' => null,
					'queryable' => false,
					'settable' => true,
					'data_type' => 'int',
					'unit' => '%',
					'format' => [['u8', 10], 50.0],
					'invalid' => 99,
					'number_of_decimals' => 0,
					'owner' => null,
					'actual_value' => 50,
					'previous_value' => 48,
					'expected_value' => null,
					'pending' => false,
					'valid' => true,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'parent' => null,
					'children' => [],
				],
			],
			'variable' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Entities/Modules/channel.property.variable.json'),
				Entities\DevicesModule\ChannelVariableProperty::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => 'variable',
					'identifier' => 'property-identifier',
					'name' => null,
					'queryable' => false,
					'settable' => true,
					'data_type' => 'enum',
					'unit' => null,
					'format' => ['one','two','three'],
					'invalid' => 99,
					'number_of_decimals' => 0,
					'owner' => null,
					'value' => 50,
					'default' => null,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'parent' => null,
					'children' => [],
				],
			],
			'mapped' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Entities/Modules/channel.property.mapped.json'),
				Entities\DevicesModule\ChannelMappedProperty::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => 'mapped',
					'identifier' => 'property-identifier',
					'name' => null,
					'queryable' => false,
					'settable' => true,
					'data_type' => 'switch',
					'unit' => null,
					'format' => [
						[
							['sw', 'switch_on'],
							'1000',
							['s', 'on'],
						],
						[
							['sw', 'switch_off'],
							'2000',
							['s', 'off'],
						],
						[
							['sw', 'switch_toggle'],
							null,
							['s', 'toggle'],
						],
					],
					'invalid' => 99,
					'number_of_decimals' => null,
					'owner' => null,
					'actual_value' => 'switch_on',
					'previous_value' => 'switch_off',
					'expected_value' => null,
					'pending' => false,
					'valid' => true,
					'value' => null,
					'default' => null,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'parent' => 'f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1',
					'children' => [],
				],
			],
			'dynamic-created' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Entities/Modules/channel.property.dynamic.json'),
				Entities\DevicesModule\ChannelDynamicProperty::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => 'dynamic',
					'identifier' => 'property-identifier',
					'name' => null,
					'queryable' => false,
					'settable' => true,
					'data_type' => 'int',
					'unit' => '%',
					'format' => [['u8', 10], 50.0],
					'invalid' => 99,
					'number_of_decimals' => 0,
					'owner' => null,
					'actual_value' => 50,
					'previous_value' => 48,
					'expected_value' => null,
					'pending' => false,
					'valid' => true,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'parent' => null,
					'children' => [],
				],
			],
			'dynamic-updated' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Entities/Modules/channel.property.dynamic.json'),
				Entities\DevicesModule\ChannelDynamicProperty::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => 'dynamic',
					'identifier' => 'property-identifier',
					'name' => null,
					'queryable' => false,
					'settable' => true,
					'data_type' => 'int',
					'unit' => '%',
					'format' => [['u8', 10], 50.0],
					'invalid' => 99,
					'number_of_decimals' => 0,
					'owner' => null,
					'actual_value' => 50,
					'previous_value' => 48,
					'expected_value' => null,
					'pending' => false,
					'valid' => true,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'parent' => null,
					'children' => [],
				],
			],
			'dynamic-deleted' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Entities/Modules/channel.property.dynamic.json'),
				Entities\DevicesModule\ChannelDynamicProperty::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => 'dynamic',
					'identifier' => 'property-identifier',
					'name' => null,
					'queryable' => false,
					'settable' => true,
					'data_type' => 'int',
					'unit' => '%',
					'format' => [['u8', 10], 50.0],
					'invalid' => 99,
					'number_of_decimals' => 0,
					'owner' => null,
					'actual_value' => 50,
					'previous_value' => 48,
					'expected_value' => null,
					'pending' => false,
					'valid' => true,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'parent' => null,
					'children' => [],
				],
			],
		];
	}

	/**
	 * @return Array<string, Array<string|bool>>
	 */
	public function channelPropertyInvalid(): array
	{
		return [
			'missing' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Entities/Modules/channel.property.missing.json'),
			],
		];
	}

}
