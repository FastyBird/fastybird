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

use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
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

	private const GET_DEVICE_INFORMATION = 'http://%s/shelly';

	private const GET_DEVICE_DESCRIPTION = 'http://%s/cit/d';

	private const GET_DEVICE_STATE = 'http://%s/status';

	private const SET_DEVICE_STATE = 'http://%s/%s/%s?%s=%s';

	private const GET_DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME = 'gen1_http_shelly.json';

	private const GET_DEVICE_DESCRIPTION_MESSAGE_SCHEMA_FILENAME = 'gen1_http_description.json';

	private const GET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'gen1_http_state.json';

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
		Types\SensorDescription::MODE,
		Types\SensorDescription::OUTPUT,
		Types\SensorDescription::ROLLER,
		Types\SensorDescription::RED,
		Types\SensorDescription::GREEN,
		Types\SensorDescription::BLUE,
		Types\SensorDescription::WHITE,
		Types\SensorDescription::WHITE_LEVEL,
		Types\SensorDescription::GAIN,
		Types\SensorDescription::BRIGHTNESS,
		Types\SensorDescription::COLOR_TEMP,
	];

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Gen1\GetDeviceInformation)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws RuntimeException
	 */
	public function getDeviceInformation(
		string $address,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Gen1\GetDeviceInformation
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_DEVICE_INFORMATION, $address),
		);

		$result = $this->callRequest($request, null, null, null, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDeviceInformation($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceInformation($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Gen1\GetDeviceDescription)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws RuntimeException
	 */
	public function getDeviceDescription(
		string $address,
		string|null $username,
		string|null $password,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Gen1\GetDeviceDescription
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_DEVICE_DESCRIPTION, $address),
		);

		$result = $this->callRequest($request, self::AUTHORIZATION_BASIC, $username, $password, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDeviceDescription($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceDescription($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Gen1\GetDeviceState)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws RuntimeException
	 */
	public function getDeviceState(
		string $address,
		string|null $username,
		string|null $password,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Gen1\GetDeviceState
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_DEVICE_STATE, $address),
		);

		$result = $this->callRequest($request, self::AUTHORIZATION_BASIC, $username, $password, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDeviceStatus($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceStatus($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 *
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 */
	public function setDeviceState(
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
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Channel identifier is not in expected format'));
			}

			throw new Exceptions\InvalidState('Channel identifier is not in expected format');
		}

		try {
			$sensorAction = $this->buildSensorAction($sensorIdentifier);

		} catch (Exceptions\InvalidState) {
			if ($async) {
				return Promise\reject(new Exceptions\InvalidState('Sensor action could not be created'));
			}

			throw new Exceptions\InvalidState('Sensor action could not be created');
		}

		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(
				self::SET_DEVICE_STATE,
				$address,
				$channelMatches['description'],
				intval($channelMatches['channel']),
				$sensorAction,
				$value,
			),
		);

		$result = $this->callRequest($request, self::AUTHORIZATION_BASIC, $username, $password, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(static function (Message\ResponseInterface $response) use ($deferred): void {
					$deferred->resolve($response->getStatusCode() === StatusCodeInterface::STATUS_OK);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $result->getStatusCode() === StatusCodeInterface::STATUS_OK;
	}

	/**
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 */
	private function parseGetDeviceInformation(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\Gen1\GetDeviceInformation
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME,
		);

		return $this->createEntity(Entities\API\Gen1\GetDeviceInformation::class, $body);
	}

	/**
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 */
	private function parseGetDeviceDescription(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\Gen1\GetDeviceDescription
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_DEVICE_DESCRIPTION_MESSAGE_SCHEMA_FILENAME,
		);

		$data = [
			'blocks' => [],
		];

		if (!$body->offsetExists('blk') || !$body->offsetExists('sen')) {
			throw new Exceptions\HttpApiCall(
				'Received response is not valid',
				$request,
				$response,
			);
		}

		$blocks = $body->offsetGet('blk');
		$sensors = $body->offsetGet('sen');

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

				$blockDescription = [
					'identifier' => intval($block->offsetGet('I')),
					'description' => strval($block->offsetGet('D')),
					'sensors' => [],
				];

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
						try {
							$sensorRange = $this->parseSensorRange(
								strval($block->offsetGet('D')),
								strval($sensor->offsetGet('D')),
								$sensor->offsetExists('R')
									? (
										is_array($sensor->offsetGet('R'))
										|| $sensor->offsetGet('R') instanceof Utils\ArrayHash
											? (array) $sensor->offsetGet('R')
											: strval($sensor->offsetGet('R'))
									)
									: null,
							);
						} catch (Exceptions\Runtime $ex) {
							throw new Exceptions\HttpApiCall(
								'Sensor range could not be decoded from response',
								$request,
								$response,
								$ex->getCode(),
								$ex,
							);
						}

						$blockDescription['sensors'][] = [
							'identifier' => intval($sensor->offsetGet('I')),
							'type' => $sensor->offsetGet('T'),
							'description' => strval($sensor->offsetGet('D')),
							'data_type' => $sensorRange->getDataType()->getValue(),
							'unit' => array_key_exists(strval($sensor->offsetExists('U')), self::SENSORS_UNIT)
								? self::SENSORS_UNIT[strval($sensor->offsetExists('U'))]
								: null,
							'format' => $sensorRange->getFormat(),
							'invalid' => $sensorRange->getInvalid(),
							'queryable' => true,
							'settable' => in_array($sensor->offsetGet('D'), self::WRITABLE_SENSORS, true),
						];
					}
				}

				$data['blocks'][] = $blockDescription;
			}
		}

		return $this->createEntity(Entities\API\Gen1\GetDeviceDescription::class, Utils\ArrayHash::from($data));
	}

	/**
	 * @throws Exceptions\HttpApiCall
	 * @throws Exceptions\HttpApiError
	 */
	private function parseGetDeviceStatus(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\Gen1\GetDeviceState
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME,
		);

		$relays = [];

		if (
			$body->offsetExists('relays')
			&& (
				is_array($body->offsetGet('relays'))
				|| $body->offsetGet('relays') instanceof Utils\ArrayHash
			)
		) {
			foreach ($body->offsetGet('relays') as $relayStatus) {
				assert($relayStatus instanceof Utils\ArrayHash);

				$relays[] = (array) $relayStatus;
			}
		}

		$rollers = [];

		if (
			$body->offsetExists('rollers')
			&& (
				is_array($body->offsetGet('rollers'))
				|| $body->offsetGet('rollers') instanceof Utils\ArrayHash
			)
		) {
			foreach ($body->offsetGet('rollers') as $rollerStatus) {
				assert($rollerStatus instanceof Utils\ArrayHash);

				$rollers[] = (array) $rollerStatus;
			}
		}

		$inputs = [];

		if (
			$body->offsetExists('inputs')
			&& (
				is_array($body->offsetGet('inputs'))
				|| $body->offsetGet('inputs') instanceof Utils\ArrayHash
			)
		) {
			foreach ($body->offsetGet('inputs') as $inputStatus) {
				assert($inputStatus instanceof Utils\ArrayHash);

				$inputs[] = (array) $inputStatus;
			}
		}

		$lights = [];

		if (
			$body->offsetExists('lights')
			&& (
				is_array($body->offsetGet('lights'))
				|| $body->offsetGet('lights') instanceof Utils\ArrayHash
			)
		) {
			foreach ($body->offsetGet('lights') as $lightStatus) {
				assert($lightStatus instanceof Utils\ArrayHash);

				$lights[] = (array) $lightStatus;
			}
		}

		$meters = [];

		if (
			$body->offsetExists('meters')
			&& (
				is_array($body->offsetGet('meters'))
				|| $body->offsetGet('meters') instanceof Utils\ArrayHash
			)
		) {
			foreach ($body->offsetGet('meters') as $meterStatus) {
				assert($meterStatus instanceof Utils\ArrayHash);

				$meters[] = (array) $meterStatus;
			}
		}

		$emeters = [];

		if (
			$body->offsetExists('emeters')
			&& (
				is_array($body->offsetGet('emeters'))
				|| $body->offsetGet('emeters') instanceof Utils\ArrayHash
			)
		) {
			foreach ($body->offsetGet('emeters') as $emeterStatus) {
				assert($emeterStatus instanceof Utils\ArrayHash);

				$emeters[] = (array) $emeterStatus;
			}
		}

		$wifi = null;

		if (
			$body->offsetExists('wifi_sta')
			&& $body->offsetGet('wifi_sta') instanceof Utils\ArrayHash
		) {
			$wifi = (array) $body->offsetGet('wifi_sta');
		}

		return $this->createEntity(Entities\API\Gen1\GetDeviceState::class, Utils\ArrayHash::from([
			'relays' => $relays,
			'rollers' => $rollers,
			'inputs' => $inputs,
			'lights' => $lights,
			'meters' => $meters,
			'emeters' => $emeters,
			'wifi' => $wifi,
		]));
	}

	/**
	 * @param string|array<string>|null $rawRange
	 *
	 * @throws Exceptions\Runtime
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
			return $this->entityHelper->create(
				Entities\API\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UNKNOWN),
					)->getValue(),
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => null,
				],
			);
		}

		if ($normalValue === '0/1' || $normalValue === '1/0') {
			return $this->entityHelper->create(
				Entities\API\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_BOOLEAN),
					)->getValue(),
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => $invalidValue,
				],
			);
		}

		if ($normalValue === 'U8') {
			return $this->entityHelper->create(
				Entities\API\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UCHAR),
					)->getValue(),
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => $invalidValue,
				],
			);
		}

		if ($normalValue === 'U16') {
			return $this->entityHelper->create(
				Entities\API\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_USHORT),
					)->getValue(),
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => $invalidValue,
				],
			);
		}

		if ($normalValue === 'U32') {
			return $this->entityHelper->create(
				Entities\API\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UINT),
					)->getValue(),
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => $invalidValue,
				],
			);
		}

		if ($normalValue === 'I8') {
			return $this->entityHelper->create(
				Entities\API\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_CHAR),
					)->getValue(),
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => $invalidValue,
				],
			);
		}

		if ($normalValue === 'I16') {
			return $this->entityHelper->create(
				Entities\API\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SHORT),
					)->getValue(),
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => $invalidValue,
				],
			);
		}

		if ($normalValue === 'I32') {
			return $this->entityHelper->create(
				Entities\API\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_INT),
					)->getValue(),
					'format' => $this->adjustSensorFormat($block, $description, null),
					'invalid' => $invalidValue,
				],
			);
		}

		if (Utils\Strings::contains($normalValue, '/')) {
			$normalValueParts = explode('/', $normalValue);

			if (
				count($normalValueParts) === 2
				&& $normalValueParts[0] === (string) (int) $normalValueParts[0]
				&& $normalValueParts[1] === (string) (int) $normalValueParts[1]
			) {
				return $this->entityHelper->create(
					Entities\API\Gen1\SensorRange::class,
					[
						'data_type' => $this->adjustSensorDataType(
							$block,
							$description,
							MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_INT),
						)->getValue(),
						'format' => $this->adjustSensorFormat(
							$block,
							$description,
							[intval($normalValueParts[0]), intval($normalValueParts[1])],
						),
						'invalid' => $invalidValue,
					],
				);
			}

			if (
				count($normalValueParts) === 2
				&& $normalValueParts[0] === (string) (float) $normalValueParts[0]
				&& $normalValueParts[1] === (string) (float) $normalValueParts[1]
			) {
				return $this->entityHelper->create(
					Entities\API\Gen1\SensorRange::class,
					[
						'data_type' => $this->adjustSensorDataType(
							$block,
							$description,
							MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_FLOAT),
						)->getValue(),
						'format' => $this->adjustSensorFormat(
							$block,
							$description,
							[floatval($normalValueParts[0]), floatval($normalValueParts[1])],
						),
						'invalid' => $invalidValue,
					],
				);
			}

			return $this->entityHelper->create(
				Entities\API\Gen1\SensorRange::class,
				[
					'data_type' => $this->adjustSensorDataType(
						$block,
						$description,
						MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
					)->getValue(),
					'format' => $this->adjustSensorFormat(
						$block,
						$description,
						array_map(static fn (string $item): string => Utils\Strings::trim($item), $normalValueParts),
					),
					'invalid' => $invalidValue,
				],
			);
		}

		return $this->entityHelper->create(
			Entities\API\Gen1\SensorRange::class,
			[
				'data_type' => $this->adjustSensorDataType(
					$block,
					$description,
					MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_UNKNOWN),
				)->getValue(),
				'format' => $this->adjustSensorFormat($block, $description, null),
				'invalid' => null,
			],
		);
	}

	private function adjustSensorDataType(
		string $block,
		string $description,
		MetadataTypes\DataType $dataType,
	): MetadataTypes\DataType
	{
		if (
			Utils\Strings::startsWith($block, Types\BlockDescription::RELAY)
			&& Utils\Strings::lower($description) === Types\SensorDescription::OUTPUT
		) {
			return MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SWITCH);
		}

		if (
			Utils\Strings::startsWith($block, Types\BlockDescription::LIGHT)
			&& Utils\Strings::lower($description) === Types\SensorDescription::OUTPUT
		) {
			return MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SWITCH);
		}

		return $dataType;
	}

	/**
	 * @param array<string>|array<int>|array<float>|null $format
	 *
	 * @return array<string>|array<int>|array<float>|array<int, array<int, (array<int, bool|string>|null)>>|null
	 */
	private function adjustSensorFormat(
		string $block,
		string $description,
		array|null $format,
	): array|null
	{
		if (
			Utils\Strings::startsWith($block, Types\BlockDescription::RELAY)
			&& Utils\Strings::lower($description) === Types\SensorDescription::OUTPUT
		) {
			return [
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH, MetadataTypes\SwitchPayload::PAYLOAD_ON],
					[MetadataTypes\DataTypeShort::DATA_TYPE_BOOLEAN, true],
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RelayPayload::ON],
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH, MetadataTypes\SwitchPayload::PAYLOAD_OFF],
					[MetadataTypes\DataTypeShort::DATA_TYPE_BOOLEAN, false],
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RelayPayload::OFF],
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH, MetadataTypes\SwitchPayload::PAYLOAD_TOGGLE],
					null,
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RelayPayload::TOGGLE],
				],
			];
		}

		if (
			Utils\Strings::startsWith($block, Types\BlockDescription::ROLLER)
			&& Utils\Strings::lower($description) === Types\SensorDescription::ROLLER
		) {
			return [
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_COVER, MetadataTypes\CoverPayload::PAYLOAD_OPEN],
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RollerPayload::OPEN],
					null,
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_COVER, MetadataTypes\CoverPayload::PAYLOAD_OPENED],
					null,
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RollerPayload::OPEN],
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_COVER, MetadataTypes\CoverPayload::PAYLOAD_CLOSE],
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RollerPayload::CLOSE],
					null,
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_COVER, MetadataTypes\CoverPayload::PAYLOAD_CLOSED],
					null,
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RollerPayload::CLOSE],
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_COVER, MetadataTypes\CoverPayload::PAYLOAD_STOP],
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\RollerPayload::STOP],
					null,
				],
			];
		}

		if (
			Utils\Strings::startsWith($block, Types\BlockDescription::LIGHT)
			&& Utils\Strings::lower($description) === Types\SensorDescription::OUTPUT
		) {
			return [
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH, MetadataTypes\SwitchPayload::PAYLOAD_ON],
					[MetadataTypes\DataTypeShort::DATA_TYPE_BOOLEAN, true],
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\LightSwitchPayload::ON],
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH, MetadataTypes\SwitchPayload::PAYLOAD_OFF],
					[MetadataTypes\DataTypeShort::DATA_TYPE_BOOLEAN, false],
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\LightSwitchPayload::OFF],
				],
				[
					[MetadataTypes\DataTypeShort::DATA_TYPE_SWITCH, MetadataTypes\SwitchPayload::PAYLOAD_TOGGLE],
					null,
					[MetadataTypes\DataTypeShort::DATA_TYPE_STRING, Types\LightSwitchPayload::TOGGLE],
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

		if ($propertyMatches['description'] === Types\SensorDescription::OUTPUT) {
			return 'turn';
		}

		if ($propertyMatches['description'] === Types\SensorDescription::ROLLER) {
			return 'go';
		}

		if ($propertyMatches['description'] === Types\SensorDescription::COLOR_TEMP) {
			return 'temp';
		}

		if ($propertyMatches['description'] === Types\SensorDescription::WHITE_LEVEL) {
			return 'white';
		}

		return $propertyMatches['description'];
	}

}
