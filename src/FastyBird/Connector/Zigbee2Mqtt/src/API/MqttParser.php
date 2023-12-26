<?php declare(strict_types = 1);

/**
 * V1Parser.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           24.02.20
 */

namespace FastyBird\Connector\Zigbee2Mqtt\API;

use FastyBird\Connector\Zigbee2Mqtt\Exceptions;
use Nette;
use Ramsey\Uuid;
use function array_key_exists;
use function array_merge;
use function assert;
use function preg_match;
use function strtolower;

/**
 * MQTT topic parser
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class MqttParser
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
		if (!MqttValidator::validate($topic)) {
			throw new Exceptions\ParseMessage('Provided topic is not valid');
		}

		if (MqttValidator::validateBridge($topic)) {
			return array_merge(
				self::parseBridgeMessage($connector, $topic, $payload),
				[
					'retained' => $retained,
				],
			);
		}

		if (MqttValidator::validateDevice($topic)) {
			return array_merge(
				self::parseDeviceMessage($connector, $topic, $payload),
				[
					'retained' => $retained,
				],
			);
		}

		throw new Exceptions\ParseMessage('Provided topic is not valid');
	}

	/**
	 * @return array<string, Uuid\UuidInterface|string>
	 */
	private static function parseBridgeMessage(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
	): array
	{
		preg_match(MqttValidator::BRIDGE_REGEXP, $topic, $matches);
		assert(array_key_exists('type', $matches));

		return [
			'connector' => $connector,
			'type' => $matches['type'],
			'payload' => $payload,
		];
	}

	/**
	 * @return array<string, Uuid\UuidInterface|string|null>
	 */
	private static function parseDeviceMessage(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
	): array
	{
		if (preg_match(MqttValidator::DEVICE_WITH_ACTION_REGEXP, $topic, $matches) === 1) {
			preg_match(MqttValidator::DEVICE_WITH_ACTION_REGEXP, $topic, $matches);
			assert(array_key_exists('name', $matches));
			assert(array_key_exists('type', $matches));

			return [
				'connector' => $connector,
				'device' => $matches['name'],
				'type' => strtolower($matches['type']),
				'payload' => $payload,
			];
		} else {
			preg_match(MqttValidator::DEVICE_WITH_ACTION_REGEXP, $topic, $matches);
			assert(array_key_exists('name', $matches));

			return [
				'connector' => $connector,
				'device' => $matches['name'],
				'type' => null,
				'payload' => $payload,
			];
		}
	}

}
