<?php declare(strict_types = 1);

/**
 * Gen1Parser.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          0.37.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly\API;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Mappers;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use function array_key_exists;
use function array_map;
use function array_values;
use function boolval;
use function count;
use function explode;
use function floatval;
use function in_array;
use function intval;
use function is_array;
use function is_string;
use function strval;
use const DIRECTORY_SEPARATOR;

/**
 * Generation 1 devices messages parser
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gen1Parser
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Gen1Validator $validator,
		private readonly Gen1Transformer $transformer,
		private readonly Mappers\Sensor $sensorMapper,
		private readonly MetadataSchemas\Validator $schemaValidator,
	)
	{
	}

	/**
	 * @throws Exceptions\ParseMessage
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function parseCoapDescriptionMessage(
		Uuid\UuidInterface $connector,
		string $address,
		string $type,
		string $identifier,
		string $message,
	): Entities\Messages\DeviceDescription
	{
		if (!$this->validator->isValidCoapDescriptionMessage($message)) {
			throw new Exceptions\ParseMessage('Provided description message is not valid');
		}

		$filePath = Shelly\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . Gen1Validator::COAP_DESCRIPTION_MESSAGE_SCHEMA_FILENAME;

		try {
			$schema = Utils\FileSystem::read($filePath);

		} catch (Nette\IOException) {
			throw new Exceptions\ParseMessage('Validation schema for message could not be loaded');
		}

		$parsedMessage = $this->schemaValidator->validate($message, $schema);

		$blocks = $this->extractBlocksDescription(
			Types\MessageSource::get(Types\MessageSource::SOURCE_GEN_1_COAP),
			$parsedMessage,
		);

		return new Entities\Messages\DeviceDescription(
			Types\MessageSource::get(Types\MessageSource::SOURCE_GEN_1_COAP),
			$connector,
			$identifier,
			$type,
			$address,
			$blocks,
		);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\ParseMessage
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function parseCoapStatusMessage(
		Uuid\UuidInterface $connector,
		string $address,
		string $type,
		string $identifier,
		string $message,
	): Entities\Messages\DeviceStatus
	{
		if (!$this->validator->isValidCoapStatusMessage($message)) {
			throw new Exceptions\ParseMessage('Provided description message is not valid');
		}

		$filePath = Shelly\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . Gen1Validator::COAP_STATUS_MESSAGE_SCHEMA_FILENAME;

		try {
			$schema = Utils\FileSystem::read($filePath);

		} catch (Nette\IOException) {
			throw new Exceptions\ParseMessage('Validation schema for message could not be loaded');
		}

		$parsedMessage = $this->schemaValidator->validate($message, $schema);

		if (
			!$parsedMessage->offsetExists('G')
			|| !$parsedMessage['G'] instanceof Utils\ArrayHash
		) {
			throw new Exceptions\ParseMessage('Provided message is not valid');
		}

		$channels = [];

		foreach ($parsedMessage['G'] as $sensorState) {
			if ((is_array($sensorState) || $sensorState instanceof Utils\ArrayHash) && count($sensorState) === 3) {
				[$channel, $sensorIdentifier, $sensorValue] = (array) $sensorState;

				if (!array_key_exists($channel, $channels)) {
					$channels[$channel] = new Entities\Messages\ChannelStatus(
						Types\MessageSource::get(Types\MessageSource::SOURCE_GEN_1_COAP),
						$channel,
					);
				}

				$property = $this->sensorMapper->findProperty(
					$connector,
					$identifier,
					intval($sensorIdentifier),
				);

				if ($property !== null) {
					$channels[$channel]->addSensor(
						new Entities\Messages\SensorStatus(
							Types\MessageSource::get(Types\MessageSource::SOURCE_GEN_1_COAP),
							intval($sensorIdentifier),
							$this->transformer->transformValueFromDevice(
								$property->getDataType(),
								$property->getFormat(),
								strval($sensorValue),
							),
						),
					);
				}
			}
		}

		return new Entities\Messages\DeviceStatus(
			Types\MessageSource::get(Types\MessageSource::SOURCE_GEN_1_COAP),
			$connector,
			$identifier,
			$type,
			$address,
			array_values($channels),
		);
	}

	/**
	 * @throws Exceptions\ParseMessage
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function parseHttpShellyMessage(
		Uuid\UuidInterface $connector,
		string $identifier,
		string $address,
		string $message,
	): Entities\Messages\DeviceInfo
	{
		if (!$this->validator->isValidHttpShellyMessage($message)) {
			throw new Exceptions\ParseMessage('Provided description message is not valid');
		}

		$filePath = Shelly\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . Gen1Validator::HTTP_SHELLY_INFO_MESSAGE_SCHEMA_FILENAME;

		try {
			$schema = Utils\FileSystem::read($filePath);

		} catch (Nette\IOException) {
			throw new Exceptions\ParseMessage('Validation schema for message could not be loaded');
		}

		$parsedMessage = $this->schemaValidator->validate($message, $schema);

		if (
			!$parsedMessage->offsetExists('type')
			|| !$parsedMessage->offsetExists('mac')
			|| !$parsedMessage->offsetExists('auth')
			|| !$parsedMessage->offsetExists('fw')
		) {
			throw new Exceptions\ParseMessage('Provided message is not valid');
		}

		return new Entities\Messages\DeviceInfo(
			Types\MessageSource::get(Types\MessageSource::SOURCE_GEN_1_HTTP),
			$connector,
			$identifier,
			$address,
			Utils\Strings::lower(strval($parsedMessage->offsetGet('type'))),
			strval($parsedMessage->offsetGet('mac')),
			boolval($parsedMessage->offsetGet('auth')),
			strval($parsedMessage->offsetGet('fw')),
		);
	}

	/**
	 * @throws Exceptions\ParseMessage
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function parseHttpDescriptionMessage(
		Uuid\UuidInterface $connector,
		string $identifier,
		string $address,
		string $message,
	): Entities\Messages\DeviceDescription
	{
		if (!$this->validator->isValidHttpDescriptionMessage($message)) {
			throw new Exceptions\ParseMessage('Provided description message is not valid');
		}

		$filePath = Shelly\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . Gen1Validator::HTTP_DESCRIPTION_MESSAGE_SCHEMA_FILENAME;

		try {
			$schema = Utils\FileSystem::read($filePath);

		} catch (Nette\IOException) {
			throw new Exceptions\ParseMessage('Validation schema for message could not be loaded');
		}

		$parsedMessage = $this->schemaValidator->validate($message, $schema);

		$blocks = $this->extractBlocksDescription(
			Types\MessageSource::get(Types\MessageSource::SOURCE_GEN_1_HTTP),
			$parsedMessage,
		);

		return new Entities\Messages\DeviceDescription(
			Types\MessageSource::get(Types\MessageSource::SOURCE_GEN_1_HTTP),
			$connector,
			$identifier,
			null,
			$address,
			$blocks,
		);
	}

	/**
	 * @return Array<Entities\Messages\BlockDescription>
	 */
	private function extractBlocksDescription(
		Types\MessageSource $source,
		Utils\ArrayHash $description,
	): array
	{
		if (!$description->offsetExists('blk') || !$description->offsetExists('sen')) {
			return [];
		}

		$blocks = $description->offsetGet('blk');
		$sensors = $description->offsetGet('sen');

		if (!$blocks instanceof Utils\ArrayHash || !$sensors instanceof Utils\ArrayHash) {
			return [];
		}

		$blocksDescriptions = [];

		foreach ($blocks as $block) {
			if (
				!$block instanceof Utils\ArrayHash
				|| !$block->offsetExists('I')
				|| !$block->offsetExists('D')
			) {
				continue;
			}

			$blockDescription = new Entities\Messages\BlockDescription(
				$source,
				intval($block->offsetGet('I')),
				strval($block->offsetGet('D')),
			);

			foreach ($sensors as $sensor) {
				if (
					!$sensor instanceof Utils\ArrayHash
					|| !$sensor->offsetExists('I')
					|| !$sensor->offsetExists('T')
					|| !$sensor->offsetExists('D')
					|| !$sensor->offsetExists('L')
				) {
					continue;
				}

				if (
					(
						$sensor->offsetGet('L') instanceof Utils\ArrayHash
						&& in_array($block->offsetGet('I'), (array) $sensor->offsetGet('L'), true)
					)
					|| intval($block->offsetGet('I')) === intval($sensor->offsetGet('L'))
				) {
					$sensorRange = $this->parseSensorRange(
						$source,
						strval($block->offsetGet('D')),
						strval($sensor->offsetGet('D')),
						$sensor->offsetExists('R') ? (is_array($sensor->offsetGet('R')) || $sensor->offsetGet(
							'R',
						) instanceof Utils\ArrayHash ? (array) $sensor->offsetGet(
							'R',
						) : strval(
							$sensor->offsetGet('R'),
						)) : null,
					);

					$sensorDescription = new Entities\Messages\SensorDescription(
						$source,
						intval($sensor->offsetGet('I')),
						Types\SensorType::get($sensor->offsetGet('T')),
						strval($sensor->offsetGet('D')),
						$sensorRange->getDataType(),
						$sensor->offsetExists('U') && $sensor->offsetGet('U') !== null ? Types\SensorUnit::get(
							$sensor->offsetGet('U'),
						) : null,
						$sensorRange->getFormat(),
						$sensorRange->getInvalid(),
						false,
						Types\WritableSensorType::isValidValue($sensor->offsetGet('D')),
					);

					$blockDescription->addSensor($sensorDescription);
				}
			}

			$blocksDescriptions[] = $blockDescription;
		}

		return $blocksDescriptions;
	}

	/**
	 * @param string|Array<string>|null $rawRange
	 */
	private function parseSensorRange(
		Types\MessageSource $source,
		string $block,
		string $description,
		string|array|null $rawRange,
	): Entities\Messages\SensorRange
	{
		$invalidValue = null;

		if (is_array($rawRange) && count($rawRange) === 2) {
			$normalValue = $rawRange[0];
			$invalidValue = $rawRange[1] === (string) (int) $rawRange[1]
				? intval($rawRange[1])
				: ($rawRange[1] === (string) (float) $rawRange[1] ? floatval(
					$rawRange[1],
				) : $rawRange[1]);

		} elseif (is_string($rawRange)) {
			$normalValue = $rawRange;

		} else {
			return new Entities\Messages\SensorRange(
				$source,
				$this->adjustSensorDataType(
					$block,
					$description,
					MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UNKNOWN),
				),
				$this->adjustSensorFormat($block, $description, null),
				null,
			);
		}

		if ($normalValue === '0/1') {
			return new Entities\Messages\SensorRange(
				$source,
				$this->adjustSensorDataType(
					$block,
					$description,
					MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
				),
				$this->adjustSensorFormat($block, $description, null),
				$invalidValue,
			);
		}

		if ($normalValue === 'U8') {
			return new Entities\Messages\SensorRange(
				$source,
				$this->adjustSensorDataType(
					$block,
					$description,
					MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
				),
				$this->adjustSensorFormat($block, $description, null),
				$invalidValue,
			);
		}

		if ($normalValue === 'U16') {
			return new Entities\Messages\SensorRange(
				$source,
				$this->adjustSensorDataType(
					$block,
					$description,
					MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_USHORT),
				),
				$this->adjustSensorFormat($block, $description, null),
				$invalidValue,
			);
		}

		if ($normalValue === 'U32') {
			return new Entities\Messages\SensorRange(
				$source,
				$this->adjustSensorDataType(
					$block,
					$description,
					MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
				),
				$this->adjustSensorFormat($block, $description, null),
				$invalidValue,
			);
		}

		if ($normalValue === 'I8') {
			return new Entities\Messages\SensorRange(
				$source,
				$this->adjustSensorDataType(
					$block,
					$description,
					MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
				),
				$this->adjustSensorFormat($block, $description, null),
				$invalidValue,
			);
		}

		if ($normalValue === 'I16') {
			return new Entities\Messages\SensorRange(
				$source,
				$this->adjustSensorDataType(
					$block,
					$description,
					MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_USHORT),
				),
				$this->adjustSensorFormat($block, $description, null),
				$invalidValue,
			);
		}

		if ($normalValue === 'I32') {
			return new Entities\Messages\SensorRange(
				$source,
				$this->adjustSensorDataType(
					$block,
					$description,
					MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
				),
				$this->adjustSensorFormat($block, $description, null),
				$invalidValue,
			);
		}

		if (Utils\Strings::contains($normalValue, '/')) {
			$normalValueParts = explode('/', $normalValue);

			if (
				count($normalValueParts) === 2
				&& $normalValueParts[0] === (string) (int) $normalValueParts[0]
				&& $normalValueParts[1] === (string) (int) $normalValueParts[1]
			) {
				return new Entities\Messages\SensorRange(
					$source,
					$this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_INT),
					),
					$this->adjustSensorFormat(
						$block,
						$description,
						[intval($normalValueParts[0]), intval($normalValueParts[1])],
					),
					$invalidValue,
				);
			}

			if (
				count($normalValueParts) === 2
				&& $normalValueParts[0] === (string) (float) $normalValueParts[0]
				&& $normalValueParts[1] === (string) (float) $normalValueParts[1]
			) {
				return new Entities\Messages\SensorRange(
					$source,
					$this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
					),
					$this->adjustSensorFormat(
						$block,
						$description,
						[floatval($normalValueParts[0]), floatval($normalValueParts[1])],
					),
					$invalidValue,
				);
			}

			return new Entities\Messages\SensorRange(
				$source,
				$this->adjustSensorDataType(
					$block,
					$description,
					MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				),
				$this->adjustSensorFormat(
					$block,
					$description,
					array_map(static fn (string $item): string => Utils\Strings::trim($item), $normalValueParts),
				),
				$invalidValue,
			);
		}

		return new Entities\Messages\SensorRange(
			$source,
			$this->adjustSensorDataType(
				$block,
				$description,
				MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UNKNOWN),
			),
			$this->adjustSensorFormat($block, $description, null),
			null,
		);
	}

	private function adjustSensorDataType(
		string $block,
		string $description,
		MetadataTypes\DataType $dataType,
	): MetadataTypes\DataType
	{
		if (Utils\Strings::startsWith($block, 'relay') && Utils\Strings::lower($description) === 'output') {
			return MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SWITCH);
		}

		if (Utils\Strings::startsWith($block, 'light') && Utils\Strings::lower($description) === 'output') {
			return MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SWITCH);
		}

		return $dataType;
	}

	/**
	 * @param Array<string>|Array<int>|Array<float>|null $format
	 *
	 * @return Array<string>|Array<int>|Array<float>|Array<int, Array<int, (string|null)>>|Array<int, (int|null)>|Array<int, (float|null)>|Array<int, (MetadataTypes\SwitchPayload|string|Types\RelayPayload|null)>|null
	 */
	private function adjustSensorFormat(
		string $block,
		string $description,
		array|null $format,
	): array|null
	{
		if (Utils\Strings::startsWith($block, 'relay') && Utils\Strings::lower($description) === 'output') {
			return [
				[MetadataTypes\SwitchPayload::PAYLOAD_ON, '1', Types\RelayPayload::PAYLOAD_ON],
				[MetadataTypes\SwitchPayload::PAYLOAD_OFF, '0', Types\RelayPayload::PAYLOAD_OFF],
				[MetadataTypes\SwitchPayload::PAYLOAD_TOGGLE, null, Types\RelayPayload::PAYLOAD_TOGGLE],
			];
		}

		if (Utils\Strings::startsWith($block, 'light') && Utils\Strings::lower($description) === 'output') {
			return [
				[MetadataTypes\SwitchPayload::PAYLOAD_ON, '1', Types\LightSwitchPayload::PAYLOAD_ON],
				[MetadataTypes\SwitchPayload::PAYLOAD_OFF, '0', Types\LightSwitchPayload::PAYLOAD_OFF],
				[MetadataTypes\SwitchPayload::PAYLOAD_TOGGLE, null, Types\LightSwitchPayload::PAYLOAD_TOGGLE],
			];
		}

		return $format;
	}

}
