<?php declare(strict_types = 1);

/**
 * V1Parser.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           24.02.20
 */

namespace FastyBird\Connector\FbMqtt\API;

use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Types;
use Nette;
use Ramsey\Uuid;
use function array_merge;
use function preg_match;
use function strtolower;

/**
 * API v1 topic parser
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class V1Parser
{

	use Nette\SmartObject;

	/**
	 * @return array<string, mixed>
	 *
	 * @throws Exceptions\ParseMessage
	 */
	public static function parse(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
		bool $retained = false,
	): array
	{
		if (!V1Validator::validate($topic)) {
			throw new Exceptions\ParseMessage('Provided topic is not valid');
		}

		if (V1Validator::validateDeviceAttribute($topic)) {
			return array_merge(
				self::parseDeviceAttribute($connector, $topic, $payload),
				[
					'retained' => $retained,
				],
			);
		}

		if (V1Validator::validateDeviceHardwareInfo($topic)) {
			return array_merge(
				self::parseDeviceHardwareInfo($connector, $topic, $payload),
				[
					'retained' => $retained,
				],
			);
		}

		if (V1Validator::validateDeviceFirmwareInfo($topic)) {
			return array_merge(
				self::parseDeviceFirmwareInfo($connector, $topic, $payload),
				[
					'retained' => $retained,
				],
			);
		}

		if (V1Validator::validateDeviceProperty($topic)) {
			return array_merge(
				self::parseDeviceProperty($connector, $topic, $payload),
				[
					'retained' => $retained,
				],
			);
		}

		if (V1Validator::validateChannelPart($topic)) {
			preg_match(V1Validator::CHANNEL_PARTIAL_REGEXP, $topic, $matches);
			[, $device] = $matches;

			if (V1Validator::validateChannelAttribute($topic)) {
				return array_merge(
					self::parseChannelAttribute($connector, $device, $topic, $payload),
					[
						'retained' => $retained,
					],
				);
			}

			if (V1Validator::validateChannelProperty($topic)) {
				return array_merge(
					self::parseChannelProperty($connector, $device, $topic, $payload),
					[
						'retained' => $retained,
					],
				);
			}
		}

		throw new Exceptions\ParseMessage('Provided topic is not valid');
	}

	/**
	 * @return array<string, Uuid\UuidInterface|string>
	 */
	private static function parseDeviceAttribute(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
	): array
	{
		preg_match(V1Validator::DEVICE_ATTRIBUTE_REGEXP, $topic, $matches);
		[, $device, $attribute] = $matches;

		return [
			'connector' => $connector,
			'device' => $device,
			'attribute' => $attribute,
			'value' => $payload,
		];
	}

	/**
	 * @return array<string, Uuid\UuidInterface|string>
	 */
	private static function parseDeviceHardwareInfo(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
	): array
	{
		preg_match(V1Validator::DEVICE_HW_INFO_REGEXP, $topic, $matches);
		[, $device, $hardware] = $matches;

		return [
			'connector' => $connector,
			'device' => $device,
			'extension' => Types\ExtensionType::FASTYBIRD_HARDWARE->value,
			'parameter' => $hardware,
			'value' => Helpers\Payload::cleanName(strtolower($payload)),
		];
	}

	/**
	 * @return array<string, Uuid\UuidInterface|string>
	 */
	private static function parseDeviceFirmwareInfo(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
	): array
	{
		preg_match(V1Validator::DEVICE_FW_INFO_REGEXP, $topic, $matches);
		[, $device, $firmware] = $matches;

		return [
			'connector' => $connector,
			'device' => $device,
			'extension' => Types\ExtensionType::FASTYBIRD_FIRMWARE->value,
			'parameter' => $firmware,
			'value' => Helpers\Payload::cleanName(strtolower($payload)),
		];
	}

	/**
	 * @return array<string, Uuid\UuidInterface|string|array<int, array<string, string>>|null>
	 */
	private static function parseDeviceProperty(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
	): array
	{
		preg_match(V1Validator::DEVICE_PROPERTY_REGEXP, $topic, $matches);
		[, $device, $property, , , $attribute] = $matches + [null, null, null, null, null, null];

		$data = [
			'connector' => $connector,
			'device' => $device,
			'property' => $property,
		];

		return $attribute !== null ? array_merge($data, [
			'attributes' => [
				[
					'attribute' => $attribute,
					'value' => Helpers\Payload::cleanPayload($payload),
				],
			],
		]) : array_merge($data, [
			'value' => $payload,
		]);
	}

	/**
	 * @return array<string, Uuid\UuidInterface|string>
	 */
	private static function parseChannelAttribute(
		Uuid\UuidInterface $connector,
		string $device,
		string $topic,
		string $payload,
	): array
	{
		preg_match(V1Validator::CHANNEL_ATTRIBUTE_REGEXP, $topic, $matches);
		[, , $channel, $attribute] = $matches;

		return [
			'connector' => $connector,
			'device' => $device,
			'channel' => $channel,
			'attribute' => $attribute,
			'value' => $payload,
		];
	}

	/**
	 * @return array<string, Uuid\UuidInterface|string|array<int, array<string, string>>|null>
	 */
	private static function parseChannelProperty(
		Uuid\UuidInterface $connector,
		string $device,
		string $topic,
		string $payload,
	): array
	{
		preg_match(V1Validator::CHANNEL_PROPERTY_REGEXP, $topic, $matches);
		[, , $channel, $property, , , $attribute] = $matches + [null, null, null, null, null, null, null];

		$data = [
			'connector' => $connector,
			'device' => $device,
			'channel' => $channel,
			'property' => $property,
		];

		return $attribute !== null ? array_merge($data, [
			'attributes' => [
				[
					'attribute' => $attribute,
					'value' => Helpers\Payload::cleanPayload($payload),
				],
			],
		]) : array_merge($data, [
			'value' => $payload,
		]);
	}

}
