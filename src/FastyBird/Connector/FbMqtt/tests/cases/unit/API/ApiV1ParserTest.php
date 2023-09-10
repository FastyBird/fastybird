<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Tests\Cases\Unit\API;

use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Tests\Cases\Unit\BaseTestCase;
use Nette;
use Ramsey\Uuid;
use Throwable;

final class ApiV1ParserTest extends BaseTestCase
{

	/**
	 * @phpstan-param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider parseDeviceAttributesProvider
	 */
	public function testParseDeviceAttribute(
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$apiV1Parser = $this->container->getByType(API\V1Parser::class);

		$entity = $apiV1Parser->parse(
			Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e'),
			$topic,
			$payload,
		);

		self::assertTrue($entity instanceof Entities\Messages\DeviceAttribute);
		self::assertEquals($expected, $entity->toArray());
	}

	/**
	 * @return array<string, array<string|array<string, bool|float|int|string|array<string>>>>
	 */
	public static function parseDeviceAttributesProvider(): array
	{
		return [
			'attr-' . Entities\Messages\Attribute::NAME => [
				'/fb/v1/device-name/$' . Entities\Messages\Attribute::NAME,
				'Some content',
				[
					'device' => 'device-name',
					'retained' => false,
					Entities\Messages\Attribute::NAME => 'Some content',
				],
			],
			'attr-' . Entities\Messages\Attribute::PROPERTIES => [
				'/fb/v1/device-name/$' . Entities\Messages\Attribute::PROPERTIES,
				'prop1,prop2',
				[
					'device' => 'device-name',
					'retained' => false,
					Entities\Messages\Attribute::PROPERTIES => ['prop1', 'prop2'],
				],
			],
			'attr-' . Entities\Messages\Attribute::CHANNELS => [
				'/fb/v1/device-name/$' . Entities\Messages\Attribute::CHANNELS,
				'channel-one,channel-two',
				[
					'device' => 'device-name',
					'retained' => false,
					Entities\Messages\Attribute::CHANNELS => ['channel-one', 'channel-two'],
				],
			],
			'attr-' . Entities\Messages\Attribute::CONTROLS => [
				'/fb/v1/device-name/$' . Entities\Messages\Attribute::CONTROLS,
				'configure,reset',
				[
					'device' => 'device-name',
					'retained' => false,
					Entities\Messages\Attribute::CONTROLS => ['configure', 'reset'],
				],
			],
		];
	}

	/**
	 * @phpstan-param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider parseDeviceHardwareInfoProvider
	 */
	public function testParseDeviceHardwareInfo(
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$apiV1Parser = $this->container->getByType(API\V1Parser::class);

		$entity = $apiV1Parser->parse(
			Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e'),
			$topic,
			$payload,
		);

		self::assertTrue($entity instanceof Entities\Messages\ExtensionAttribute);
		self::assertEquals($expected, $entity->toArray());
	}

	/**
	 * @return array<string, array<string|array<string, bool|float|int|string|array<string>>>>
	 */
	public static function parseDeviceHardwareInfoProvider(): array
	{
		return [
			'hw-' . Entities\Messages\ExtensionAttribute::MAC_ADDRESS => [
				'/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::MAC_ADDRESS,
				'00:0a:95:9d:68:16',
				[
					'device' => 'device-name',
					'retained' => false,
					Entities\Messages\ExtensionAttribute::MAC_ADDRESS => '000a959d6816',
				],
			],
			'hw-' . Entities\Messages\ExtensionAttribute::MANUFACTURER => [
				'/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::MANUFACTURER,
				'value-content',
				[
					'device' => 'device-name',
					'retained' => false,
					Entities\Messages\ExtensionAttribute::MANUFACTURER => 'value-content',
				],
			],
			'hw-' . Entities\Messages\ExtensionAttribute::MODEL => [
				'/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::MODEL,
				'value-content',
				[
					'device' => 'device-name',
					'retained' => false,
					Entities\Messages\ExtensionAttribute::MODEL => 'value-content',
				],
			],
			'hw-' . Entities\Messages\ExtensionAttribute::VERSION => [
				'/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::VERSION,
				'value-content',
				[
					'device' => 'device-name',
					'retained' => false,
					Entities\Messages\ExtensionAttribute::VERSION => 'value-content',
				],
			],
		];
	}

