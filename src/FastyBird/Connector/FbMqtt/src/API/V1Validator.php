<?php declare(strict_types = 1);

/**
 * V1Validator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     API
 * @since          0.1.0
 *
 * @date           24.02.20
 */

namespace FastyBird\Connector\FbMqtt\API;

use FastyBird\Connector\FbMqtt;
use Nette;
use function preg_match;
use function str_contains;
use function trim;

/**
 * API v1 topic validator
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class V1Validator
{

	use Nette\SmartObject;

	// TOPIC: /fb/*
	public const CONVENTION_PREFIX_REGEXP = '/^\/fb\/.*$/';

	// TOPIC: /fb/v1/*
	public const API_VERSION_REGEXP = '/^\/fb\/v1\/.*$/';

	// TOPIC: /fb/v1/<device>/$channel/<channel>/*
	public const CHANNEL_PARTIAL_REGEXP = '/^\/fb\/v1\/([a-z0-9-]+)\/\$channel\/([a-z0-9-]+)\/.*$/';

	// TOPIC: /fb/v1/<device>/<$state|$name|$properties|$controls|$channels|$extensions>
	public const DEVICE_ATTRIBUTE_REGEXP = '/^\/fb\/v1\/([a-z0-9-]+)\/\$(state|name|properties|controls|channels|extensions)$/';

	// TOPIC: /fb/v1/<device>/$hw/<mac-address|manufacturer|model|version>
	public const DEVICE_HW_INFO_REGEXP = '/^\/fb\/v1\/([a-z0-9-]+)\/\$hw\/(mac-address|manufacturer|model|version)$/';

	// TOPIC: /fb/v1/<device>/$fw/<manufacturer|name|version>
	public const DEVICE_FW_INFO_REGEXP = '/^\/fb\/v1\/([a-z0-9-]+)\/\$fw\/(manufacturer|name|version)$/';

	// TOPIC: /fb/v1/<device>/$property/<property-name>[/<$name|$settable|$queryable|$data-type|$format|$unit>]
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const DEVICE_PROPERTY_REGEXP = '/^\/fb\/v1\/([a-z0-9-]+)\/\$property\/([a-z0-9-]+)((\/\$)(name|settable|queryable|data-type|format|unit))?$/';

	// TOPIC: /fb/v1/*/$channel/<channel>/<$name|$properties|$controls>
	public const CHANNEL_ATTRIBUTE_REGEXP = '/\/(.*)\/\$channel\/([a-z0-9-]+)\/\$(name|properties|controls)$/';

	// TOPIC: /fb/v1/*/$channel/<channel>/$property/<property-name>[/<$name|$settable|$queryable|$data-type|$format|$unit>]
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const CHANNEL_PROPERTY_REGEXP = '/\/(.*)\/\$channel\/([a-z0-9-]+)\/\$property\/([a-z0-9-]+)((\/\$)(name|settable|queryable|data-type|format|unit))?$/';

	public function validate(string $topic): bool
	{
		// Check if message is sent from broker
		if (str_contains(
			trim($topic, FbMqtt\Constants::MQTT_TOPIC_DELIMITER),
			FbMqtt\Constants::MQTT_TOPIC_DELIMITER . 'set',
		)) {
			return false;
		}

		// Check for valid convention prefix
		if (!$this->validateConvention($topic)) {
			return false;
		}

		// Check for valid version V1
		if (!$this->validateVersion($topic)) {
			return false;
		}

		if ($this->validateDeviceAttribute($topic)) {
			return true;
		}

		if ($this->validateDeviceHardwareInfo($topic)) {
			return true;
		}

		if ($this->validateDeviceFirmwareInfo($topic)) {
			return true;
		}

		if ($this->validateDeviceProperty($topic)) {
			return true;
		}

		// Check for channel topics
		if ($this->validateChannelPart($topic)) {
			if ($this->validateChannelAttribute($topic)) {
				return true;
			}

			if ($this->validateChannelProperty($topic)) {
				return true;
			}
		}

		return false;
	}

	public function validateConvention(string $topic): bool
	{
		return preg_match(self::CONVENTION_PREFIX_REGEXP, $topic) === 1;
	}

	public function validateVersion(string $topic): bool
	{
		return preg_match(self::API_VERSION_REGEXP, $topic) === 1;
	}

	public function validateDeviceAttribute(string $topic): bool
	{
		return preg_match(self::DEVICE_ATTRIBUTE_REGEXP, $topic) === 1;
	}

	public function validateDeviceHardwareInfo(string $topic): bool
	{
		return preg_match(self::DEVICE_HW_INFO_REGEXP, $topic) === 1;
	}

	public function validateDeviceFirmwareInfo(string $topic): bool
	{
		return preg_match(self::DEVICE_FW_INFO_REGEXP, $topic) === 1;
	}

	public function validateDeviceProperty(string $topic): bool
	{
		return preg_match(self::DEVICE_PROPERTY_REGEXP, $topic) === 1;
	}

	public function validateChannelPart(string $topic): bool
	{
		return preg_match(self::CHANNEL_PARTIAL_REGEXP, $topic) === 1;
	}

	public function validateChannelAttribute(string $topic): bool
	{
		return $this->validateChannelPart($topic) && preg_match(self::CHANNEL_ATTRIBUTE_REGEXP, $topic) === 1;
	}

	public function validateChannelProperty(string $topic): bool
	{
		return $this->validateChannelPart($topic) && preg_match(self::CHANNEL_PROPERTY_REGEXP, $topic) === 1;
	}

}
