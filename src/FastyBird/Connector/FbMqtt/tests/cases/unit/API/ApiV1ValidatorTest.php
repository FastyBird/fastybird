<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Tests\Cases\Unit\API;

use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Queue;
use PHPUnit\Framework\TestCase;

final class ApiV1ValidatorTest extends TestCase
{

	public function testValidateDevices(): void
	{
		self::assertTrue(API\V1Validator::validateVersion('/fb/v1/device-name/'));
		self::assertFalse(API\V1Validator::validateVersion('/fb/v2/device-name/'));
		self::assertFalse(API\V1Validator::validateVersion('/v1/device-name/'));
		self::assertFalse(API\V1Validator::validateVersion('/v2/device-name/'));

		self::assertTrue(API\V1Validator::validate('/fb/v1/device-name/$' . Queue\Messages\Attribute::NAME));
		self::assertTrue(
			API\V1Validator::validate('/fb/v1/device-name/$' . Queue\Messages\Attribute::PROPERTIES),
		);
		self::assertTrue(
			API\V1Validator::validate('/fb/v1/device-name/$' . Queue\Messages\Attribute::CHANNELS),
		);
		self::assertTrue(
			API\V1Validator::validate('/fb/v1/device-name/$' . Queue\Messages\Attribute::EXTENSIONS),
		);
		self::assertTrue(
			API\V1Validator::validate('/fb/v1/device-name/$' . Queue\Messages\Attribute::CONTROLS),
		);
		self::assertFalse(API\V1Validator::validate('/fb/v1/device-name/$invalid'));
		self::assertFalse(API\V1Validator::validate('/fb/v1/device-name/invalid'));
		self::assertFalse(
			API\V1Validator::validate(
				'/fb/v1/device-name/$' . Queue\Messages\Attribute::NAME . '/test',
			),
		);
		self::assertFalse(
			API\V1Validator::validate('/fb/v1/device-name/' . Queue\Messages\Attribute::NAME),
		);
		self::assertFalse(
			API\V1Validator::validate('/fb/v1/Device-Name/$' . Queue\Messages\Attribute::EXTENSIONS),
		);

		self::assertTrue(
			API\V1Validator::validate('/fb/v1/device-name/$hw/' . Queue\Messages\ExtensionAttribute::MAC_ADDRESS),
		);
		self::assertTrue(
			API\V1Validator::validate(
				'/fb/v1/device-name/$hw/' . Queue\Messages\ExtensionAttribute::MANUFACTURER,
			),
		);
		self::assertTrue(
			API\V1Validator::validate(
				'/fb/v1/device-name/$hw/' . Queue\Messages\ExtensionAttribute::MODEL,
			),
		);
		self::assertTrue(
			API\V1Validator::validate(
				'/fb/v1/device-name/$hw/' . Queue\Messages\ExtensionAttribute::VERSION,
			),
		);
		self::assertFalse(
			API\V1Validator::validate(
				'/fb/v1/device-name/$mw/' . Queue\Messages\ExtensionAttribute::VERSION,
			),
		);
		self::assertFalse(
			API\V1Validator::validate(
				'/fb/v1/device-name/$hardware/' . Queue\Messages\ExtensionAttribute::VERSION,
			),
		);
		self::assertFalse(
			API\V1Validator::validate(
				'/fb/v1/device-name/$hware/' . Queue\Messages\ExtensionAttribute::VERSION,
			),
		);

		self::assertTrue(
			API\V1Validator::validate(
				'/fb/v1/device-name/$fw/' . Queue\Messages\ExtensionAttribute::MANUFACTURER,
			),
		);
		self::assertTrue(
			API\V1Validator::validate(
				'/fb/v1/device-name/$fw/' . Queue\Messages\ExtensionAttribute::VERSION,
			),
		);
		self::assertFalse(
			API\V1Validator::validate(
				'/fb/v1/device-name/$mw/' . Queue\Messages\ExtensionAttribute::VERSION,
			),
		);
		self::assertFalse(
			API\V1Validator::validate(
				'/fb/v1/device-name/$firmware/' . Queue\Messages\ExtensionAttribute::VERSION,
			),
		);
		self::assertFalse(
			API\V1Validator::validate(
				'/fb/v1/device-name/$fware/' . Queue\Messages\ExtensionAttribute::VERSION,
			),
		);

		self::assertFalse(API\V1Validator::validate('/fb/v1/device-name'));
		self::assertFalse(
			API\V1Validator::validate('/fb/v1/$device-name/$' . Queue\Messages\Attribute::NAME),
		);
		self::assertFalse(
			API\V1Validator::validate('/fb/v1/device?-name/$' . Queue\Messages\Attribute::NAME),
		);
		self::assertFalse(
			API\V1Validator::validate('/fb/v1/dev&ice-name/$' . Queue\Messages\Attribute::NAME),
		);
		self::assertFalse(
			API\V1Validator::validate('/fb/v1/dev*ice-name/$' . Queue\Messages\Attribute::NAME),
		);
		self::assertFalse(
			API\V1Validator::validate('/fb/v1/device_name/$' . Queue\Messages\Attribute::NAME),
		);
		self::assertFalse(
			API\V1Validator::validate('/fb/v1/device.name/$' . Queue\Messages\Attribute::NAME),
		);
	}