	/**
	 * @phpstan-param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider parseDeviceFirmwareInfoProvider
	 */
	public function testParseDeviceFirmwareInfo(
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$apiV1Parser = $this->container->getByType(API\V1Parser::class);

		$entity = $apiV1Parser->parse(
			Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e'),
			$topic,
			$payload,
		);

		self::assertTrue($entity instanceof Entities\Messages\ExtensionAttribute);
		self::assertEquals($expected, $entity->toArray());
	}

	/**
	 * @return array<string, array<string|array<string, bool|float|int|string|array<string>>>>
	 */
	public static function parseDeviceFirmwareInfoProvider(): array
	{
		return [
			'fw-' . Entities\Messages\ExtensionAttribute::MANUFACTURER => [
				'/fb/v1/device-name/$fw/' . Entities\Messages\ExtensionAttribute::MANUFACTURER,
				'value-content',
				[
					'device' => 'device-name',
					'retained' => false,
					Entities\Messages\ExtensionAttribute::MANUFACTURER => 'value-content',
				],
			],
			'fw-' . Entities\Messages\ExtensionAttribute::VERSION => [
				'/fb/v1/device-name/$fw/' . Entities\Messages\ExtensionAttribute::VERSION,
				'value-content',
				[
					'device' => 'device-name',
					'retained' => false,
					Entities\Messages\ExtensionAttribute::VERSION => 'value-content',
				],
			],
		];
	}

	/**
	 * @phpstan-param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider parseDevicePropertiesProvider
	 */
	public function testParseDeviceProperties(
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$apiV1Parser = $this->container->getByType(API\V1Parser::class);

		$entity = $apiV1Parser->parse(
			Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e'),
			$topic,
			$payload,
		);

		self::assertTrue($entity instanceof Entities\Messages\DeviceProperty);
		self::assertEquals($expected, $entity->toArray());
	}

	/**
	 * @return array<string, array<string|array<string, bool|float|int|string|array<string>>>>
	 */
	public static function parseDevicePropertiesProvider(): array
	{
		return [
			'prop-property-name' => [
				'/fb/v1/device-name/$property/property-name',
				'content',
				[
					'device' => 'device-name',
					'retained' => false,
					'property' => 'property-name',
					'value' => 'content',
				],
			],
		];
	}

	/**
	 * @phpstan-param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider parseDevicePropertiesAttributesProvider
	 */
	public function testParseDevicePropertiesAttributes(
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$apiV1Parser = $this->container->getByType(API\V1Parser::class);

		$entity = $apiV1Parser->parse(
			Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e'),
			$topic,
			$payload,
		);

		self::assertTrue($entity instanceof Entities\Messages\DeviceProperty);
		self::assertEquals($expected, $entity->toArray());
	}

	/**
	 * @return array<string, array<string|array<string, bool|float|int|string|array<string>>>>
	 */
	public static function parseDevicePropertiesAttributesProvider(): array
	{
		return [
			'attr-' . Entities\Messages\PropertyAttribute::NAME => [
				'/fb/v1/device-name/$property/some-property/$' . Entities\Messages\PropertyAttribute::NAME,
				'payload',
				[
					'device' => 'device-name',
					'retained' => false,
					'property' => 'some-property',
					Entities\Messages\PropertyAttribute::NAME => 'payload',
				],
			],
			'attr-' . Entities\Messages\PropertyAttribute::SETTABLE => [
				'/fb/v1/device-name/$property/some-property/$' . Entities\Messages\PropertyAttribute::SETTABLE,
				'true',
				[
					'device' => 'device-name',
					'retained' => false,
					'property' => 'some-property',
					Entities\Messages\PropertyAttribute::SETTABLE => true,
				],
			],
			'attr-' . Entities\Messages\PropertyAttribute::QUERYABLE => [
				'/fb/v1/device-name/$property/some-property/$' . Entities\Messages\PropertyAttribute::QUERYABLE,
				'invalid',
				[
					'device' => 'device-name',
					'retained' => false,
					'property' => 'some-property',
					Entities\Messages\PropertyAttribute::QUERYABLE => false,
				],
			],
		];
	}

