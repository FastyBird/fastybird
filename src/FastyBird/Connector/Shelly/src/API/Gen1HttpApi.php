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
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use Psr\Log;
use React\EventLoop;
use React\Promise;
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
use function sprintf;
use function strval;

/**
 * Generation 1 device http api interface
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

	public const DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME = 'gen1_http_shelly.json';

	public const DEVICE_DESCRIPTION_MESSAGE_SCHEMA_FILENAME = 'gen1_http_description.json';

	public const DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME = 'gen1_http_status.json';

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
		Types\SensorDescription::TYPE_MODE,
		Types\SensorDescription::TYPE_OUTPUT,
		Types\SensorDescription::TYPE_ROLLER,
		Types\SensorDescription::TYPE_RED,
		Types\SensorDescription::TYPE_GREEN,
		Types\SensorDescription::TYPE_BLUE,
		Types\SensorDescription::TYPE_WHITE,
		Types\SensorDescription::TYPE_GAIN,
		Types\SensorDescription::TYPE_BRIGHTNESS,
		Types\SensorDescription::TYPE_COLOR_TEMP,
		Types\SensorDescription::TYPE_WHITE_LEVEL,
	];

	public function __construct(
		private readonly EntityFactory $entityFactory,
		private readonly MetadataSchemas\Validator $schemaValidator,
		EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		parent::__construct($eventLoop, $logger);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getDeviceInformation(
		string $address,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf(self::DEVICE_INFORMATION, $address),
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					$parsedMessage = $this->schemaValidator->validate(
						$response->getBody()->getContents(),
						$this->getSchemaFilePath(self::DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME),
					);

					$information = $this->entityFactory->build(
						Entities\API\Gen1\DeviceInformation::class,
						$parsedMessage,
					);

					$promise->resolve($information);
				})
				->otherwise(static function (Throwable $ex) use ($promise): void {
					$promise->reject($ex);
				});
		} else {
			throw new Exceptions\InvalidState('Request promise could not be created');
		}

		return $promise->promise();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getDeviceDescription(
		string $address,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf(self::DEVICE_DESCRIPTION, $address),
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
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

					$promise->resolve(new Entities\API\Gen1\DeviceDescription($descriptionBlocks));
				})
				->otherwise(static function (Throwable $ex) use ($promise): void {
					$promise->reject($ex);
				});
		} else {
			throw new Exceptions\InvalidState('Request promise could not be created');
		}

		return $promise->promise();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getDeviceStatus(
		string $address,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf(self::DEVICE_STATUS, $address),
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					$parsedMessage = $this->schemaValidator->validate(
						$response->getBody()->getContents(),
						$this->getSchemaFilePath(self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME),
					);

					$relays = [];

					if (
						$parsedMessage->offsetExists('relays')
						&& is_array($parsedMessage->offsetGet('relays'))
					) {
						foreach ($parsedMessage->offsetGet('relays') as $relayStatus) {
							assert($relayStatus instanceof Utils\ArrayHash);

							$relays[] = $this->entityFactory->build(
								Entities\API\Gen1\DeviceRelayStatus::class,
								$relayStatus,
							);
						}
					}

					$rollers = [];

					if (
						$parsedMessage->offsetExists('rollers')
						&& is_array($parsedMessage->offsetGet('rollers'))
					) {
						foreach ($parsedMessage->offsetGet('rollers') as $rollerStatus) {
							assert($rollerStatus instanceof Utils\ArrayHash);

							$rollers[] = $this->entityFactory->build(
								Entities\API\Gen1\DeviceRollerStatus::class,
								$rollerStatus,
							);
						}
					}

					$inputs = [];

					if (
						$parsedMessage->offsetExists('inputs')
						&& is_array($parsedMessage->offsetGet('inputs'))
					) {
						foreach ($parsedMessage->offsetGet('inputs') as $inputStatus) {
							assert($inputStatus instanceof Utils\ArrayHash);

							$inputs[] = $this->entityFactory->build(
								Entities\API\Gen1\DeviceInputStatus::class,
								$inputStatus,
							);
						}
					}

					$lights = [];

					if (
						$parsedMessage->offsetExists('lights')
						&& is_array($parsedMessage->offsetGet('lights'))
					) {
						foreach ($parsedMessage->offsetGet('lights') as $lightStatus) {
							assert($lightStatus instanceof Utils\ArrayHash);

							$lights[] = $this->entityFactory->build(
								Entities\API\Gen1\DeviceLightStatus::class,
								$lightStatus,
							);
						}
					}

					$promise->resolve(new Entities\API\Gen1\DeviceStatus(
						$relays,
						$rollers,
						$inputs,
						$lights,
					));
				})
				->otherwise(static function (Throwable $ex) use ($promise): void {
					$promise->reject($ex);
				});
		} else {
			throw new Exceptions\InvalidState('Request promise could not be created');
		}

		return $promise->promise();
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function setDeviceStatus(
		string $address,
		string $sensor,
		int $channel,
		string $action,
		string|int|float|bool $value,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf(
				self::DEVICE_ACTION,
				$address,
				$sensor,
				$channel,
				$action,
				$value,
			),
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(static function () use ($promise): void {
					$promise->resolve();
				})
				->otherwise(static function (Throwable $ex) use ($promise): void {
					$promise->reject($ex);
				});
		} else {
			throw new Exceptions\InvalidState('Request promise could not be created');
		}

		return $promise->promise();
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
		if (Utils\Strings::startsWith($block, 'relay') && Utils\Strings::lower($description) === 'output') {
			return MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SWITCH);
		}

		if (Utils\Strings::startsWith($block, 'light') && Utils\Strings::lower($description) === 'output') {
			return MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_SWITCH);
		}

		return $dataType;
	}

	/**
	 * @param array<string>|array<int>|array<float>|null $format
	 *
	 * @return array<string>|array<int>|array<float>|array<int, array<int, (string|null)>>|array<int, (int|null)>|array<int, (float|null)>|array<int, (MetadataTypes\SwitchPayload|string|Types\RelayPayload|null)>|null
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

		if (Utils\Strings::startsWith($block, 'roller') && Utils\Strings::lower($description) === 'roller') {
			return [
				[MetadataTypes\CoverPayload::PAYLOAD_OPEN, Types\RollerPayload::PAYLOAD_OPEN, null],
				[MetadataTypes\CoverPayload::PAYLOAD_OPENED, null, Types\RollerPayload::PAYLOAD_OPEN],
				[MetadataTypes\CoverPayload::PAYLOAD_CLOSE, Types\RollerPayload::PAYLOAD_CLOSE, null],
				[MetadataTypes\CoverPayload::PAYLOAD_CLOSED, null, Types\RollerPayload::PAYLOAD_CLOSE],
				[MetadataTypes\CoverPayload::PAYLOAD_STOP, Types\RollerPayload::PAYLOAD_STOP, null],
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
