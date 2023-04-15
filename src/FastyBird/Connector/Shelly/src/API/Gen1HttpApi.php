<?php declare(strict_types = 1);

/**
 * Gen1HttpApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           20.12.22
 */

namespace FastyBird\Connector\Shelly\API;

use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function array_map;
use function assert;
use function count;
use function explode;
use function floatval;
use function in_array;
use function intval;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function strval;
use const DIRECTORY_SEPARATOR;

/**
 * Generation 1 device http API interface
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gen1HttpApi extends HttpApi
{

	use Nette\SmartObject;

	private const DEVICE_INFORMATION = 'http://%s/shelly';

	private const DEVICE_DESCRIPTION = 'http://%s/cit/d';

	private const DEVICE_STATUS = 'http://%s/status';

	private const DEVICE_ACTION = 'http://%s/%s/%s?%s=%s';

	private const DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME = 'gen1_http_shelly.json';

	private const DEVICE_DESCRIPTION_MESSAGE_SCHEMA_FILENAME = 'gen1_http_description.json';

	private const DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME = 'gen1_http_status.json';

	private const CHANNEL_BLOCK = '/^(?P<identifier>[0-9]+)_(?P<description>[a-zA-Z]+)(_(?P<channel>[0-9]+))?$/';

	private const PROPERTY_SENSOR = '/^(?P<identifier>[0-9]+)_(?P<type>[a-zA-Z]{1,3})_(?P<description>[a-zA-Z0-9]+)$/';

	private const SENSORS_UNIT = [
		'W' => 'W',
		'Wmin' => 'Wmin',
		'Wh' => 'Wh',
		'V' => 'V',
		'A' => 'A',
		'C' => '°C',
		'F' => '°F',
		'K' => 'K',
		'deg' => 'deg',
		'lux' => 'lx',
		'ppm' => 'ppm',
		's' => 's',
		'pct' => '%',
	];

	private const WRITABLE_SENSORS = [
		Types\SensorDescription::DESC_MODE,
		Types\SensorDescription::DESC_OUTPUT,
		Types\SensorDescription::DESC_ROLLER,
		Types\SensorDescription::DESC_RED,
		Types\SensorDescription::DESC_GREEN,
		Types\SensorDescription::DESC_BLUE,
		Types\SensorDescription::DESC_WHITE,
		Types\SensorDescription::DESC_WHITE_LEVEL,
		Types\SensorDescription::DESC_GAIN,
		Types\SensorDescription::DESC_BRIGHTNESS,
		Types\SensorDescription::DESC_COLOR_TEMP,
	];

	public function __construct(
		private readonly MetadataSchemas\Validator $schemaValidator,
		EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		parent::__construct($eventLoop, $logger);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	public function getDeviceInformation(
		string $address,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Gen1\DeviceInformation
	{
		if ($async) {
			$deferred = new Promise\Deferred();

			$this->callAsyncRequest(
				'GET',
				sprintf(self::DEVICE_INFORMATION, $address),
			)
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$deferred->resolve($this->parseDeviceInformationResponse($response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseDeviceInformationResponse(
			$this->callRequest(
				'GET',
				sprintf(self::DEVICE_INFORMATION, $address),
			),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	public function getDeviceDescription(
		string $address,
		string|null $username,
		string|null $password,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Gen1\DeviceDescription
	{
		if ($async) {
			$deferred = new Promise\Deferred();

			$this->callAsyncRequest(
				'GET',
				sprintf(self::DEVICE_DESCRIPTION, $address),
				[],
				[],
				null,
				self::AUTHORIZATION_BASIC,
				$username,
				$password,
			)
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$deferred->resolve($this->parseDeviceDescriptionResponse($response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseDeviceDescriptionResponse(
			$this->callRequest(
				'GET',
				sprintf(self::DEVICE_DESCRIPTION, $address),
				[],
				[],
				null,
				self::AUTHORIZATION_BASIC,
				$username,
				$password,
			),
		);
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	public function getDeviceStatus(
		string $address,
		string|null $username,
		string|null $password,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Gen1\DeviceStatus
	{
		if ($async) {
			$deferred = new Promise\Deferred();

			$this->callAsyncRequest(
				'GET',
				sprintf(self::DEVICE_STATUS, $address),
				[],
				[],
				null,
				self::AUTHORIZATION_BASIC,
				$username,
				$password,
			)
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$deferred->resolve($this->parseDeviceStatusResponse($response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseDeviceStatusResponse(
			$this->callRequest(
				'GET',
				sprintf(self::DEVICE_STATUS, $address),
				[],
				[],
				null,
				self::AUTHORIZATION_BASIC,
				$username,
				$password,
			),
		);
	}

	/**
	 * @throws Exceptions\HttpApiCall
	 */
	public function setDeviceStatus(
		string $address,
		string|null $username,
		string|null $password,
		string $blockIdentifier,
		string $sensorIdentifier,
		int|float|string|bool $value,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		if (
			preg_match(self::CHANNEL_BLOCK, $blockIdentifier, $channelMatches) !== 1
			|| !array_key_exists('identifier', $channelMatches)
			|| !array_key_exists('description', $channelMatches)
			|| !array_key_exists('channel', $channelMatches)
		) {
			return Promise\reject(new Exceptions\InvalidState('Channel identifier is not in expected format'));
		}

		try {
			$sensorAction = $this->buildSensorAction($sensorIdentifier);

		} catch (Exceptions\InvalidState) {
			return Promise\reject(new Exceptions\InvalidState('Sensor action could not be created'));
		}

		if ($async) {
			$deferred = new Promise\Deferred();

			$this->callAsyncRequest(
				'GET',
				sprintf(
					self::DEVICE_ACTION,
					$address,
					$channelMatches['description'],
					intval($channelMatches['channel']),
					$sensorAction,
					$value,
				),
				[],
				[],
				null,
				self::AUTHORIZATION_BASIC,
				$username,
				$password,
			)
				->then(static function () use ($deferred): void {
					$deferred->resolve();
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		$response = $this->callRequest(
			'GET',
			sprintf(
				self::DEVICE_ACTION,
				$address,
				$channelMatches['description'],
				intval($channelMatches['channel']),
				$sensorAction,
				$value,
			),
			[],
			[],
			null,
			self::AUTHORIZATION_BASIC,
			$username,
			$password,
		);

		return $response->getStatusCode() === StatusCodeInterface::STATUS_OK;
	}

	/**
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	private function parseDeviceInformationResponse(
		Message\ResponseInterface $response,
	): Entities\API\Gen1\DeviceInformation
	{
		$parsedMessage = $this->schemaValidator->validate(
			$response->getBody()->getContents(),
			$this->getSchemaFilePath(self::DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME),
		);

		return EntityFactory::build(
			Entities\API\Gen1\DeviceInformation::class,
			$parsedMessage,
		);
	}

	/**
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	private function parseDeviceDescriptionResponse(
		Message\ResponseInterface $response,
	): Entities\API\Gen1\DeviceDescription
	{
		$parsedMessage = $this->schemaValidator->validate(
			$response->getBody()->getContents(),
			$this->getSchemaFilePath(self::DEVICE_DESCRIPTION_MESSAGE_SCHEMA_FILENAME),
		);

		if (!$parsedMessage->offsetExists('blk') || !$parsedMessage->offsetExists('sen')) {
			throw new Exceptions\InvalidState('Received response is not valid');
		}

		$blocks = $parsedMessage->offsetGet('blk');
		$sensors = $parsedMessage->offsetGet('sen');

		$descriptionBlocks = [];

		if ($blocks instanceof Utils\ArrayHash && $sensors instanceof Utils\ArrayHash) {
			foreach ($blocks as $block) {
				if (
					!$block instanceof Utils\ArrayHash
					|| !$block->offsetExists('I')
					|| !$block->offsetExists('D')
				) {
					$this->logger->debug(
						'Received device block description is not in valid format',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'gen1-http-api',
							'description' => (array) $block,
						],
					);

					continue;
				}

				$blockDescription = new Entities\API\Gen1\DeviceBlockDescription(
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
						$this->logger->debug(
							'Received block sensor description is not in valid format',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
								'type' => 'gen1-http-api',
								'description' => (array) $sensor,
							],
						);

						continue;
					}

					if (
						(
							$sensor->offsetGet('L') instanceof Utils\ArrayHash
							&& in_array(
								$block->offsetGet('I'),
								array_map(
									static fn ($item): int => intval($item),
									(array) $sensor->offsetGet('L'),
								),
								true,
							)
						)
						|| intval($block->offsetGet('I')) === intval($sensor->offsetGet('L'))
					) {
						$sensorRange = $this->parseSensorRange(
							strval($block->offsetGet('D')),
							strval($sensor->offsetGet('D')),
							$sensor->offsetExists('R') ? (is_array(
								$sensor->offsetGet('R'),
							) || $sensor->offsetGet(
								'R',
							) instanceof Utils\ArrayHash ? (array) $sensor->offsetGet(
								'R',
							) : strval(
								$sensor->offsetGet('R'),
							)) : null,
						);

						$sensorDescription = new Entities\API\Gen1\BlockSensorDescription(
							intval($sensor->offsetGet('I')),
							Types\SensorType::get($sensor->offsetGet('T')),
							strval($sensor->offsetGet('D')),
							$sensorRange->getDataType(),
							array_key_exists(
								strval($sensor->offsetExists('U')),
								self::SENSORS_UNIT,
							) ? self::SENSORS_UNIT[strval($sensor->offsetExists(
								'U',
							))] : null,
							$sensorRange->getFormat(),
							$sensorRange->getInvalid(),
							true,
							in_array($sensor->offsetGet('D'), self::WRITABLE_SENSORS, true),
						);

						$blockDescription->addSensor($sensorDescription);
					}
				}

				$descriptionBlocks[] = $blockDescription;
			}
		}

		return new Entities\API\Gen1\DeviceDescription($descriptionBlocks);
	}

	/**
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	private function parseDeviceStatusResponse(
		Message\ResponseInterface $response,
	): Entities\API\Gen1\DeviceStatus
	{
		$parsedMessage = $this->schemaValidator->validate(
			$response->getBody()->getContents(),
			$this->getSchemaFilePath(self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME),
		);

		$relays = [];

		if (
			$parsedMessage->offsetExists('relays')
			&& (
				is_array($parsedMessage->offsetGet('relays'))
				|| $parsedMessage->offsetGet('relays') instanceof Utils\ArrayHash
			)
		) {
			foreach ($parsedMessage->offsetGet('relays') as $relayStatus) {
				assert($relayStatus instanceof Utils\ArrayHash);

				$relays[] = EntityFactory::build(
					Entities\API\Gen1\DeviceRelayStatus::class,
					$relayStatus,
				);
			}
		}

		$rollers = [];

		if (
			$parsedMessage->offsetExists('rollers')
			&& (
				is_array($parsedMessage->offsetGet('rollers'))
				|| $parsedMessage->offsetGet('rollers') instanceof Utils\ArrayHash
			)
		) {
			foreach ($parsedMessage->offsetGet('rollers') as $rollerStatus) {
				assert($rollerStatus instanceof Utils\ArrayHash);

				$rollers[] = EntityFactory::build(
					Entities\API\Gen1\DeviceRollerStatus::class,
					$rollerStatus,
				);
			}
		}

		$inputs = [];

		if (
			$parsedMessage->offsetExists('inputs')
			&& (
				is_array($parsedMessage->offsetGet('inputs'))
				|| $parsedMessage->offsetGet('inputs') instanceof Utils\ArrayHash
			)
		) {
			foreach ($parsedMessage->offsetGet('inputs') as $inputStatus) {
				assert($inputStatus instanceof Utils\ArrayHash);

				$inputs[] = EntityFactory::build(
					Entities\API\Gen1\DeviceInputStatus::class,
					$inputStatus,
				);
			}
		}

		$lights = [];

		if (
			$parsedMessage->offsetExists('lights')
			&& (
				is_array($parsedMessage->offsetGet('lights'))
				|| $parsedMessage->offsetGet('lights') instanceof Utils\ArrayHash
			)
		) {
			foreach ($parsedMessage->offsetGet('lights') as $lightStatus) {
				assert($lightStatus instanceof Utils\ArrayHash);

				$lights[] = EntityFactory::build(
					Entities\API\Gen1\DeviceLightStatus::class,
					$lightStatus,
				);
			}
		}

		$meters = [];

		if (
			$parsedMessage->offsetExists('meters')
			&& (
				is_array($parsedMessage->offsetGet('meters'))
				|| $parsedMessage->offsetGet('meters') instanceof Utils\ArrayHash
			)
		) {
			foreach ($parsedMessage->offsetGet('meters') as $meterStatus) {
				assert($meterStatus instanceof Utils\ArrayHash);

				$meters[] = EntityFactory::build(
					Entities\API\Gen1\DeviceMeterStatus::class,
					$meterStatus,
				);
			}
		}

		$emeters = [];

		if (
			$parsedMessage->offsetExists('emeters')
			&& (
				is_array($parsedMessage->offsetGet('emeters'))
				|| $parsedMessage->offsetGet('emeters') instanceof Utils\ArrayHash
			)
		) {
			foreach ($parsedMessage->offsetGet('emeters') as $emeterStatus) {
				assert($emeterStatus instanceof Utils\ArrayHash);

				$emeters[] = EntityFactory::build(
					Entities\API\Gen1\DeviceEmeterStatus::class,
					$emeterStatus,
				);
			}
		}

		$wifi = null;

		if (
			$parsedMessage->offsetExists('wifi_sta')
			&& $parsedMessage->offsetGet('wifi_sta') instanceof Utils\ArrayHash
		) {
			$wifi = EntityFactory::build(
				Entities\API\Gen1\WifiStaStatus::class,
				$parsedMessage->offsetGet('wifi_sta'),
			);
		}

		return new Entities\API\Gen1\DeviceStatus(
			$relays,
			$rollers,
			$inputs,
			$lights,
			$meters,
			$emeters,
			$wifi,
		);
	}

	/**
	 * @param string|array<string>|null $rawRange
	 */
	private function parseSensorRange(
		string $block,
		string $description,
		string|array|null $rawRange,
	): Entities\API\Gen1\SensorRange
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
			return new Entities\API\Gen1\SensorRange(
				$this->adjustSensorDataType(
					$block,
					$description,
					MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UNKNOWN),
				),
				$this->adjustSensorFormat($block, $description, null),
				null,
			);
		}

		if ($normalValue === '0/1' || $normalValue === '1/0') {
			return new Entities\API\Gen1\SensorRange(
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
			return new Entities\API\Gen1\SensorRange(
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
			return new Entities\API\Gen1\SensorRange(
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
			return new Entities\API\Gen1\SensorRange(
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
			return new Entities\API\Gen1\SensorRange(
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
			return new Entities\API\Gen1\SensorRange(
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
			return new Entities\API\Gen1\SensorRange(
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
				return new Entities\API\Gen1\SensorRange(
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
				return new Entities\API\Gen1\SensorRange(
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

			return new Entities\API\Gen1\SensorRange(
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

		return new Entities\API\Gen1\SensorRange(
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
		if (
			Utils\Strings::startsWith($block, Types\BlockDescription::DESC_RELAY)
			&& Utils\Strings::lower($description) === Types\SensorDescription::DESC_OUTPUT
		) {
			return MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SWITCH);
		}

		if (
			Utils\Strings::startsWith($block, Types\BlockDescription::DESC_LIGHT)
			&& Utils\Strings::lower($description) === Types\SensorDescription::DESC_OUTPUT
		) {
			return MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SWITCH);
		}

		return $dataType;
	}

	/**
	 * @param array<string>|array<int>|array<float>|null $format
	 *
	 * @return array<string>|array<int>|array<float>|array<int, array<int, (string|array<int, string>|null)>>|array<int, (int|null)>|array<int, (float|null)>|array<int, (MetadataTypes\SwitchPayload|string|Types\RelayPayload|null)>|null
	 */
	private function adjustSensorFormat(
		string $block,
		string $description,
		array|null $format,
	): array|null
	{
		if (
			Utils\Strings::startsWith($block, Types\BlockDescription::DESC_RELAY)
			&& Utils\Strings::lower($description) === Types\SensorDescription::DESC_OUTPUT
		) {
			return [
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH, MetadataTypes\SwitchPayload::PAYLOAD_ON],
					[MetadataTypes\DataTypeShort::DATA_TYPE_UCHAR, '1'],
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RelayPayload::PAYLOAD_ON],
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH, MetadataTypes\SwitchPayload::PAYLOAD_OFF],
					[MetadataTypes\DataTypeShort::DATA_TYPE_UCHAR, '0'],
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RelayPayload::PAYLOAD_OFF],
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH, MetadataTypes\SwitchPayload::PAYLOAD_TOGGLE],
					null,
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RelayPayload::PAYLOAD_TOGGLE],
				],
			];
		}

		if (
			Utils\Strings::startsWith($block, Types\BlockDescription::DESC_ROLLER)
			&& Utils\Strings::lower($description) === Types\SensorDescription::DESC_ROLLER
		) {
			return [
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_COVER, MetadataTypes\CoverPayload::PAYLOAD_OPEN],
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RollerPayload::PAYLOAD_OPEN],
					null,
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_COVER, MetadataTypes\CoverPayload::PAYLOAD_OPENED],
					null,
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RollerPayload::PAYLOAD_OPEN],
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_COVER, MetadataTypes\CoverPayload::PAYLOAD_CLOSE],
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RollerPayload::PAYLOAD_CLOSE],
					null,
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_COVER, MetadataTypes\CoverPayload::PAYLOAD_CLOSED],
					null,
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RollerPayload::PAYLOAD_CLOSE],
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_COVER, MetadataTypes\CoverPayload::PAYLOAD_STOP],
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RollerPayload::PAYLOAD_STOP],
					null,
				],
			];
		}

		if (
			Utils\Strings::startsWith($block, Types\BlockDescription::DESC_LIGHT)
			&& Utils\Strings::lower($description) === Types\SensorDescription::DESC_OUTPUT
		) {
			return [
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH, MetadataTypes\SwitchPayload::PAYLOAD_ON],
					[MetadataTypes\DataTypeShort::DATA_TYPE_UCHAR, '1'],
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\LightSwitchPayload::PAYLOAD_ON],
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH, MetadataTypes\SwitchPayload::PAYLOAD_OFF],
					[MetadataTypes\DataTypeShort::DATA_TYPE_UCHAR, '0'],
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\LightSwitchPayload::PAYLOAD_OFF],
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH, MetadataTypes\SwitchPayload::PAYLOAD_TOGGLE],
					null,
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\LightSwitchPayload::PAYLOAD_TOGGLE],
				],
			];
		}

		return $format;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function buildSensorAction(string $property): string
	{
		if (preg_match(self::PROPERTY_SENSOR, $property, $propertyMatches) !== 1) {
			throw new Exceptions\InvalidState('Property identifier is not valid');
		}

		if (
			!array_key_exists('identifier', $propertyMatches)
			|| !array_key_exists('type', $propertyMatches)
			|| !array_key_exists('description', $propertyMatches)
		) {
			throw new Exceptions\InvalidState('Property identifier is not valid');
		}

		if ($propertyMatches['description'] === Types\SensorDescription::DESC_OUTPUT) {
			return 'turn';
		}

		if ($propertyMatches['description'] === Types\SensorDescription::DESC_ROLLER) {
			return 'go';
		}

		if ($propertyMatches['description'] === Types\SensorDescription::DESC_COLOR_TEMP) {
			return 'temp';
		}

		if ($propertyMatches['description'] === Types\SensorDescription::DESC_WHITE_LEVEL) {
			return 'white';
		}

		return $propertyMatches['description'];
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function getSchemaFilePath(string $schemaFilename): string
	{
		try {
			$schema = Utils\FileSystem::read(
				Shelly\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
			);

		} catch (Nette\IOException) {
			throw new Exceptions\InvalidState('Validation schema for response could not be loaded');
		}

		return $schema;
	}

}
