<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Tests\Cases\Unit\API;

use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Types\ExtensionType;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;
use Throwable;

final class ApiV1ParserTest extends TestCase
{

	/**
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDeviceAttributesProvider
	 */
	public function testParseDeviceAttribute(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 */
	public static function parseDeviceAttributesProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e');

		return [
			'attr-' . Entities\Messages\Attribute::NAME => [
				$connectorId,
				'/fb/v1/device-name/$' . Entities\Messages\Attribute::NAME,
				'Some content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'attribute' => Entities\Messages\Attribute::NAME,
					'value' => 'Some content',
				],
			],
			'attr-' . Entities\Messages\Attribute::PROPERTIES => [
				$connectorId,
				'/fb/v1/device-name/$' . Entities\Messages\Attribute::PROPERTIES,
				'prop1,prop2',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'attribute' => Entities\Messages\Attribute::PROPERTIES,
					'value' => 'prop1,prop2',
				],
			],
			'attr-' . Entities\Messages\Attribute::CHANNELS => [
				$connectorId,
				'/fb/v1/device-name/$' . Entities\Messages\Attribute::CHANNELS,
				'channel-one,channel-two',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'attribute' => Entities\Messages\Attribute::CHANNELS,
					'value' => 'channel-one,channel-two',
				],
			],
			'attr-' . Entities\Messages\Attribute::CONTROLS => [
				$connectorId,
				'/fb/v1/device-name/$' . Entities\Messages\Attribute::CONTROLS,
				'configure,reset',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'attribute' => Entities\Messages\Attribute::CONTROLS,
					'value' => 'configure,reset',
				],
			],
		];
	}

	/**
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDeviceHardwareInfoProvider
	 */
	public function testParseDeviceHardwareInfo(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 */
	public static function parseDeviceHardwareInfoProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e');

		return [
			'hw-' . Entities\Messages\ExtensionAttribute::MAC_ADDRESS => [
				$connectorId,
				'/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::MAC_ADDRESS,
				'00:0a:95:9d:68:16',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'extension' => ExtensionType::FASTYBIRD_HARDWARE,
					'parameter' => Entities\Messages\ExtensionAttribute::MAC_ADDRESS,
					'value' => '000a959d6816',
				],
			],
			'hw-' . Entities\Messages\ExtensionAttribute::MANUFACTURER => [
				$connectorId,
				'/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::MANUFACTURER,
				'value-content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'extension' => ExtensionType::FASTYBIRD_HARDWARE,
					'parameter' => Entities\Messages\ExtensionAttribute::MANUFACTURER,
					'value' => 'value-content',
				],
			],
			'hw-' . Entities\Messages\ExtensionAttribute::MODEL => [
				$connectorId,
				'/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::MODEL,
				'value-content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'extension' => ExtensionType::FASTYBIRD_HARDWARE,
					'parameter' => Entities\Messages\ExtensionAttribute::MODEL,
					'value' => 'value-content',
				],
			],
			'hw-' . Entities\Messages\ExtensionAttribute::VERSION => [
				$connectorId,
				'/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::VERSION,
				'value-content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'extension' => ExtensionType::FASTYBIRD_HARDWARE,
					'parameter' => Entities\Messages\ExtensionAttribute::VERSION,
					'value' => 'value-content',
				],
			],
		];
	}

	/**
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDeviceFirmwareInfoProvider
	 */
	public function testParseDeviceFirmwareInfo(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 */
	public static function parseDeviceFirmwareInfoProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e');

		return [
			'fw-' . Entities\Messages\ExtensionAttribute::MANUFACTURER => [
				$connectorId,
				'/fb/v1/device-name/$fw/' . Entities\Messages\ExtensionAttribute::MANUFACTURER,
				'value-content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'extension' => ExtensionType::FASTYBIRD_FIRMWARE,
					'parameter' => Entities\Messages\ExtensionAttribute::MANUFACTURER,
					'value' => 'value-content',
				],
			],
			'fw-' . Entities\Messages\ExtensionAttribute::VERSION => [
				$connectorId,
				'/fb/v1/device-name/$fw/' . Entities\Messages\ExtensionAttribute::VERSION,
				'value-content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'extension' => ExtensionType::FASTYBIRD_FIRMWARE,
					'parameter' => Entities\Messages\ExtensionAttribute::VERSION,
					'value' => 'value-content',
				],
			],
		];
	}

	/**
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDevicePropertiesProvider
	 */
	public function testParseDeviceProperties(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 */
	public static function parseDevicePropertiesProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e');

		return [
			'prop-property-name' => [
				$connectorId,
				'/fb/v1/device-name/$property/property-name',
				'content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'property' => 'property-name',
					'value' => 'content',
				],
			],
		];
	}

	/**
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDevicePropertiesAttributesProvider
	 */
	public function testParseDevicePropertiesAttributes(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, array<int, array<string, string>>|bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 */
	public static function parseDevicePropertiesAttributesProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e');

		return [
			'attr-' . Entities\Messages\PropertyAttribute::NAME => [
				$connectorId,
				'/fb/v1/device-name/$property/some-property/$' . Entities\Messages\PropertyAttribute::NAME,
				'payload',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'property' => 'some-property',
					'attributes' => [
						[
							'attribute' => Entities\Messages\PropertyAttribute::NAME,
							'value' => 'payload',
						],
					],
				],
			],
			'attr-' . Entities\Messages\PropertyAttribute::SETTABLE => [
				$connectorId,
				'/fb/v1/device-name/$property/some-property/$' . Entities\Messages\PropertyAttribute::SETTABLE,
				'true',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'property' => 'some-property',
					'attributes' => [
						[
							'attribute' => Entities\Messages\PropertyAttribute::SETTABLE,
							'value' => 'true',
						],
					],
				],
			],
			'attr-' . Entities\Messages\PropertyAttribute::QUERYABLE => [
				$connectorId,
				'/fb/v1/device-name/$property/some-property/$' . Entities\Messages\PropertyAttribute::QUERYABLE,
				'invalid',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'retained' => false,
					'property' => 'some-property',
					'attributes' => [
						[
							'attribute' => Entities\Messages\PropertyAttribute::QUERYABLE,
							'value' => 'invalid',
						],
					],
				],
			],
		];
	}

	/**
	 * @param class-string<Throwable> $exception
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDeviceAttributesInvalidProvider
	 */
	public function testParseDeviceAttributeNotValid(
		string $topic,
		string $exception,
		string $message,
	): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		API\V1Parser::parse(
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
	 * @param class-string<Throwable> $exception
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDeviceHardwareInfoInvalidProvider
	 */
	public function testParseDeviceHardwareInfoNotValid(
		string $topic,
		string $exception,
		string $message,
	): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		API\V1Parser::parse(
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
	 * @param class-string<Throwable> $exception
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseDeviceFirmwareInfoInvalidProvider
	 */
	public function testParseDeviceFirmwareInfoNotValid(
		string $topic,
		string $exception,
		string $message,
	): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		API\V1Parser::parse(
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
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseChannelAttributesProvider
	 */
	public function testParseChannelAttributes(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 */
	public static function parseChannelAttributesProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e');

		return [
			'attr-' . Entities\Messages\Attribute::NAME => [
				$connectorId,
				'/fb/v1/device-name/$channel/channel-name/$' . Entities\Messages\Attribute::NAME,
				'Some content',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'attribute' => Entities\Messages\Attribute::NAME,
					'value' => 'Some content',
				],
			],
			'attr-' . Entities\Messages\Attribute::PROPERTIES => [
				$connectorId,
				'/fb/v1/device-name/$channel/channel-name/$' . Entities\Messages\Attribute::PROPERTIES,
				'prop1,prop2',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'attribute' => Entities\Messages\Attribute::PROPERTIES,
					'value' => 'prop1,prop2',
				],
			],
			'attr-' . Entities\Messages\Attribute::CONTROLS => [
				$connectorId,
				'/fb/v1/device-name/$channel/channel-name/$' . Entities\Messages\Attribute::CONTROLS,
				'configure,reset',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'attribute' => Entities\Messages\Attribute::CONTROLS,
					'value' => 'configure,reset',
				],
			],
		];
	}

	/**
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseChannelPropertiesProvider
	 */
	public function testParseChannelProperties(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 */
	public static function parseChannelPropertiesProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e');

		return [
			'prop-property-name' => [
				$connectorId,
				'/fb/v1/device-name/$channel/channel-name/$property/property-name',
				'content',
				[
					'connector' => $connectorId,
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
	 * @param array<string, bool|float|int|string|array<string>> $expected
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseChannelPropertiesAttributesProvider
	 */
	public function testParseChannelPropertiesAttributes(
		Uuid\UuidInterface $connectorId,
		string $topic,
		string $payload,
		array $expected,
	): void
	{
		$data = API\V1Parser::parse($connectorId, $topic, $payload);

		self::assertEquals($expected, $data);
	}

	/**
	 * @return array<string, array<int, array<string, array<int, array<string, bool|string>>|bool|Uuid\UuidInterface|string>|Uuid\UuidInterface|string>>
	 */
	public static function parseChannelPropertiesAttributesProvider(): array
	{
		$connectorId = Uuid\Uuid::fromString('17c59Dfa-2edd-438e-8c49f-aa4e38e5a5e');

		return [
			'attr-' . Entities\Messages\PropertyAttribute::NAME => [
				$connectorId,
				'/fb/v1/device-name/$channel/channel-name/$property/some-property/$' . Entities\Messages\PropertyAttribute::NAME,
				'payload',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'property' => 'some-property',
					'attributes' => [
						[
							'attribute' => Entities\Messages\PropertyAttribute::NAME,
							'value' => 'payload',
						],
					],
				],
			],
			'attr-' . Entities\Messages\PropertyAttribute::SETTABLE => [
				$connectorId,
				'/fb/v1/device-name/$channel/channel-name/$property/some-property/$' . Entities\Messages\PropertyAttribute::SETTABLE,
				'true',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'property' => 'some-property',
					'attributes' => [
						[
							'attribute' => Entities\Messages\PropertyAttribute::SETTABLE,
							'value' => true,
						],
					],
				],
			],
			'attr-' . Entities\Messages\PropertyAttribute::QUERYABLE => [
				$connectorId,
				'/fb/v1/device-name/$channel/channel-name/$property/some-property/$' . Entities\Messages\PropertyAttribute::QUERYABLE,
				'invalid',
				[
					'connector' => $connectorId,
					'device' => 'device-name',
					'channel' => 'channel-name',
					'retained' => false,
					'property' => 'some-property',
					'attributes' => [
						[
							'attribute' => Entities\Messages\PropertyAttribute::QUERYABLE,
							'value' => 'invalid',
						],
					],
				],
			],
		];
	}

	/**
	 * @param class-string<Throwable> $exception
	 *
	 * @throws Exceptions\ParseMessage
	 *
	 * @dataProvider parseChannelAttributesInvalidProvider
	 */
	public function testParseChannelAttributeNotValid(
		string $topic,
		string $exception,
		string $message,
	): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		API\V1Parser::parse(
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