	/**
	 * @phpstan-param class-string<Throwable> $exception
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider parseDeviceAttributesInvalidProvider
	 */
	public function testParseDeviceAttributeNotValid(
		string $topic,
		string $exception,
		string $message,
	): void
	{
		$apiV1Parser = $this->container->getByType(API\V1Parser::class);

		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$apiV1Parser->parse(
			Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e'),
			$topic,
			'bar',
		);
	}

	/**
	 * @return array<string, array<string>>
	 */
	public static function parseDeviceAttributesInvalidProvider(): array
	{
		return [
			'attr-unknown' => [
				'/fb/v1/device-name/$unknown',
				Exceptions\ParseMessage::class,
				'Provided topic is not valid',
			],
		];
	}

	/**
	 * @phpstan-param class-string<Throwable> $exception
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider parseDeviceHardwareInfoInvalidProvider
	 */
	public function testParseDeviceHardwareInfoNotValid(
		string $topic,
		string $exception,
		string $message,
	): void
	{
		$apiV1Parser = $this->container->getByType(API\V1Parser::class);

		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$apiV1Parser->parse(
			Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e'),
			$topic,
			'bar',
		);
	}

	/**
	 * @return array<string, array<string>>
	 */
	public static function parseDeviceHardwareInfoInvalidProvider(): array
	{
		return [
			'hw-not-valid' => [
				'/fb/v1/device-name/$hw/not-valid',
				Exceptions\ParseMessage::class,
				'Provided topic is not valid',
			],
		];
	}

	/**
	 * @phpstan-param class-string<Throwable> $exception
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider parseDeviceFirmwareInfoInvalidProvider
	 */
	public function testParseDeviceFirmwareInfoNotValid(
		string $topic,
		string $exception,
		string $message,
	): void
	{
		$apiV1Parser = $this->container->getByType(API\V1Parser::class);

		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$apiV1Parser->parse(
			Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e'),
			$topic,
			'bar',
		);
	}

	/**
	 * @return array<string, array<string>>
	 */
	public static function parseDeviceFirmwareInfoInvalidProvider(): array
	{
		return [
			'fw-not-valid' => [
				'/fb/v1/device-name/$fw/not-valid',
				Exceptions\ParseMessage::class,
				'Provided topic is not valid',
			],
		];
	}

	/**
	 * @phpstan-param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider parseChannelAttributesProvider
	 */
	public function testParseChannelAttributes(
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$apiV1Parser = $this->container->getByType(API\V1Parser::class);

		$entity = $apiV1Parser->parse(
			Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e'),
			$topic,
			$payload,
		);

		self::assertTrue($entity instanceof Entities\Messages\ChannelAttribute);
		self::assertEquals($expected, $entity->toArray());
	}

	/**
	 * @return array<string, array<string|array<string, bool|float|int|string|array<string>>>>
	 */
	public static function parseChannelAttributesProvider(): array
	{
		return [
			'attr-' . Entities\Messages\Attribute::NAME => [
				'/fb/v1/device-name/$channel/channel-name/$' . Entities\Messages\Attribute::NAME,
				'Some content',
				[
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					Entities\Messages\Attribute::NAME => 'Some content',
				],
			],
			'attr-' . Entities\Messages\Attribute::PROPERTIES => [
				'/fb/v1/device-name/$channel/channel-name/$' . Entities\Messages\Attribute::PROPERTIES,
				'prop1,prop2',
				[
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					Entities\Messages\Attribute::PROPERTIES => ['prop1', 'prop2'],
				],
			],
			'attr-' . Entities\Messages\Attribute::CONTROLS => [
				'/fb/v1/device-name/$channel/channel-name/$' . Entities\Messages\Attribute::CONTROLS,
				'configure,reset',
				[
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					Entities\Messages\Attribute::CONTROLS => ['configure', 'reset'],
				],
			],
		];
	}

