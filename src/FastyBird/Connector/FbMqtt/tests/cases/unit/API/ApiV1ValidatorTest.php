<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Tests\Cases\Unit\API;

use FastyBird\Connector\FbMqtt\API;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Tests\Cases\Unit\BaseTestCase;

final class ApiV1ValidatorTest extends BaseTestCase
{

	public function testValidateDevices(): void
	{
		$apiV1Validator = new API\V1Validator();

		self::assertTrue($apiV1Validator->validateVersion('/fb/v1/device-name/'));
		self::assertFalse($apiV1Validator->validateVersion('/fb/v2/device-name/'));
		self::assertFalse($apiV1Validator->validateVersion('/v1/device-name/'));
		self::assertFalse($apiV1Validator->validateVersion('/v2/device-name/'));

		self::assertTrue($apiV1Validator->validate('/fb/v1/device-name/$' . Entities\Messages\Attribute::NAME));
		self::assertTrue($apiV1Validator->validate('/fb/v1/device-name/$' . Entities\Messages\Attribute::PROPERTIES));
		self::assertTrue($apiV1Validator->validate('/fb/v1/device-name/$' . Entities\Messages\Attribute::CHANNELS));
		self::assertTrue($apiV1Validator->validate('/fb/v1/device-name/$' . Entities\Messages\Attribute::EXTENSIONS));
		self::assertTrue($apiV1Validator->validate('/fb/v1/device-name/$' . Entities\Messages\Attribute::CONTROLS));
		self::assertFalse($apiV1Validator->validate('/fb/v1/device-name/$invalid'));
		self::assertFalse($apiV1Validator->validate('/fb/v1/device-name/invalid'));
		self::assertFalse(
			$apiV1Validator->validate('/fb/v1/device-name/$' . Entities\Messages\Attribute::NAME . '/test'),
		);
		self::assertFalse($apiV1Validator->validate('/fb/v1/device-name/' . Entities\Messages\Attribute::NAME));
		self::assertFalse($apiV1Validator->validate('/fb/v1/Device-Name/$' . Entities\Messages\Attribute::EXTENSIONS));

		self::assertTrue(
			$apiV1Validator->validate('/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::MAC_ADDRESS),
		);
		self::assertTrue(
			$apiV1Validator->validate('/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::MANUFACTURER),
		);
		self::assertTrue(
			$apiV1Validator->validate('/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::MODEL),
		);
		self::assertTrue(
			$apiV1Validator->validate('/fb/v1/device-name/$hw/' . Entities\Messages\ExtensionAttribute::VERSION),
		);
		self::assertFalse(
			$apiV1Validator->validate('/fb/v1/device-name/$mw/' . Entities\Messages\ExtensionAttribute::VERSION),
		);
		self::assertFalse(
			$apiV1Validator->validate('/fb/v1/device-name/$hardware/' . Entities\Messages\ExtensionAttribute::VERSION),
		);
		self::assertFalse(
			$apiV1Validator->validate('/fb/v1/device-name/$hware/' . Entities\Messages\ExtensionAttribute::VERSION),
		);

		self::assertTrue(
			$apiV1Validator->validate('/fb/v1/device-name/$fw/' . Entities\Messages\ExtensionAttribute::MANUFACTURER),
		);
		self::assertTrue(
			$apiV1Validator->validate('/fb/v1/device-name/$fw/' . Entities\Messages\ExtensionAttribute::VERSION),
		);
		self::assertFalse(
			$apiV1Validator->validate('/fb/v1/device-name/$mw/' . Entities\Messages\ExtensionAttribute::VERSION),
		);
		self::assertFalse(
			$apiV1Validator->validate('/fb/v1/device-name/$firmware/' . Entities\Messages\ExtensionAttribute::VERSION),
		);
		self::assertFalse(
			$apiV1Validator->validate('/fb/v1/device-name/$fware/' . Entities\Messages\ExtensionAttribute::VERSION),
		);

		self::assertFalse($apiV1Validator->validate('/fb/v1/device-name'));
		self::assertFalse($apiV1Validator->validate('/fb/v1/$device-name/$' . Entities\Messages\Attribute::NAME));
		self::assertFalse($apiV1Validator->validate('/fb/v1/device?-name/$' . Entities\Messages\Attribute::NAME));
		self::assertFalse($apiV1Validator->validate('/fb/v1/dev&ice-name/$' . Entities\Messages\Attribute::NAME));
		self::assertFalse($apiV1Validator->validate('/fb/v1/dev*ice-name/$' . Entities\Messages\Attribute::NAME));
		self::assertFalse($apiV1Validator->validate('/fb/v1/device_name/$' . Entities\Messages\Attribute::NAME));
		self::assertFalse($apiV1Validator->validate('/fb/v1/device.name/$' . Entities\Messages\Attribute::NAME));
	}

	public function testValidateChannels(): void
	{
		$apiV1Validator = new API\V1Validator();

		self::assertTrue($apiV1Validator->validateChannelPart('/fb/v1/device-name/$channel/channel-name/'));
		self::assertTrue($apiV1Validator->validateChannelPart('/fb/v1/device-name/$channel/channel-name/whatever'));
		self::assertFalse($apiV1Validator->validateChannelPart('/fb/v1/device-name/$channel/channel-name'));
		self::assertFalse($apiV1Validator->validateChannelPart('/fb/v1/device-name/$whatever/channel-name/'));
		self::assertFalse($apiV1Validator->validateChannelPart('/fb/v1/device-name/channel/channel-name/'));

		self::assertTrue(
			$apiV1Validator->validateChannelAttribute(
				'/fb/v1/whatever/$channel/channel-name/$' . Entities\Messages\Attribute::NAME,
			),
		);
		self::assertTrue(
			$apiV1Validator->validateChannelAttribute(
				'/fb/v1/whatever/$channel/channel-name/$' . Entities\Messages\Attribute::PROPERTIES,
			),
		);
		self::assertTrue(
			$apiV1Validator->validateChannelAttribute(
				'/fb/v1/whatever/$channel/channel-name/$' . Entities\Messages\Attribute::CONTROLS,
			),
		);
		self::assertFalse($apiV1Validator->validateChannelAttribute('/fb/v1/whatever/$channel/channel-name/$unknown'));

		self::assertTrue(
			$apiV1Validator->validateChannelProperty('/fb/v1/whatever/$channel/channel-name/$property/property-name'),
		);
		self::assertTrue(
			$apiV1Validator->validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/$' . Entities\Messages\PropertyAttribute::NAME,
			),
		);
		self::assertTrue(
			$apiV1Validator->validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/$' . Entities\Messages\PropertyAttribute::SETTABLE,
			),
		);
		self::assertTrue(
			$apiV1Validator->validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/$' . Entities\Messages\PropertyAttribute::QUERYABLE,
			),
		);
		self::assertTrue(
			$apiV1Validator->validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/$' . Entities\Messages\PropertyAttribute::DATA_TYPE,
			),
		);
		self::assertTrue(
			$apiV1Validator->validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/$' . Entities\Messages\PropertyAttribute::FORMAT,
			),
		);
		self::assertTrue(
			$apiV1Validator->validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/$' . Entities\Messages\PropertyAttribute::UNIT,
			),
		);
		self::assertFalse(
			$apiV1Validator->validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/' . Entities\Messages\PropertyAttribute::UNIT,
			),
		);
		self::assertFalse(
			$apiV1Validator->validateChannelProperty(
				'/fb/v1/whatever/$channel/channel-name/$property/property-name/$invalid',
			),
		);
	}

}
