<?php declare(strict_types = 1);

/**
 * OpenApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Tuya\API;

use FastyBird\Connector\Tuya;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Types;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use GuzzleHttp;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Psr\Http\Message;
use Psr\Log;
use Ramsey\Uuid;
use React\EventLoop;
use React\Http;
use React\Promise;
use React\Socket\Connector;
use RuntimeException;
use Throwable;
use function assert;
use function boolval;
use function count;
use function hash;
use function hash_hmac;
use function http_build_query;
use function implode;
use function intval;
use function is_array;
use function React\Async\await;
use function sprintf;
use function strval;
use function urldecode;
use const DIRECTORY_SEPARATOR;

/**
 * OpenAPI interface
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class OpenApi
{

	use Nette\SmartObject;

	private const CONNECTION_TIMEOUT = 30;

	private const VERSION = '0.1.0';

	private const TUYA_ERROR_CODE_TOKEN_INVALID = 1_010;

	private const TUYA_ERROR_CODE_API_ACCESS_NOT_ALLOWED = 1_114;

	private const ACCESS_TOKEN_API_ENDPOINT = '/v1.0/token';

	private const REFRESH_TOKEN_API_ENDPOINT = '/v1.0/token/%s';

	private const USER_DEVICES_API_ENDPOINT = '/v1.0/users/%s/devices';

	private const USER_DEVICE_DETAIL_API_ENDPOINT = '/v1.0/devices/%s';

	private const USER_DEVICES_FACTORY_INFOS_API_ENDPOINT = '/v1.0/devices/factory-infos';

	private const USER_DEVICE_SPECIFICATIONS_API_ENDPOINT = '/v1.0/devices/%s/specifications';

	private const USER_DEVICE_STATUS_API_ENDPOINT = '/v1.0/devices/%s/status';

	private const USER_DEVICE_CHILDREN_DEVICES_API_ENDPOINT = '/v1.0/devices/%s/sub-devices';

	private const DEVICES_API_ENDPOINT = '/v1.3/iot-03/devices';

	private const DEVICES_FACTORY_INFOS_API_ENDPOINT = '/v1.0/iot-03/devices/factory-infos';

	private const DEVICE_INFORMATION_API_ENDPOINT = '/v1.1/iot-03/devices/%s';

	private const DEVICE_SPECIFICATION_API_ENDPOINT = '/v1.2/iot-03/devices/%s/specification';

	private const DEVICE_STATUS_API_ENDPOINT = '/v1.0/iot-03/devices/%s/status';

	private const DEVICE_SEND_COMMAND_API_ENDPOINT = '/v1.0/iot-03/devices/%s/commands';

	public const ACCESS_TOKEN_MESSAGE_SCHEMA_FILENAME = 'openapi_access_token.json';

	public const REFRESH_TOKEN_MESSAGE_SCHEMA_FILENAME = 'openapi_refresh_token.json';

	public const USER_DEVICES_MESSAGE_SCHEMA_FILENAME = 'openapi_user_devices.json';

	public const USER_DEVICE_DETAIL_MESSAGE_SCHEMA_FILENAME = 'openapi_user_device_detail.json';

	public const USER_DEVICES_FACTORY_INFOS_MESSAGE_SCHEMA_FILENAME = 'openapi_user_devices_factory_infos.json';

	public const USER_DEVICE_SPECIFICATIONS_MESSAGE_SCHEMA_FILENAME = 'openapi_user_device_specifications.json';

	public const USER_DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME = 'openapi_user_device_status.json';

	public const USER_DEVICE_CHILDREN_DEVICES_MESSAGE_SCHEMA_FILENAME = 'openapi_user_device_children.json';

	public const DEVICES_MESSAGE_SCHEMA_FILENAME = 'openapi_devices.json';

	public const DEVICES_FACTORY_INFOS_MESSAGE_SCHEMA_FILENAME = 'openapi_devices_factory_infos.json';

	public const DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME = 'openapi_device_info.json';

	public const DEVICE_SPECIFICATION_MESSAGE_SCHEMA_FILENAME = 'openapi_device_specification.json';

	public const DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME = 'openapi_device_status.json';

	public const DEVICE_SEND_COMMAND_MESSAGE_SCHEMA_FILENAME = 'openapi_device_send_command.json';

	private string $devChannel = 'fastybird_iot';

	private Uuid\UuidInterface $nonce;

	private Entities\API\TuyaTokenInfo|null $tokenInfo = null;

	private GuzzleHttp\Client|null $client = null;

	private Http\Browser|null $asyncClient = null;

	private Promise\Deferred|null $refreshTokenPromise = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly string $identifier,
		private readonly string $accessId,
		private readonly string $accessSecret,
		private readonly string $lang,
		private readonly Types\OpenApiEndpoint $endpoint,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();

		$this->nonce = Uuid\Uuid::uuid1();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\OpenApiCall
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	public function connect(): void
	{
		$response = $this->callRequest(
			'GET',
			self::ACCESS_TOKEN_API_ENDPOINT,
			[
				'grant_type' => 1,
			],
			null,
			false,
		);

		if (!$response instanceof Message\ResponseInterface) {
			throw new Exceptions\InvalidState('Calling get access token returned invalid response');
		}

		try {
			$parsedMessage = $this->schemaValidator->validate(
				$response->getBody()->getContents(),
				$this->getSchemaFilePath(self::ACCESS_TOKEN_MESSAGE_SCHEMA_FILENAME),
			);
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			$response->getBody()->rewind();

			$this->logger->error(
				'Could not decode received access token response payload',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'openapi-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'path' => self::ACCESS_TOKEN_API_ENDPOINT,
						'params' => [
							'grant_type' => 1,
						],
					],
					'response' => [
						'body' => $response->getBody()->getContents(),
						'schema' => self::ACCESS_TOKEN_MESSAGE_SCHEMA_FILENAME,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\OpenApiCall('Could not decode received access token response payload');
		}

		$result = $parsedMessage->offsetGet('result');

		if (!$result instanceof Utils\ArrayHash) {
			throw new Exceptions\OpenApiCall('Received response is not valid');
		}

		$result->offsetSet(
			'expire_time',
			intval($parsedMessage->offsetGet('t')) + ($result->offsetExists('expire') ? $result->offsetGet(
				'expire',
			) : $result->offsetGet(
				'expire_time',
			)) * 1_000,
		);

		$this->tokenInfo = EntityFactory::build(
			Entities\API\TuyaTokenInfo::class,
			$result,
		);
	}

	public function disconnect(): void
	{
		$this->client = null;
		$this->asyncClient = null;

		$this->tokenInfo = null;
	}

	public function isConnected(): bool
	{
		return $this->tokenInfo !== null;
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	public function getUid(): string
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		if ($this->tokenInfo === null) {
			throw new Exceptions\OpenApiCall('Access token could not be created');
		}

		return $this->tokenInfo->getUid();
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	public function getUserDevices(
		string $userId,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf(self::USER_DEVICES_API_ENDPOINT, $userId),
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					try {
						$parsedMessage = $this->schemaValidator->validate(
							$response->getBody()->getContents(),
							$this->getSchemaFilePath(self::USER_DEVICES_MESSAGE_SCHEMA_FILENAME),
						);
					} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
						$response->getBody()->rewind();

						$this->logger->error(
							'Could not decode received response payload',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'openapi-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'response' => [
									'body' => $response->getBody()->getContents(),
									'schema' => self::USER_DEVICES_MESSAGE_SCHEMA_FILENAME,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$promise->reject(new Exceptions\OpenApiCall('Could not decode received response payload'));

						return;
					}

					$result = $parsedMessage->offsetGet('result');

					if (!$result instanceof Utils\ArrayHash) {
						$promise->reject(new Exceptions\OpenApiCall('Received response is not valid'));

						return;
					}

					$devices = [];

					foreach ($result as $deviceData) {
						if (!$deviceData instanceof Utils\ArrayHash) {
							continue;
						}

						$devices[] = EntityFactory::build(
							Entities\API\UserDeviceDetail::class,
							$deviceData,
						);
					}

					$promise->resolve($devices);
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
	 * @param array<string> $deviceIds
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	public function getUserDevicesFactoryInfos(
		array $deviceIds,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			self::USER_DEVICES_FACTORY_INFOS_API_ENDPOINT,
			[
				'device_ids' => implode(',', $deviceIds),
			],
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					try {
						$parsedMessage = $this->schemaValidator->validate(
							$response->getBody()->getContents(),
							$this->getSchemaFilePath(self::USER_DEVICES_FACTORY_INFOS_MESSAGE_SCHEMA_FILENAME),
						);
					} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
						$response->getBody()->rewind();

						$this->logger->error(
							'Could not decode received response payload',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'openapi-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'response' => [
									'body' => $response->getBody()->getContents(),
									'schema' => self::USER_DEVICES_FACTORY_INFOS_MESSAGE_SCHEMA_FILENAME,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$promise->reject(new Exceptions\OpenApiCall('Could not decode received response payload'));

						return;
					}

					$result = $parsedMessage->offsetGet('result');

					if (!$result instanceof Utils\ArrayHash) {
						$promise->reject(new Exceptions\OpenApiCall('Received response is not valid'));

						return;
					}

					$factoryInfos = [];

					foreach ($result as $deviceData) {
						if (!$deviceData instanceof Utils\ArrayHash) {
							continue;
						}

						$factoryInfos[] = EntityFactory::build(
							Entities\API\UserDeviceFactoryInfos::class,
							$deviceData,
						);
					}

					$promise->resolve($factoryInfos);
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
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	public function getUserDeviceDetail(
		string $deviceId,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf(self::USER_DEVICE_DETAIL_API_ENDPOINT, $deviceId),
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					try {
						$parsedMessage = $this->schemaValidator->validate(
							$response->getBody()->getContents(),
							$this->getSchemaFilePath(self::USER_DEVICE_DETAIL_MESSAGE_SCHEMA_FILENAME),
						);
					} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
						$response->getBody()->rewind();

						$this->logger->error(
							'Could not decode received response payload',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'openapi-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'response' => [
									'body' => $response->getBody()->getContents(),
									'schema' => self::USER_DEVICE_DETAIL_MESSAGE_SCHEMA_FILENAME,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$promise->reject(new Exceptions\OpenApiCall('Could not decode received response payload'));

						return;
					}

					$result = $parsedMessage->offsetGet('result');

					if (!$result instanceof Utils\ArrayHash) {
						$promise->reject(new Exceptions\OpenApiCall('Received response is not valid'));

						return;
					}

					$deviceStatus = [];

					if (
						$result->offsetExists('status')
						&& (
							is_array($result->offsetGet('status'))
							|| $result->offsetGet('status') instanceof Utils\ArrayHash
						)
					) {
						foreach ($result->offsetGet('status') as $item) {
							assert($item instanceof Utils\ArrayHash);

							$deviceStatus[] = EntityFactory::build(
								Entities\API\UserDeviceDataPointStatus::class,
								$item,
							);
						}
					}

					$result->offsetSet('status', $deviceStatus);

					$promise->resolve(EntityFactory::build(
						Entities\API\UserDeviceDetail::class,
						$result,
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
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	public function getUserDeviceSpecifications(
		string $deviceId,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf(self::USER_DEVICE_SPECIFICATIONS_API_ENDPOINT, $deviceId),
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					try {
						$parsedMessage = $this->schemaValidator->validate(
							$response->getBody()->getContents(),
							$this->getSchemaFilePath(self::USER_DEVICE_SPECIFICATIONS_MESSAGE_SCHEMA_FILENAME),
						);
					} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
						$response->getBody()->rewind();

						$this->logger->error(
							'Could not decode received response payload',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'openapi-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'response' => [
									'body' => $response->getBody()->getContents(),
									'schema' => self::USER_DEVICE_SPECIFICATIONS_MESSAGE_SCHEMA_FILENAME,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$promise->reject(new Exceptions\OpenApiCall('Could not decode received response payload'));

						return;
					}

					$result = $parsedMessage->offsetGet('result');

					if (!$result instanceof Utils\ArrayHash) {
						$promise->reject(new Exceptions\OpenApiCall('Received response is not valid'));

						return;
					}

					$deviceFunctions = [];

					if (
						$result->offsetExists('functions')
						&& (
							is_array($result->offsetGet('functions'))
							|| $result->offsetGet('functions') instanceof Utils\ArrayHash
						)
					) {
						foreach ($result->offsetGet('functions') as $item) {
							assert($item instanceof Utils\ArrayHash);

							$deviceFunctions[] = EntityFactory::build(
								Entities\API\UserDeviceSpecificationsFunction::class,
								$item,
							);
						}
					}

					$result->offsetSet('functions', $deviceFunctions);

					$deviceStatus = [];

					if (
						$result->offsetExists('status')
						&& (
							is_array($result->offsetGet('status'))
							|| $result->offsetGet('status') instanceof Utils\ArrayHash
						)
					) {
						foreach ($result->offsetGet('status') as $item) {
							assert($item instanceof Utils\ArrayHash);

							$deviceStatus[] = EntityFactory::build(
								Entities\API\UserDeviceSpecificationsStatus::class,
								$item,
							);
						}
					}

					$result->offsetSet('status', $deviceStatus);

					$promise->resolve(EntityFactory::build(
						Entities\API\UserDeviceSpecifications::class,
						$result,
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
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	public function getUserDeviceStatus(
		string $deviceId,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf(self::USER_DEVICE_STATUS_API_ENDPOINT, $deviceId),
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					try {
						$parsedMessage = $this->schemaValidator->validate(
							$response->getBody()->getContents(),
							$this->getSchemaFilePath(self::USER_DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME),
						);
					} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
						$response->getBody()->rewind();

						$this->logger->error(
							'Could not decode received response payload',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'openapi-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'response' => [
									'body' => $response->getBody()->getContents(),
									'schema' => self::USER_DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$promise->reject(new Exceptions\OpenApiCall('Could not decode received response payload'));

						return;
					}

					$result = $parsedMessage->offsetGet('result');

					if (!$result instanceof Utils\ArrayHash) {
						$promise->reject(new Exceptions\OpenApiCall('Received response is not valid'));

						return;
					}

					$statuses = [];

					foreach ($result as $statusData) {
						if (!$statusData instanceof Utils\ArrayHash) {
							continue;
						}

						$statuses[] = EntityFactory::build(
							Entities\API\UserDeviceDataPointStatus::class,
							$statusData,
						);
					}

					$promise->resolve($statuses);
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
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	public function getUserDeviceChildren(
		string $deviceId,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf(self::USER_DEVICE_CHILDREN_DEVICES_API_ENDPOINT, $deviceId),
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					try {
						$parsedMessage = $this->schemaValidator->validate(
							$response->getBody()->getContents(),
							$this->getSchemaFilePath(self::USER_DEVICE_CHILDREN_DEVICES_MESSAGE_SCHEMA_FILENAME),
						);
					} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
						$response->getBody()->rewind();

						$this->logger->error(
							'Could not decode received response payload',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'openapi-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'response' => [
									'body' => $response->getBody()->getContents(),
									'schema' => self::USER_DEVICE_CHILDREN_DEVICES_MESSAGE_SCHEMA_FILENAME,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$promise->reject(new Exceptions\OpenApiCall('Could not decode received response payload'));

						return;
					}

					$result = $parsedMessage->offsetGet('result');

					if (!$result instanceof Utils\ArrayHash) {
						$promise->reject(new Exceptions\OpenApiCall('Received response is not valid'));

						return;
					}

					$children = [];

					foreach ($result as $childrenData) {
						if (!$childrenData instanceof Utils\ArrayHash) {
							continue;
						}

						$children[] = EntityFactory::build(
							Entities\API\UserDeviceChild::class,
							$childrenData,
						);
					}

					$promise->resolve($children);
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
	 * @param array<string, mixed> $params
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	public function getDevices(
		array $params = [],
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			self::DEVICES_API_ENDPOINT,
			$params,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					try {
						$parsedMessage = $this->schemaValidator->validate(
							$response->getBody()->getContents(),
							$this->getSchemaFilePath(self::DEVICES_MESSAGE_SCHEMA_FILENAME),
						);
					} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
						$response->getBody()->rewind();

						$this->logger->error(
							'Could not decode received response payload',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'openapi-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'response' => [
									'body' => $response->getBody()->getContents(),
									'schema' => self::DEVICES_MESSAGE_SCHEMA_FILENAME,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$promise->reject(new Exceptions\OpenApiCall('Could not decode received response payload'));

						return;
					}

					$result = $parsedMessage->offsetGet('result');

					if (!$result instanceof Utils\ArrayHash) {
						$promise->reject(new Exceptions\OpenApiCall('Received response is not valid'));

						return;
					}

					$list = $result->offsetGet('list');

					if (!$list instanceof Utils\ArrayHash) {
						$promise->reject(new Exceptions\OpenApiCall('Received response is not valid'));

						return;
					}

					$devices = [];

					foreach ($list as $deviceData) {
						if (!$deviceData instanceof Utils\ArrayHash) {
							continue;
						}

						$devices[] = EntityFactory::build(
							Entities\API\DeviceInformation::class,
							$deviceData,
						);
					}

					$promise->resolve($devices);
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
	 * @param array<string> $deviceIds
	 *
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	public function getDevicesFactoryInfos(
		array $deviceIds,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			self::DEVICES_FACTORY_INFOS_API_ENDPOINT,
			[
				'device_ids' => implode(',', $deviceIds),
			],
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					try {
						$parsedMessage = $this->schemaValidator->validate(
							$response->getBody()->getContents(),
							$this->getSchemaFilePath(self::DEVICES_FACTORY_INFOS_MESSAGE_SCHEMA_FILENAME),
						);
					} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
						$response->getBody()->rewind();

						$this->logger->error(
							'Could not decode received response payload',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'openapi-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'response' => [
									'body' => $response->getBody()->getContents(),
									'schema' => self::DEVICES_FACTORY_INFOS_MESSAGE_SCHEMA_FILENAME,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$promise->reject(new Exceptions\OpenApiCall('Could not decode received response payload'));

						return;
					}

					$result = $parsedMessage->offsetGet('result');

					if (!$result instanceof Utils\ArrayHash) {
						$promise->reject(new Exceptions\OpenApiCall('Received response is not valid'));

						return;
					}

					$factoryInfos = [];

					foreach ($result as $deviceData) {
						if (!$deviceData instanceof Utils\ArrayHash) {
							continue;
						}

						$factoryInfos[] = EntityFactory::build(
							Entities\API\DeviceFactoryInfos::class,
							$deviceData,
						);
					}

					$promise->resolve($factoryInfos);
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
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	public function getDeviceInformation(
		string $deviceId,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf(self::DEVICE_INFORMATION_API_ENDPOINT, $deviceId),
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					try {
						$parsedMessage = $this->schemaValidator->validate(
							$response->getBody()->getContents(),
							$this->getSchemaFilePath(self::DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME),
						);
					} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
						$response->getBody()->rewind();

						$this->logger->error(
							'Could not decode received response payload',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'openapi-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'response' => [
									'body' => $response->getBody()->getContents(),
									'schema' => self::DEVICE_INFORMATION_MESSAGE_SCHEMA_FILENAME,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$promise->reject(new Exceptions\OpenApiCall('Could not decode received response payload'));

						return;
					}

					$result = $parsedMessage->offsetGet('result');

					if (!$result instanceof Utils\ArrayHash) {
						$promise->reject(new Exceptions\OpenApiCall('Received response is not valid'));

						return;
					}

					$promise->resolve(EntityFactory::build(
						Entities\API\DeviceInformation::class,
						$result,
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
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	public function getDeviceSpecification(
		string $deviceId,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf(self::DEVICE_SPECIFICATION_API_ENDPOINT, $deviceId),
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					try {
						$parsedMessage = $this->schemaValidator->validate(
							$response->getBody()->getContents(),
							$this->getSchemaFilePath(self::DEVICE_SPECIFICATION_MESSAGE_SCHEMA_FILENAME),
						);
					} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
						$response->getBody()->rewind();

						$this->logger->error(
							'Could not decode received response payload',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'openapi-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'response' => [
									'body' => $response->getBody()->getContents(),
									'schema' => self::DEVICE_SPECIFICATION_MESSAGE_SCHEMA_FILENAME,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$promise->reject(new Exceptions\OpenApiCall('Could not decode received response payload'));

						return;
					}

					$result = $parsedMessage->offsetGet('result');

					if (!$result instanceof Utils\ArrayHash) {
						$promise->reject(new Exceptions\OpenApiCall('Received response is not valid'));

						return;
					}

					$deviceFunctions = [];

					if (
						$result->offsetExists('functions')
						&& (
							is_array($result->offsetGet('functions'))
							|| $result->offsetGet('functions') instanceof Utils\ArrayHash
						)
					) {
						foreach ($result->offsetGet('functions') as $item) {
							assert($item instanceof Utils\ArrayHash);

							$deviceFunctions[] = EntityFactory::build(
								Entities\API\DeviceSpecificationFunction::class,
								$item,
							);
						}
					}

					$result->offsetSet('functions', $deviceFunctions);

					$deviceStatus = [];

					if (
						$result->offsetExists('status')
						&& (
							is_array($result->offsetGet('status'))
							|| $result->offsetGet('status') instanceof Utils\ArrayHash
						)
					) {
						foreach ($result->offsetGet('status') as $item) {
							assert($item instanceof Utils\ArrayHash);

							$deviceStatus[] = EntityFactory::build(
								Entities\API\DeviceSpecificationStatus::class,
								$item,
							);
						}
					}

					$result->offsetSet('status', $deviceStatus);

					$promise->resolve(EntityFactory::build(
						Entities\API\DeviceSpecification::class,
						$result,
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
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	public function getDeviceStatus(
		string $deviceId,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$promise = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf(self::DEVICE_STATUS_API_ENDPOINT, $deviceId),
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					try {
						$parsedMessage = $this->schemaValidator->validate(
							$response->getBody()->getContents(),
							$this->getSchemaFilePath(self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME),
						);
					} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
						$response->getBody()->rewind();

						$this->logger->error(
							'Could not decode received response payload',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'openapi-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'response' => [
									'body' => $response->getBody()->getContents(),
									'schema' => self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$promise->reject(new Exceptions\OpenApiCall('Could not decode received response payload'));

						return;
					}

					$result = $parsedMessage->offsetGet('result');

					if (!$result instanceof Utils\ArrayHash) {
						$promise->reject(new Exceptions\OpenApiCall('Received response is not valid'));

						return;
					}

					$statuses = [];

					foreach ($result as $statusData) {
						if (!$statusData instanceof Utils\ArrayHash) {
							continue;
						}

						$statuses[] = EntityFactory::build(
							Entities\API\DeviceDataPointStatus::class,
							$statusData,
						);
					}

					$promise->resolve($statuses);
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
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	public function setDeviceStatus(
		string $deviceId,
		string $code,
		string|int|float|bool $value,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		$promise = new Promise\Deferred();

		try {
			$body = Utils\Json::encode([
				'commands' => [
					[
						'code' => $code,
						'value' => $value,
					],
				],
			]);
		} catch (Utils\JsonException $ex) {
			return Promise\reject(new Exceptions\OpenApiCall(
				'Message body could not be encoded',
				$ex->getCode(),
				$ex,
			));
		}

		$result = $this->callRequest(
			'POST',
			sprintf(self::DEVICE_SEND_COMMAND_API_ENDPOINT, $deviceId),
			[],
			$body,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($promise): void {
					try {
						$parsedMessage = $this->schemaValidator->validate(
							$response->getBody()->getContents(),
							$this->getSchemaFilePath(self::DEVICE_SEND_COMMAND_MESSAGE_SCHEMA_FILENAME),
						);
					} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
						$response->getBody()->rewind();

						$this->logger->error(
							'Could not decode received response payload',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'openapi-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'response' => [
									'body' => $response->getBody()->getContents(),
									'schema' => self::DEVICE_SEND_COMMAND_MESSAGE_SCHEMA_FILENAME,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$promise->reject(new Exceptions\OpenApiCall('Could not decode received response payload'));

						return;
					}

					$promise->resolve(boolval($parsedMessage->offsetGet('result')));
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
	 * @param array<string, mixed> $params
	 *
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	private function callRequest(
		string $method,
		string $path,
		array $params = [],
		string|null $body = null,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Message\ResponseInterface|false
	{
		$refreshTokenResult = $this->refreshAccessToken($path);

		if ($refreshTokenResult instanceof Promise\PromiseInterface) {
			try {
				await($refreshTokenResult);
			} catch (Throwable $ex) {
				$this->logger->error('Awaiting for refresh token promise failed', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'openapi-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'identifier' => $this->identifier,
					],
				]);
			}
		}

		$deferred = new Promise\Deferred();

		$requestPath = $this->endpoint->getValue() . $path;

		if (count($params) > 0) {
			$requestPath .= '?';
			$requestPath .= http_build_query($params);
		}

		$headers = $this->buildRequestHeaders($method, $path, $params, $body);

		$this->logger->debug(sprintf(
			'Request: method = %s url = %s',
			$method,
			$this->endpoint->getValue() . $path,
		), [
			'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
			'type' => 'openapi-api',
			'request' => [
				'method' => $method,
				'path' => $path,
				'headers' => $headers,
				'params' => $params,
				'body' => $body,
			],
			'connector' => [
				'identifier' => $this->identifier,
			],
		]);

		if ($async) {
			try {
				$request = $this->getClient()->request(
					$method,
					$requestPath,
					$headers,
					$body ?? '',
				);

				assert($request instanceof Promise\PromiseInterface);

				$request
					->then(
						function (Message\ResponseInterface $response) use ($deferred, $method, $path, $headers, $params, $body): void {
							$this->logger->debug('Received response', [
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'openapi-api',
								'request' => [
									'method' => $method,
									'path' => $path,
									'headers' => $headers,
									'params' => $params,
									'body' => $body,
								],
								'response' => [
									'status_code' => $response->getStatusCode(),
									'body' => $response->getBody()->getContents(),
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							]);

							$response->getBody()->rewind();

							try {
								$response = $this->checkResponse($path, $response);

							} catch (Exceptions\OpenApiCall $ex) {
								$this->logger->error('Received payload is not valid', [
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
									'type' => 'openapi-api',
									'exception' => BootstrapHelpers\Logger::buildException($ex),
									'request' => [
										'method' => $method,
										'path' => $path,
										'headers' => $headers,
										'params' => $params,
										'body' => $body,
									],
									'connector' => [
										'identifier' => $this->identifier,
									],
								]);

								$deferred->reject($ex);

								return;
							}

							$deferred->resolve($response);
						},
						function (Throwable $ex) use ($deferred, $method, $path, $headers, $params, $body): void {
							$this->logger->error('Calling api endpoint failed', [
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'openapi-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'request' => [
									'method' => $method,
									'path' => $path,
									'headers' => $headers,
									'params' => $params,
									'body' => $body,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							]);

							$deferred->reject($ex);
						},
					);
			} catch (Throwable $ex) {
				$deferred->reject($ex);
			}

			return $deferred->promise();
		} else {
			try {
				$response = $this->getClient(false)->request(
					$method,
					$requestPath,
					[
						'headers' => $headers,
						'body' => $body ?? '',
					],
				);

				assert($response instanceof Message\ResponseInterface);

				$this->logger->debug('Received response', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'openapi-api',
					'request' => [
						'method' => $method,
						'path' => $path,
						'headers' => $headers,
						'params' => $params,
						'body' => $body,
					],
					'response' => [
						'status_code' => $response->getStatusCode(),
						'body' => $response->getBody()->getContents(),
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				]);

				$response->getBody()->rewind();

				$response = $this->checkResponse($path, $response);

			} catch (GuzzleHttp\Exception\GuzzleException | InvalidArgumentException $ex) {
				$this->logger->error('Calling api endpoint failed', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'openapi-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'method' => $method,
						'path' => $path,
						'headers' => $headers,
						'params' => $params,
						'body' => $body,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				]);

				return false;
			} catch (Exceptions\OpenApiCall $ex) {
				$this->logger->error('Received payload is not valid', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'openapi-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'method' => $method,
						'path' => $path,
						'headers' => $headers,
						'params' => $params,
						'body' => $body,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				]);

				return false;
			}

			return $response;
		}
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws MetadataExceptions\Logic
	 * @throws RuntimeException
	 */
	private function checkResponse(string $path, Message\ResponseInterface $response): Message\ResponseInterface
	{
		$body = $response->getBody()->getContents();

		try {
			$decodedResponse = Utils\Json::decode($body, Utils\Json::FORCE_ARRAY);

		} catch (Utils\JsonException) {
			throw new Exceptions\OpenApiCall('Received response body is not valid JSON');
		}

		if (!is_array($decodedResponse)) {
			throw new Exceptions\OpenApiCall('Received response body is not valid JSON');
		}

		$data = Utils\ArrayHash::from($decodedResponse);

		if (
			$data->offsetExists('code')
			&& $data->offsetGet('code') === self::TUYA_ERROR_CODE_TOKEN_INVALID
		) {
			$this->tokenInfo = null;

			if ($path !== self::ACCESS_TOKEN_API_ENDPOINT) {
				$this->connect();

			} else {
				throw new Exceptions\OpenApiCall('API token is not valid and can not be refreshed');
			}
		}

		if (
			$data->offsetExists('success')
			&& boolval($data->offsetGet('success')) !== true
		) {
			if ($data->offsetExists('msg')) {
				throw new Exceptions\OpenApiCall(strval($data->offsetGet('msg')));
			}

			throw new Exceptions\OpenApiCall('Received response is not success');
		}

		$response->getBody()->rewind();

		return $response;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\OpenApiCall
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws RuntimeException
	 */
	private function refreshAccessToken(string $path): Promise\PromiseInterface|false
	{
		if (Utils\Strings::startsWith($path, self::ACCESS_TOKEN_API_ENDPOINT)) {
			return false;
		}

		if ($this->tokenInfo === null) {
			return false;
		}

		if (!$this->tokenInfo->isExpired($this->dateTimeFactory->getNow())) {
			return false;
		}

		if ($this->refreshTokenPromise !== null) {
			return $this->refreshTokenPromise->promise();
		}

		$this->refreshTokenPromise = new Promise\Deferred();

		$path = sprintf(self::REFRESH_TOKEN_API_ENDPOINT, $this->tokenInfo->getRefreshToken());
		$headers = $this->buildRequestHeaders('get', $path);

		try {
			$this->logger->debug(sprintf(
				'Request: method = %s url = %s',
				'get',
				$this->endpoint->getValue() . $path,
			), [
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'openapi-api',
				'request' => [
					'method' => 'get',
					'path' => $path,
					'headers' => $headers,
				],
				'connector' => [
					'identifier' => $this->identifier,
				],
			]);

			$response = $this->getClient(false)->get(
				$this->endpoint->getValue() . $path,
				[
					'headers' => $headers,
				],
			);

			assert($response instanceof Message\ResponseInterface);

			$this->logger->debug('Received response', [
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'openapi-api',
				'request' => [
					'method' => 'get',
					'path' => $path,
					'headers' => $headers,
				],
				'response' => [
					'status_code' => $response->getStatusCode(),
					'body' => $response->getBody()->getContents(),
				],
				'connector' => [
					'identifier' => $this->identifier,
				],
			]);

			$response->getBody()->rewind();

			$body = $response->getBody()->getContents();

			$response->getBody()->rewind();

			try {
				$decodedResponse = Utils\Json::decode($body, Utils\Json::FORCE_ARRAY);

			} catch (Utils\JsonException) {
				$error = new Exceptions\OpenApiCall('Received response body is not valid JSON');

				$this->refreshTokenPromise->reject($error);
				$this->refreshTokenPromise = null;

				return Promise\reject($error);
			}

			if (!is_array($decodedResponse)) {
				$error = new Exceptions\OpenApiCall('Received response body is not valid JSON');

				$this->refreshTokenPromise->reject($error);
				$this->refreshTokenPromise = null;

				return Promise\reject($error);
			}

			$data = Utils\ArrayHash::from($decodedResponse);

			if (
				$data->offsetExists('success')
				&& boolval($data->offsetGet('success')) !== true
			) {
				// TUYA api has something wrong and refreshing toke is not allowed
				// According to response, /v1.0/token/{refresh_token} is not allowed to access
				// Workaround is to reconnect to obtain new tokens pair
				if (
					$data->offsetExists('code')
					&& intval($data->offsetGet('code')) === self::TUYA_ERROR_CODE_API_ACCESS_NOT_ALLOWED
				) {
					$this->logger->warning(
						'Refresh token api endpoint is not allowed to access',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
							'type' => 'openapi-api',
							'request' => [
								'method' => 'get',
								'path' => $path,
								'headers' => $headers,
							],
							'response' => [
								'body' => $response->getBody()->getContents(),
								'schema' => self::REFRESH_TOKEN_MESSAGE_SCHEMA_FILENAME,
							],
							'connector' => [
								'identifier' => $this->identifier,
							],
						],
					);

					$this->tokenInfo = null;

					$this->connect();

					$this->refreshTokenPromise?->resolve();
					$this->refreshTokenPromise = null;

					return Promise\resolve();
				} else {
					if ($data->offsetExists('msg')) {
						$error = new Exceptions\OpenApiCall(strval($data->offsetGet('msg')));

						$this->refreshTokenPromise->reject($error);
						$this->refreshTokenPromise = null;

						return Promise\reject($error);
					}

					$error = new Exceptions\OpenApiCall('Received response is not success');

					$this->refreshTokenPromise->reject($error);
					$this->refreshTokenPromise = null;

					return Promise\reject($error);
				}
			}

			try {
				$parsedMessage = $this->schemaValidator->validate(
					$response->getBody()->getContents(),
					$this->getSchemaFilePath(self::REFRESH_TOKEN_MESSAGE_SCHEMA_FILENAME),
				);
			} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
				$response->getBody()->rewind();

				$this->logger->error(
					'Could not decode received refresh token response payload',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
						'type' => 'openapi-api',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'request' => [
							'method' => 'get',
							'path' => $path,
							'headers' => $headers,
						],
						'response' => [
							'body' => $response->getBody()->getContents(),
							'schema' => self::REFRESH_TOKEN_MESSAGE_SCHEMA_FILENAME,
						],
						'connector' => [
							'identifier' => $this->identifier,
						],
					],
				);

				$error = new Exceptions\OpenApiCall('Could not decode received refresh token response payload');

				$this->refreshTokenPromise->reject($error);
				$this->refreshTokenPromise = null;

				return Promise\reject($error);
			}

			$result = $parsedMessage->offsetGet('result');

			if (!$result instanceof Utils\ArrayHash) {
				$error = new Exceptions\OpenApiCall('Received response is not valid');

				$this->refreshTokenPromise->reject($error);
				$this->refreshTokenPromise = null;

				return Promise\reject($error);
			}

			$result->offsetSet(
				'expire_time',
				intval($parsedMessage->offsetGet('t')) + ($result->offsetExists('expire') ? $result->offsetGet(
					'expire',
				) : $result->offsetGet(
					'expire_time',
				)) * 1_000,
			);

			$this->tokenInfo = EntityFactory::build(
				Entities\API\TuyaTokenInfo::class,
				$result,
			);

			$this->refreshTokenPromise->resolve();
			$this->refreshTokenPromise = null;

			return Promise\resolve();
		} catch (GuzzleHttp\Exception\GuzzleException | InvalidArgumentException $ex) {
			$this->logger->error(
				'Could not refresh access token',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
					'type' => 'openapi-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'method' => 'get',
						'path' => $path,
						'headers' => $headers,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			$error = new Exceptions\OpenApiCall('Could not refresh access token');

			$this->refreshTokenPromise->reject($error);
			$this->refreshTokenPromise = null;

			return Promise\reject($error);
		}
	}

	/**
	 * @param array<string, mixed> $params
	 *
	 * @return array<string, string|int>
	 */
	private function buildRequestHeaders(
		string $method,
		string $path,
		array $params = [],
		string|null $body = null,
	): array
	{
		$accessToken = $this->tokenInfo?->getAccessToken() ?? '';

		$sign = $this->calculateSign(
			Utils\Strings::startsWith($path, self::ACCESS_TOKEN_API_ENDPOINT) ? '' : $accessToken,
			$method,
			$path,
			$params,
			$body,
		);

		return [
			'client_id' => $this->accessId,
			'nonce' => $this->nonce->toString(),
			'Signature-Headers' => 'client_id',
			'sign' => $sign->getSign(),
			'sign_method' => 'HMAC-SHA256',
			'access_token' => Utils\Strings::startsWith($path, self::ACCESS_TOKEN_API_ENDPOINT) ? '' : $accessToken,
			't' => $sign->getTimestamp(),
			'lang' => $this->lang,
			'dev_lang' => 'php',
			'dev_version' => self::VERSION,
			'dev_channel' => 'cloud_' . $this->devChannel,
		];
	}

	/**
	 * @param array<string, mixed> $params
	 */
	private function calculateSign(
		string $accessToken,
		string $method,
		string $path,
		array $params = [],
		string|null $body = null,
	): Entities\API\Sign
	{
		$strToSign = Utils\Strings::upper($method);
		$strToSign .= "\n";

		// Content-SHA256
		$contentToSha256 = $body === null || $body === '' ? '' : $body;

		$strToSign .= hash('sha256', $contentToSha256);
		$strToSign .= "\n";

		// Header
		$strToSign .= 'client_id:' . $this->accessId;
		$strToSign .= "\n";
		$strToSign .= "\n";

		// URL
		$strToSign .= $path;

		if (count($params) > 0) {
			$strToSign .= '?';
			$strToSign .= urldecode(http_build_query($params));
		}

		// Sign
		$timestamp = intval($this->dateTimeFactory->getNow()->format('Uv'));

		$message = $this->accessId . $accessToken . $timestamp . $this->nonce->toString() . $strToSign;

		$sign = Utils\Strings::upper(hash_hmac('sha256', $message, $this->accessSecret));

		return new Entities\API\Sign($sign, $timestamp);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	private function getClient(bool $async = true): GuzzleHttp\Client|Http\Browser
	{
		if ($async) {
			if ($this->asyncClient === null) {
				$this->asyncClient = new Http\Browser(
					new Connector(
						[
							'timeout' => self::CONNECTION_TIMEOUT,
						],
						$this->eventLoop,
					),
					$this->eventLoop,
				);
			}

			return $this->asyncClient;
		} else {
			if ($this->client === null) {
				$this->client = new GuzzleHttp\Client();
			}

			return $this->client;
		}
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 */
	private function getSchemaFilePath(string $schemaFilename): string
	{
		try {
			$schema = Utils\FileSystem::read(
				Tuya\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
			);

		} catch (Nette\IOException) {
			throw new Exceptions\OpenApiCall('Validation schema for response could not be loaded');
		}

		return $schema;
	}

}
