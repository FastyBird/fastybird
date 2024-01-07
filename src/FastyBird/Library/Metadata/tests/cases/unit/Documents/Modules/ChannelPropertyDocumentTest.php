<?php declare(strict_types = 1);

namespace FastyBird\Library\Metadata\Tests\Cases\Unit\Documents\Modules;

use FastyBird\Library\Metadata\Documents;
use FastyBird\Library\Metadata\Exceptions;
use FastyBird\Library\Metadata\Tests\Cases\Unit\BaseTestCase;
use Nette;
use Ramsey\Uuid;
use Throwable;
use function file_get_contents;
use function method_exists;

final class ChannelPropertyDocumentTest extends BaseTestCase
{

	/**
	 * @param class-string<Documents\Document> $class
	 * @param array<string, mixed> $fixture
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\MalformedInput
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider channelProperty
	 */
	public function testCreateDocument(string $data, string $class, array $fixture): void
	{
		$factory = $this->container->getByType(Documents\DocumentFactory::class);

		$document = $factory->create($class, $data);

		self::assertTrue($document instanceof $class);
		self::assertTrue(method_exists($document, 'getChannel'));
		self::assertTrue($document->getChannel() instanceof Uuid\UuidInterface);
		self::assertEquals($fixture, $document->toArray());
	}

	/**
	 * @param class-string<Documents\Document> $class
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\MalformedInput
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider channelPropertyInvalid
	 */
	public function testCreateDocumentInvalid(string $data, string $class): void
	{
		$factory = $this->container->getByType(Documents\DocumentFactory::class);

		/** @var class-string<Throwable> $exception */
		$exception = Exceptions\Exception::class;

		$this->expectException($exception);

		$factory->create($class, $data);
	}

	/**
	 * @return array<string, array<string|bool|array<string, mixed>>>
	 */
	public static function channelProperty(): array
	{
		return [
			'dynamic' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Documents/Modules/channel.property.dynamic.json'),
				Documents\DevicesModule\ChannelDynamicProperty::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => 'dynamic',
					'category' => 'generic',
					'identifier' => 'property-identifier',
					'name' => null,
					'queryable' => false,
					'settable' => true,
					'data_type' => 'int',
					'unit' => '%',
					'format' => [['u8', 10], 50.0],
					'invalid' => 99,
					'scale' => 0,
					'step' => null,
					'owner' => null,
					'actual_value' => 50,
					'expected_value' => null,
					'pending' => false,
					'valid' => true,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'children' => [],
					'previous_value' => null,
					'created_at' => null,
					'updated_at' => null,

				],
			],
			'variable' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Documents/Modules/channel.property.variable.json'),
				Documents\DevicesModule\ChannelVariableProperty::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => 'variable',
					'category' => 'generic',
					'identifier' => 'property-identifier',
					'name' => null,
					'data_type' => 'enum',
					'unit' => null,
					'format' => ['one','two','three'],
					'invalid' => 99,
					'scale' => null,
					'step' => null,
					'owner' => null,
					'value' => 'two',
					'default' => null,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'children' => [],
					'created_at' => null,
					'updated_at' => null,
				],
			],
			'mapped' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Documents/Modules/channel.property.mapped.json'),
				Documents\DevicesModule\ChannelMappedProperty::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => 'mapped',
					'category' => 'generic',
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
					'scale' => null,
					'step' => null,
					'owner' => null,
					'actual_value' => 'switch_on',
					'expected_value' => null,
					'pending' => false,
					'valid' => true,
					'value' => null,
					'default' => null,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'parent' => 'f42a8b4c-d5c8-4242-8ff0-6e5f867dcfb1',
					'previous_value' => null,
					'created_at' => null,
					'updated_at' => null,
				],
			],
			'dynamic-created' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Documents/Modules/channel.property.dynamic.json'),
				Documents\DevicesModule\ChannelDynamicProperty::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => 'dynamic',
					'category' => 'generic',
					'identifier' => 'property-identifier',
					'name' => null,
					'queryable' => false,
					'settable' => true,
					'data_type' => 'int',
					'unit' => '%',
					'format' => [['u8', 10], 50.0],
					'invalid' => 99,
					'scale' => 0,
					'step' => null,
					'owner' => null,
					'actual_value' => 50,
					'expected_value' => null,
					'pending' => false,
					'valid' => true,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'children' => [],
					'previous_value' => null,
					'created_at' => null,
					'updated_at' => null,
				],
			],
			'dynamic-updated' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Documents/Modules/channel.property.dynamic.json'),
				Documents\DevicesModule\ChannelDynamicProperty::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => 'dynamic',
					'category' => 'generic',
					'identifier' => 'property-identifier',
					'name' => null,
					'queryable' => false,
					'settable' => true,
					'data_type' => 'int',
					'unit' => '%',
					'format' => [['u8', 10], 50.0],
					'invalid' => 99,
					'scale' => 0,
					'step' => null,
					'owner' => null,
					'actual_value' => 50,
					'expected_value' => null,
					'pending' => false,
					'valid' => true,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'children' => [],
					'previous_value' => null,
					'created_at' => null,
					'updated_at' => null,
				],
			],
			'dynamic-deleted' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Documents/Modules/channel.property.dynamic.json'),
				Documents\DevicesModule\ChannelDynamicProperty::class,
				[
					'id' => '176984ad-7cf7-465d-9e53-71668a74a688',
					'type' => 'dynamic',
					'category' => 'generic',
					'identifier' => 'property-identifier',
					'name' => null,
					'queryable' => false,
					'settable' => true,
					'data_type' => 'int',
					'unit' => '%',
					'format' => [['u8', 10], 50.0],
					'invalid' => 99,
					'scale' => 0,
					'step' => null,
					'owner' => null,
					'actual_value' => 50,
					'expected_value' => null,
					'pending' => false,
					'valid' => true,
					'channel' => '247fd6b5-8466-4323-81de-cec3e315015a',
					'children' => [],
					'previous_value' => null,
					'created_at' => null,
					'updated_at' => null,
				],
			],
		];
	}

	/**
	 * @return array<string, array<string|bool>>
	 */
	public static function channelPropertyInvalid(): array
	{
		return [
			'missing' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Documents/Modules/channel.property.missing.json'),
				Documents\DevicesModule\ChannelDynamicProperty::class,
			],
			'type-mismatch' => [
				file_get_contents(__DIR__ . '/../../../../fixtures/Documents/Modules/channel.property.mismatch.json'),
				Documents\DevicesModule\ChannelDynamicProperty::class,
			],
		];
	}

}
