<?php declare(strict_types = 1);

/**
 * V1Parser.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     API
 * @since          0.1.0
 *
 * @date           24.02.20
 */

namespace FastyBird\FbMqttConnector\API;

use FastyBird\FbMqttConnector\Entities;
use FastyBird\FbMqttConnector\Exceptions;
use FastyBird\FbMqttConnector\Helpers;
use FastyBird\FbMqttConnector\Types;
use Nette;
use Ramsey\Uuid;
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

	public function __construct(private readonly V1Validator $validator)
	{
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 */
	public function parse(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
		bool $retained = false,
	): Entities\Messages\Entity
	{
		if (!$this->validator->validate($topic)) {
			throw new Exceptions\ParseMessage('Provided topic is not valid');
		}

		if ($this->validator->validateDeviceAttribute($topic)) {
			$entity = $this->parseDeviceAttribute($connector, $topic, $payload);
			$entity->setRetained($retained);

			return $entity;
		}

		if ($this->validator->validateDeviceHardwareInfo($topic)) {
			$entity = $this->parseDeviceHardwareInfo($connector, $topic, $payload);
			$entity->setRetained($retained);

			return $entity;
		}

		if ($this->validator->validateDeviceFirmwareInfo($topic)) {
			$entity = $this->parseDeviceFirmwareInfo($connector, $topic, $payload);
			$entity->setRetained($retained);

			return $entity;
		}

		if ($this->validator->validateDeviceProperty($topic)) {
			$entity = $this->parseDeviceProperty($connector, $topic, $payload);
			$entity->setRetained($retained);

			return $entity;
		}

		if ($this->validator->validateChannelPart($topic)) {
			preg_match(V1Validator::CHANNEL_PARTIAL_REGEXP, $topic, $matches);
			[, $device] = $matches;

			if ($this->validator->validateChannelAttribute($topic)) {
				$entity = $this->parseChannelAttribute($connector, $device, $topic, $payload);
				$entity->setRetained($retained);

				return $entity;
			}

			if ($this->validator->validateChannelProperty($topic)) {
				$entity = $this->parseChannelProperty($connector, $device, $topic, $payload);
				$entity->setRetained($retained);

				return $entity;
			}
		}

		throw new Exceptions\ParseMessage('Provided topic is not valid');
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	private function parseDeviceAttribute(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
	): Entities\Messages\DeviceAttribute
	{
		preg_match(V1Validator::DEVICE_ATTRIBUTE_REGEXP, $topic, $matches);
		[, $device, $attribute] = $matches;

		return new Entities\Messages\DeviceAttribute(
			$connector,
			$device,
			$attribute,
			$payload,
		);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	private function parseDeviceHardwareInfo(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
	): Entities\Messages\ExtensionAttribute
	{
		preg_match(V1Validator::DEVICE_HW_INFO_REGEXP, $topic, $matches);
		[, $device, $hardware] = $matches;

		return new Entities\Messages\ExtensionAttribute(
			$connector,
			$device,
			Types\ExtensionType::get(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_HARDWARE),
			$hardware,
			Helpers\Payload::cleanName(strtolower($payload)),
		);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	private function parseDeviceFirmwareInfo(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
	): Entities\Messages\ExtensionAttribute
	{
		preg_match(V1Validator::DEVICE_FW_INFO_REGEXP, $topic, $matches);
		[, $device, $firmware] = $matches;

		return new Entities\Messages\ExtensionAttribute(
			$connector,
			$device,
			Types\ExtensionType::get(Types\ExtensionType::EXTENSION_TYPE_FASTYBIRD_FIRMWARE),
			$firmware,
			Helpers\Payload::cleanName(strtolower($payload)),
		);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 */
	private function parseDeviceProperty(
		Uuid\UuidInterface $connector,
		string $topic,
		string $payload,
	): Entities\Messages\DeviceProperty
	{
		preg_match(V1Validator::DEVICE_PROPERTY_REGEXP, $topic, $matches);
		[, $device, $property, , , $attribute] = $matches + [null, null, null, null, null, null];

		$entity = new Entities\Messages\DeviceProperty($connector, (string) $device, (string) $property);

		if ($attribute !== null) {
			$entity->addAttribute(
				new Entities\Messages\PropertyAttribute($attribute, Helpers\Payload::cleanPayload($payload)),
			);

		} else {
			$entity->setValue($payload);
		}

		return $entity;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	private function parseChannelAttribute(
		Uuid\UuidInterface $connector,
		string $device,
		string $topic,
		string $payload,
	): Entities\Messages\ChannelAttribute
	{
		preg_match(V1Validator::CHANNEL_ATTRIBUTE_REGEXP, $topic, $matches);
		[, , $channel, $attribute] = $matches;

		return new Entities\Messages\ChannelAttribute(
			$connector,
			$device,
			$channel,
			$attribute,
			$payload,
		);
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\ParseMessage
	 */
	private function parseChannelProperty(
		Uuid\UuidInterface $connector,
		string $device,
		string $topic,
		string $payload,
	): Entities\Messages\ChannelProperty
	{
		preg_match(V1Validator::CHANNEL_PROPERTY_REGEXP, $topic, $matches);
		[, , $channel, $property, , , $attribute] = $matches + [null, null, null, null, null, null, null];

		$entity = new Entities\Messages\ChannelProperty(
			$connector,
			$device,
			(string) $channel,
			(string) $property,
		);

		if ($attribute !== null) {
			$entity->addAttribute(
				new Entities\Messages\PropertyAttribute($attribute, Helpers\Payload::cleanPayload($payload)),
			);

		} else {
			$entity->setValue($payload);
		}

		return $entity;
	}

}