	/**
	 * @phpstan-param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider parseChannelPropertiesProvider
	 */
	public function testParseChannelProperties(
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$apiV1Parser = $this->container->getByType(API\V1Parser::class);

		$entity = $apiV1Parser->parse(
			Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e'),
			$topic,
			$payload,
		);

		self::assertTrue($entity instanceof Entities\Messages\ChannelProperty);
		self::assertEquals($expected, $entity->toArray());
	}

	/**
	 * @return array<string, array<string|array<string, bool|float|int|string|array<string>>>>
	 */
	public static function parseChannelPropertiesProvider(): array
	{
		return [
			'prop-property-name' => [
				'/fb/v1/device-name/$channel/channel-name/$property/property-name',
				'content',
				[
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'property' => 'property-name',
					'value' => 'content',
				],
			],
		];
	}

	/**
	 * @phpstan-param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider parseChannelPropertiesAttributesProvider
	 */
	public function testParseChannelPropertiesAttributes(
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$apiV1Parser = $this->container->getByType(API\V1Parser::class);

		$entity = $apiV1Parser->parse(
			Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e'),
			$topic,
			$payload,
		);

		self::assertTrue($entity instanceof Entities\Messages\ChannelProperty);
		self::assertEquals($expected, $entity->toArray());
	}

	/**
	 * @return array<string, array<string|array<string, bool|float|int|string|array<string>>>>
	 */
	public static function parseChannelPropertiesAttributesProvider(): array
	{
		return [
			'attr-' . Entities\Messages\PropertyAttribute::NAME => [
				'/fb/v1/device-name/$channel/channel-name/$property/some-property/$' . Entities\Messages\PropertyAttribute::NAME,
				'payload',
				[
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'property' => 'some-property',
					Entities\Messages\PropertyAttribute::NAME => 'payload',
				],
			],
			'attr-' . Entities\Messages\PropertyAttribute::SETTABLE => [
				'/fb/v1/device-name/$channel/channel-name/$property/some-property/$' . Entities\Messages\PropertyAttribute::SETTABLE,
				'true',
				[
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'property' => 'some-property',
					Entities\Messages\PropertyAttribute::SETTABLE => true,
				],
			],
			'attr-' . Entities\Messages\PropertyAttribute::QUERYABLE => [
				'/fb/v1/device-name/$channel/channel-name/$property/some-property/$' . Entities\Messages\PropertyAttribute::QUERYABLE,
				'invalid',
				[
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'property' => 'some-property',
					Entities\Messages\PropertyAttribute::QUERYABLE => false,
				],
			],
		];
	}

	/**
	 * @phpstan-param class-string<Throwable> $exception
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 * @throws Nette\DI\MissingServiceException
	 *
	 * @dataProvider parseChannelAttributesInvalidProvider
	 */
	public function testParseChannelAttributeNotValid(
		string $topic,
		string $exception,
		string $message,
	): void
	{
		$apiV1Parser = $this->container->getByType(API\V1Parser::class);

		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$apiV1Parser->parse(
			Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e'),
			$topic,
			'bar',
		);
	}

	/**
	 * @return array<string, array<string>>
	 */
	public static function parseChannelAttributesInvalidProvider(): array
	{
		return [
			'attr-' . Entities\Messages\Attribute::CHANNELS => [
				'/fb/v1/device-name/$channel/channel-name/$' . Entities\Messages\Attribute::CHANNELS,
				Exceptions\ParseMessage::class,
				'Provided topic is not valid',
			],
			'attr-other' => [
				'/fb/v1/device-name/$channel/channel-name/$other',
				Exceptions\ParseMessage::class,
				'Provided topic is not valid',
			],
		];
	}

}