	public function testValidateChannels(): void
	{
		self::assertTrue(API\V1Validator::validateChannelPart('/fb/v1/device-name/$channel/channel-name/'));
		self::assertTrue(API\V1Validator::validateChannelPart('/fb/v1/device-name/$channel/channel-name/whatever'));
		self::assertFalse(API\V1Validator::validateChannelPart('/fb/v1/device-name/$channel/channel-name'));
		self::assertFalse(API\V1Validator::validateChannelPart('/fb/v1/device-name/$whatever/channel-name/'));
		self::assertFalse(API\V1Validator::validateChannelPart('/fb/v1/device-name/channel/channel-name/'));

		self::assertTrue(
			API\V1Validator::validateChannelAttribute(
				'/fb/v1/whatever/$channel/channel-name/$' . Queue\Messages\Attribute::NAME,
			),
		);
		self::assertTrue(
			API\V1Validator::validateChannelAttribute(
				'/fb/v1/whatever/$channel/channel-name/$' . Queue\Messages\Attribute::PROPERTIES,
			),
		);
		self::assertTrue(
			API\V1Validator::validateChannelAttribute(
				'/fb/v1/whatever/$channel/channel-name/$' . Queue\Messages\Attribute::CONTROLS,
			),
		);
		self::assertFalse(API\V1Validator::validateChannelAttribute('/fb/v1/whatever/$channel/channel-name/$unknown'));

		self::assertTrue(
			API\V1Validator::validateChannelProperty('/fb/v1/whatever/$channel/channel-name/$property/property-name'),
		);
		self::assertTrue(
			API\V1Validator::validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/$' . Queue\Messages\PropertyAttribute::NAME,
			),
		);
		self::assertTrue(
			API\V1Validator::validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/$' . Queue\Messages\PropertyAttribute::SETTABLE,
			),
		);
		self::assertTrue(
			API\V1Validator::validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/$' . Queue\Messages\PropertyAttribute::QUERYABLE,
			),
		);
		self::assertTrue(
			API\V1Validator::validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/$' . Queue\Messages\PropertyAttribute::DATA_TYPE,
			),
		);
		self::assertTrue(
			API\V1Validator::validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/$' . Queue\Messages\PropertyAttribute::FORMAT,
			),
		);
		self::assertTrue(
			API\V1Validator::validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/$' . Queue\Messages\PropertyAttribute::UNIT,
			),
		);
		self::assertFalse(
			API\V1Validator::validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/' . Queue\Messages\PropertyAttribute::UNIT,
			),
		);
		self::assertFalse(
			API\V1Validator::validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/$invalid',
			),
		);
	}

}
