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
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Services;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Connector\Tuya\ValueObjects;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp;
use InvalidArgumentException;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use Psr\Http\Message;
use Ramsey\Uuid;
use React\Promise;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function boolval;
use function count;
use function hash;
use function hash_hmac;
use function http_build_query;
use function implode;
use function intval;
use function is_array;
use function md5;
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

	private const VERSION = '0.1.0';

	private const TUYA_ERROR_CODE_TOKEN_INVALID = 1_010;

	private const TUYA_ERROR_CODE_API_ACCESS_NOT_ALLOWED = 1_114;

	private const GET_ACCESS_TOKEN_API_ENDPOINT = '/v1.0/token';

	private const REFRESH_ACCESS_TOKEN_API_ENDPOINT = '/v1.0/token/%s';

	private const GET_USER_DEVICES_API_ENDPOINT = '/v1.0/users/%s/devices';

	private const GET_USER_DEVICE_DETAIL_API_ENDPOINT = '/v1.0/devices/%s';

	private const GET_USER_DEVICES_FACTORY_INFOS_API_ENDPOINT = '/v1.0/devices/factory-infos';

	private const GET_USER_DEVICE_SPECIFICATIONS_API_ENDPOINT = '/v1.0/devices/%s/specifications';

	private const GET_USER_DEVICE_STATE_API_ENDPOINT = '/v1.0/devices/%s/status';

	private const GET_USER_DEVICE_CHILDREN_DEVICES_API_ENDPOINT = '/v1.0/devices/%s/sub-devices';

	private const GET_DEVICES_API_ENDPOINT = '/v1.3/iot-03/devices';

	private const GET_DEVICES_FACTORY_INFOS_API_ENDPOINT = '/v1.0/iot-03/devices/factory-infos';

	private const GET_DEVICE_DETAIL_API_ENDPOINT = '/v1.1/iot-03/devices/%s';

	private const GET_DEVICE_SPECIFICATION_API_ENDPOINT = '/v1.2/iot-03/devices/%s/specification';

	private const GET_DEVICE_STATE_API_ENDPOINT = '/v1.0/iot-03/devices/%s/status';

	private const SET_DEVICE_STATE_API_ENDPOINT = '/v1.0/iot-03/devices/%s/commands';

	private const GET_ACCESS_TOKEN_MESSAGE_SCHEMA_FILENAME = 'openapi_get_access_token.json';

	private const REFRESH_ACCESS_TOKEN_MESSAGE_SCHEMA_FILENAME = 'openapi_refresh_access_token.json';

	private const GET_USER_DEVICES_MESSAGE_SCHEMA_FILENAME = 'openapi_get_user_devices.json';

	private const GET_USER_DEVICE_DETAIL_MESSAGE_SCHEMA_FILENAME = 'openapi_get_user_device_detail.json';

	private const GET_USER_DEVICES_FACTORY_INFOS_MESSAGE_SCHEMA_FILENAME = 'openapi_get_user_devices_factory_infos.json';

	private const GET_USER_DEVICE_SPECIFICATIONS_MESSAGE_SCHEMA_FILENAME = 'openapi_get_user_device_specifications.json';

	private const GET_USER_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'openapi_get_user_device_state.json';

	private const GET_USER_DEVICE_CHILDREN_DEVICES_MESSAGE_SCHEMA_FILENAME = 'openapi_get_user_device_children.json';

	private const GET_DEVICES_MESSAGE_SCHEMA_FILENAME = 'openapi_get_devices.json';

	private const GET_DEVICES_FACTORY_INFOS_MESSAGE_SCHEMA_FILENAME = 'openapi_get_devices_factory_infos.json';

	private const GET_DEVICE_DETAIL_MESSAGE_SCHEMA_FILENAME = 'openapi_get_device_detail.json';

	private const GET_DEVICE_SPECIFICATION_MESSAGE_SCHEMA_FILENAME = 'openapi_get_device_specification.json';

	private const GET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'openapi_get_device_state.json';

	private const SET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'openapi_set_device_state.json';

	private string $devChannel = 'fastybird_iot';

	/** @var array<string, string> */
	private array $validationSchemas = [];

	private Uuid\UuidInterface $nonce;

	private Entities\API\AccessToken|null $tokenInfo = null;

	/** @var Promise\Deferred<bool>|null */
	private Promise\Deferred|null $refreshTokenPromise = null;

	public function __construct(
		private readonly string $identifier,
		private readonly string $accessId,
		private readonly string $accessSecret,
		private readonly string $lang,
		private readonly Helpers\Entity $entityHelper,
		private readonly Types\OpenApiEndpoint $endpoint,
		private readonly Services\HttpClientFactory $httpClientFactory,
		private readonly Tuya\Logger $logger,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly ObjectMapper\Processing\Processor $objectMapper,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
	)
	{
		$this->nonce = Uuid\Uuid::uuid1();
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<bool> : true)
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	public function connect(bool $async = true): Promise\PromiseInterface|bool
	{
		$deferred = new Promise\Deferred();

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			self::GET_ACCESS_TOKEN_API_ENDPOINT,
			[
				'grant_type' => 1,
			],
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$this->tokenInfo = $this->parseGetAccessToken($request, $response)->getResult();

						$deferred->resolve(true);
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		$this->tokenInfo = $this->parseGetAccessToken($request, $result)->getResult();

		return true;
	}

	public function disconnect(): void
	{
		$this->tokenInfo = null;
	}

	public function isConnected(): bool
	{
		return $this->tokenInfo !== null;
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	public function getUid(): string
	{
		if (!$this->isConnected()) {
			$this->connect();
		}

		if ($this->tokenInfo === null) {
			throw new Exceptions\OpenApiError('Access token could not be created');
		}

		return $this->tokenInfo->getUid();
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Entities\API\GetUserDevices> : Entities\API\GetUserDevices)
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	public function getUserDevices(
		string $userId,
		bool $async = true,
	): Promise\PromiseInterface|Entities\API\GetUserDevices
	{
		$deferred = new Promise\Deferred();

		if (!$this->isConnected()) {
			$this->connect();
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_USER_DEVICES_API_ENDPOINT, $userId),
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetUserDevices($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetUserDevices($request, $result);
	}

	/**
	 * @param array<string> $deviceIds
	 *
	 * @return ($async is true ? Promise\PromiseInterface<Entities\API\GetUserDeviceFactoryInfos> : Entities\API\GetUserDeviceFactoryInfos)
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	public function getUserDevicesFactoryInfos(
		array $deviceIds,
		bool $async = true,
	): Promise\PromiseInterface|Entities\API\GetUserDeviceFactoryInfos
	{
		$deferred = new Promise\Deferred();

		if (!$this->isConnected()) {
			$this->connect();
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			self::GET_USER_DEVICES_FACTORY_INFOS_API_ENDPOINT,
			[
				'device_ids' => implode(',', $deviceIds),
			],
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetUserDevicesFactoryInfos($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetUserDevicesFactoryInfos($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Entities\API\GetUserDeviceDetail> : Entities\API\GetUserDeviceDetail)
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	public function getUserDeviceDetail(
		string $deviceId,
		bool $async = true,
	): Promise\PromiseInterface|Entities\API\GetUserDeviceDetail
	{
		$deferred = new Promise\Deferred();

		if (!$this->isConnected()) {
			$this->connect();
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_USER_DEVICE_DETAIL_API_ENDPOINT, $deviceId),
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetUserDeviceDetail($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetUserDeviceDetail($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Entities\API\GetUserDeviceSpecifications> : Entities\API\GetUserDeviceSpecifications)
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	public function getUserDeviceSpecifications(
		string $deviceId,
		bool $async = true,
	): Promise\PromiseInterface|Entities\API\GetUserDeviceSpecifications
	{
		$deferred = new Promise\Deferred();

		if (!$this->isConnected()) {
			$this->connect();
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_USER_DEVICE_SPECIFICATIONS_API_ENDPOINT, $deviceId),
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetUserDeviceSpecifications($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetUserDeviceSpecifications($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Entities\API\GetUserDeviceState> : Entities\API\GetUserDeviceState)
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	public function getUserDeviceState(
		string $deviceId,
		bool $async = true,
	): Promise\PromiseInterface|Entities\API\GetUserDeviceState
	{
		$deferred = new Promise\Deferred();

		if (!$this->isConnected()) {
			$this->connect();
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_USER_DEVICE_STATE_API_ENDPOINT, $deviceId),
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetUserDeviceState($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetUserDeviceState($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Entities\API\GetUserDeviceChildren> : Entities\API\GetUserDeviceChildren)
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	public function getUserDeviceChildren(
		string $deviceId,
		bool $async = true,
	): Promise\PromiseInterface|Entities\API\GetUserDeviceChildren
	{
		$deferred = new Promise\Deferred();

		if (!$this->isConnected()) {
			$this->connect();
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_USER_DEVICE_CHILDREN_DEVICES_API_ENDPOINT, $deviceId),
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetUserDeviceChildren($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetUserDeviceChildren($request, $result);
	}

	/**
	 * @param array<string, mixed> $params
	 *
	 * @return ($async is true ? Promise\PromiseInterface<Entities\API\GetDevices> : Entities\API\GetDevices)
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	public function getDevices(
		array $params = [],
		bool $async = true,
	): Promise\PromiseInterface|Entities\API\GetDevices
	{
		$deferred = new Promise\Deferred();

		if (!$this->isConnected()) {
			$this->connect();
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			self::GET_DEVICES_API_ENDPOINT,
			$params,
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDevices($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDevices($request, $result);
	}

	/**
	 * @param array<string> $deviceIds
	 *
	 * @return ($async is true ? Promise\PromiseInterface<Entities\API\GetDevicesFactoryInfos> : Entities\API\GetDevicesFactoryInfos)
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	public function getDevicesFactoryInfos(
		array $deviceIds,
		bool $async = true,
	): Promise\PromiseInterface|Entities\API\GetDevicesFactoryInfos
	{
		$deferred = new Promise\Deferred();

		if (!$this->isConnected()) {
			$this->connect();
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			self::GET_DEVICES_FACTORY_INFOS_API_ENDPOINT,
			[
				'device_ids' => implode(',', $deviceIds),
			],
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDevicesFactoryInfos($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDevicesFactoryInfos($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Entities\API\GetDevice> : Entities\API\GetDevice)
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	public function getDeviceDetail(
		string $deviceId,
		bool $async = true,
	): Promise\PromiseInterface|Entities\API\GetDevice
	{
		$deferred = new Promise\Deferred();

		if (!$this->isConnected()) {
			$this->connect();
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_DEVICE_DETAIL_API_ENDPOINT, $deviceId),
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDeviceDetail($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceDetail($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Entities\API\GetDeviceSpecification> : Entities\API\GetDeviceSpecification)
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	public function getDeviceSpecification(
		string $deviceId,
		bool $async = true,
	): Promise\PromiseInterface|Entities\API\GetDeviceSpecification
	{
		$deferred = new Promise\Deferred();

		if (!$this->isConnected()) {
			$this->connect();
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_DEVICE_SPECIFICATION_API_ENDPOINT, $deviceId),
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDeviceSpecification($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceSpecification($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Entities\API\GetDeviceState> : Entities\API\GetDeviceState)
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	public function getDeviceState(
		string $deviceId,
		bool $async = true,
	): Promise\PromiseInterface|Entities\API\GetDeviceState
	{
		$deferred = new Promise\Deferred();

		if (!$this->isConnected()) {
			$this->connect();
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			sprintf(self::GET_DEVICE_STATE_API_ENDPOINT, $deviceId),
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseGetDeviceState($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseGetDeviceState($request, $result);
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<bool> : bool)
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	public function setDeviceState(
		string $deviceId,
		string $code,
		string|int|float|bool $value,
		bool $async = true,
	): Promise\PromiseInterface|bool
	{
		$deferred = new Promise\Deferred();

		if (!$this->isConnected()) {
			$this->connect();
		}

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
				null,
				null,
				$ex->getCode(),
				$ex,
			));
		}

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_POST,
			sprintf(self::SET_DEVICE_STATE_API_ENDPOINT, $deviceId),
			[],
			$body,
		);

		$result = $this->callRequest($request, $async);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred, $request): void {
					try {
						$deferred->resolve($this->parseSetDeviceState($request, $response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		return $this->parseSetDeviceState($request, $result);
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function parseGetAccessToken(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\GetAccessToken
	{
		$body = $this->validateResponseBody($request, $response, self::GET_ACCESS_TOKEN_MESSAGE_SCHEMA_FILENAME);

		$result = $body->offsetGet('result');

		if (!$result instanceof Utils\ArrayHash) {
			throw new Exceptions\OpenApiCall('Received response is not valid', $request, $response);
		}

		$result->offsetSet(
			'expire_time',
			intval($body->offsetGet('t')) + ($result->offsetExists('expire') ? $result->offsetGet(
				'expire',
			) : $result->offsetGet(
				'expire_time',
			)) * 1_000,
		);

		$body->offsetSet('result', $result);

		return $this->createEntity(Entities\API\GetAccessToken::class, $body);
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function parseRefreshAccessToken(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\RefreshAccessToken
	{
		$body = $this->validateResponseBody($request, $response, self::REFRESH_ACCESS_TOKEN_MESSAGE_SCHEMA_FILENAME);

		$result = $body->offsetGet('result');

		if (!$result instanceof Utils\ArrayHash) {
			throw new Exceptions\OpenApiCall('Received response is not valid', $request, $response);
		}

		$result->offsetSet(
			'expire_time',
			intval($body->offsetGet('t')) + ($result->offsetExists('expire') ? $result->offsetGet(
				'expire',
			) : $result->offsetGet(
				'expire_time',
			)) * 1_000,
		);

		$body->offsetSet('result', $result);

		return $this->createEntity(Entities\API\RefreshAccessToken::class, $body);
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function parseGetUserDevices(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\GetUserDevices
	{
		$body = $this->validateResponseBody($request, $response, self::GET_USER_DEVICES_MESSAGE_SCHEMA_FILENAME);

		return $this->createEntity(Entities\API\GetUserDevices::class, $body);
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function parseGetUserDevicesFactoryInfos(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\GetUserDeviceFactoryInfos
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_USER_DEVICES_FACTORY_INFOS_MESSAGE_SCHEMA_FILENAME,
		);

		return $this->createEntity(Entities\API\GetUserDeviceFactoryInfos::class, $body);
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function parseGetUserDeviceDetail(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\GetUserDeviceDetail
	{
		$body = $this->validateResponseBody($request, $response, self::GET_USER_DEVICE_DETAIL_MESSAGE_SCHEMA_FILENAME);

		return $this->createEntity(Entities\API\GetUserDeviceDetail::class, $body);
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function parseGetUserDeviceSpecifications(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\GetUserDeviceSpecifications
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_USER_DEVICE_SPECIFICATIONS_MESSAGE_SCHEMA_FILENAME,
		);

		return $this->createEntity(Entities\API\GetUserDeviceSpecifications::class, $body);
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function parseGetUserDeviceState(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\GetUserDeviceState
	{
		$body = $this->validateResponseBody($request, $response, self::GET_USER_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME);

		return $this->createEntity(Entities\API\GetUserDeviceState::class, $body);
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function parseGetUserDeviceChildren(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\GetUserDeviceChildren
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_USER_DEVICE_CHILDREN_DEVICES_MESSAGE_SCHEMA_FILENAME,
		);

		return $this->createEntity(Entities\API\GetUserDeviceChildren::class, $body);
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function parseGetDevices(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\GetDevices
	{
		$body = $this->validateResponseBody($request, $response, self::GET_DEVICES_MESSAGE_SCHEMA_FILENAME);

		return $this->createEntity(Entities\API\GetDevices::class, $body);
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function parseGetDevicesFactoryInfos(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\GetDevicesFactoryInfos
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_DEVICES_FACTORY_INFOS_MESSAGE_SCHEMA_FILENAME,
		);

		return $this->createEntity(Entities\API\GetDevicesFactoryInfos::class, $body);
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function parseGetDeviceDetail(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\GetDevice
	{
		$body = $this->validateResponseBody($request, $response, self::GET_DEVICE_DETAIL_MESSAGE_SCHEMA_FILENAME);

		return $this->createEntity(Entities\API\GetDevice::class, $body);
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function parseGetDeviceSpecification(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\GetDeviceSpecification
	{
		$body = $this->validateResponseBody(
			$request,
			$response,
			self::GET_DEVICE_SPECIFICATION_MESSAGE_SCHEMA_FILENAME,
		);

		return $this->createEntity(Entities\API\GetDeviceSpecification::class, $body);
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function parseGetDeviceState(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): Entities\API\GetDeviceState
	{
		$body = $this->validateResponseBody($request, $response, self::GET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME);

		return $this->createEntity(Entities\API\GetDeviceState::class, $body);
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function parseSetDeviceState(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): bool
	{
		$body = $this->validateResponseBody($request, $response, self::SET_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME);

		return boolval($body->offsetGet('result'));
	}

	/**
	 * @template T of Entities\API\Entity
	 *
	 * @param class-string<T> $entity
	 *
	 * @return T
	 *
	 * @throws Exceptions\OpenApiCall
	 */
	private function createEntity(string $entity, Utils\ArrayHash $data): Entities\API\Entity
	{
		try {
			return $this->entityHelper->create(
				$entity,
				(array) Utils\Json::decode(Utils\Json::encode($data), Utils\Json::FORCE_ARRAY),
			);
		} catch (Exceptions\Runtime $ex) {
			throw new Exceptions\OpenApiCall('Could not map data to entity', null, null, $ex->getCode(), $ex);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\OpenApiCall(
				'Could not create entity from response',
				null,
				null,
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<Message\ResponseInterface> : Message\ResponseInterface)
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function callRequest(
		Request $request,
		bool $async = true,
	): Promise\PromiseInterface|Message\ResponseInterface
	{
		$deferred = new Promise\Deferred();

		$refreshTokenResult = $this->refreshAccessToken($request);

		if ($refreshTokenResult instanceof Promise\PromiseInterface) {
			try {
				await($refreshTokenResult);
			} catch (Throwable $ex) {
				return Promise\reject(
					new Exceptions\OpenApiCall(
						'Awaiting for refresh token promise failed',
						$request,
						null,
						$ex->getCode(),
						$ex,
					),
				);
			}
		}

		$this->logger->debug(sprintf(
			'Request: method = %s url = %s',
			$request->getMethod(),
			strval($request->getUri()),
		), [
			'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
			'type' => 'openapi-api',
			'request' => [
				'method' => $request->getMethod(),
				'path' => strval($request->getUri()),
				'headers' => $request->getHeaders(),
				'body' => $request->getContent(),
			],
			'connector' => [
				'identifier' => $this->identifier,
			],
		]);

		if ($async) {
			try {
				$this->httpClientFactory
					->create()
					->send($request)
					->then(
						function (Message\ResponseInterface $response) use ($deferred, $request): void {
							try {
								$responseBody = $response->getBody()->getContents();

								$response->getBody()->rewind();
							} catch (RuntimeException $ex) {
								$deferred->reject(
									new Exceptions\OpenApiCall(
										'Could not get content from response body',
										$request,
										$response,
										$ex->getCode(),
										$ex,
									),
								);

								return;
							}

							$this->logger->debug('Received response', [
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
								'type' => 'openapi-api',
								'request' => [
									'method' => $request->getMethod(),
									'url' => strval($request->getUri()),
									'headers' => $request->getHeaders(),
									'body' => $request->getContent(),
								],
								'response' => [
									'code' => $response->getStatusCode(),
									'body' => $responseBody,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							]);

							$this->checkResponse($request, $response);

							$deferred->resolve($response);
						},
						static function (Throwable $ex) use ($deferred, $request): void {
							$deferred->reject(
								new Exceptions\OpenApiCall(
									'Calling api endpoint failed',
									$request,
									null,
									$ex->getCode(),
									$ex,
								),
							);
						},
					);
			} catch (Throwable $ex) {
				$deferred->reject($ex);
			}

			return $deferred->promise();
		}

		try {
			$response = $this->httpClientFactory
				->create(false)
				->send($request);

			try {
				$responseBody = $response->getBody()->getContents();

				$response->getBody()->rewind();
			} catch (RuntimeException $ex) {
				throw new Exceptions\OpenApiCall(
					'Could not get content from response body',
					$request,
					$response,
					$ex->getCode(),
					$ex,
				);
			}

			$this->logger->debug('Received response', [
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'openapi-api',
				'request' => [
					'method' => $request->getMethod(),
					'url' => strval($request->getUri()),
					'headers' => $request->getHeaders(),
					'body' => $request->getContent(),
				],
				'response' => [
					'code' => $response->getStatusCode(),
					'body' => $responseBody,
				],
				'connector' => [
					'identifier' => $this->identifier,
				],
			]);

			$this->checkResponse($request, $response);

			return $response;
		} catch (GuzzleHttp\Exception\GuzzleException | InvalidArgumentException $ex) {
			throw new Exceptions\OpenApiCall(
				'Calling api endpoint failed',
				$request,
				null,
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @param array<string, mixed> $params
	 *
	 * @throws Exceptions\OpenApiError
	 */
	private function createRequest(
		string $method,
		string $path,
		array $params = [],
		string|null $body = null,
	): Request
	{
		$url = $this->endpoint->getValue() . $path;

		if (count($params) > 0) {
			$url .= '?';
			$url .= http_build_query($params);
		}

		try {
			$headers = $this->buildRequestHeaders($method, $path, $params, $body);

			return new Request($method, $url, $headers, $body);
		} catch (Exceptions\InvalidArgument | Exceptions\Runtime $ex) {
			throw new Exceptions\OpenApiError('Could not create request instance', $ex->getCode(), $ex);
		}
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function checkResponse(Request $request, Message\ResponseInterface $response): bool
	{
		try {
			$decodedResponse = Utils\Json::decode($response->getBody()->getContents(), Utils\Json::FORCE_ARRAY);

			$response->getBody()->rewind();

		} catch (Utils\JsonException $ex) {
			throw new Exceptions\OpenApiCall(
				'Received response body is not valid JSON',
				$request,
				$response,
				$ex->getCode(),
				$ex,
			);
		} catch (RuntimeException $ex) {
			throw new Exceptions\OpenApiCall(
				'Could not get content from response body',
				$request,
				$response,
				$ex->getCode(),
				$ex,
			);
		}

		if (!is_array($decodedResponse)) {
			throw new Exceptions\OpenApiCall('Received response body is not valid JSON', $request, $response);
		}

		$data = Utils\ArrayHash::from($decodedResponse);

		if (
			$data->offsetExists('code')
			&& $data->offsetGet('code') === self::TUYA_ERROR_CODE_TOKEN_INVALID
		) {
			$this->tokenInfo = null;

			if (!Utils\Strings::endsWith(strval($request->getUri()), self::GET_ACCESS_TOKEN_API_ENDPOINT)) {
				$this->connect();

			} else {
				throw new Exceptions\OpenApiCall(
					'API token is not valid and can not be refreshed',
					$request,
					$response,
				);
			}
		}

		if (
			$data->offsetExists('success')
			&& boolval($data->offsetGet('success')) !== true
		) {
			if ($data->offsetExists('msg')) {
				throw new Exceptions\OpenApiCall(strval($data->offsetGet('msg')), $request, $response);
			}

			throw new Exceptions\OpenApiCall('Received response is not success', $request, $response);
		}

		return true;
	}

	/**
	 * @return ($throw is true ? Utils\ArrayHash : Utils\ArrayHash|false)
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function validateResponseBody(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
		string $schemaFilename,
		bool $throw = true,
	): Utils\ArrayHash|bool
	{
		$body = $this->getResponseBody($request, $response);

		try {
			return $this->schemaValidator->validate(
				$body,
				$this->getSchema($schemaFilename),
			);
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			if ($throw) {
				throw new Exceptions\OpenApiCall(
					'Could not validate received response payload',
					$request,
					$response,
					$ex->getCode(),
					$ex,
				);
			}

			return false;
		}
	}

	/**
	 * @throws Exceptions\OpenApiCall
	 */
	private function getResponseBody(
		Message\RequestInterface $request,
		Message\ResponseInterface $response,
	): string
	{
		try {
			$response->getBody()->rewind();

			return $response->getBody()->getContents();
		} catch (RuntimeException $ex) {
			throw new Exceptions\OpenApiCall(
				'Could not get content from response body',
				$request,
				$response,
				$ex->getCode(),
				$ex,
			);
		}
	}

	/**
	 * @return Promise\PromiseInterface<bool>|false
	 *
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 */
	private function refreshAccessToken(Request $request): Promise\PromiseInterface|false
	{
		if (Utils\Strings::contains(strval($request->getUri()), self::GET_ACCESS_TOKEN_API_ENDPOINT)) {
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

		$path = sprintf(self::REFRESH_ACCESS_TOKEN_API_ENDPOINT, $this->tokenInfo->getRefreshToken());

		$request = $this->createRequest(
			RequestMethodInterface::METHOD_GET,
			$path,
		);

		try {
			$this->logger->debug(sprintf(
				'Request: method = %s url = %s',
				$request->getMethod(),
				strval($request->getUri()),
			), [
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'openapi-api',
				'request' => [
					'method' => $request->getMethod(),
					'path' => strval($request->getUri()),
					'headers' => $request->getHeaders(),
				],
				'connector' => [
					'identifier' => $this->identifier,
				],
			]);

			$response = $this->httpClientFactory->create(false)->send($request);

			try {
				$responseBody = $response->getBody()->getContents();
			} catch (RuntimeException $ex) {
				throw new Exceptions\OpenApiCall(
					'Could not get content from response body',
					$request,
					$response,
					$ex->getCode(),
					$ex,
				);
			}

			$this->logger->debug('Received response', [
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_TUYA,
				'type' => 'openapi-api',
				'request' => [
					'method' => $request->getMethod(),
					'path' => strval($request->getUri()),
					'headers' => $request->getHeaders(),
				],
				'response' => [
					'code' => $response->getStatusCode(),
					'body' => $responseBody,
				],
				'connector' => [
					'identifier' => $this->identifier,
				],
			]);

			try {
				$decodedResponse = Utils\Json::decode($responseBody, Utils\Json::FORCE_ARRAY);

			} catch (Utils\JsonException $ex) {
				$error = new Exceptions\OpenApiCall(
					'Received response body is not valid JSON',
					$request,
					$response,
					$ex->getCode(),
					$ex,
				);

				$this->refreshTokenPromise->reject($error);
				$this->refreshTokenPromise = null;

				return Promise\reject($error);
			}

			if (!is_array($decodedResponse)) {
				$error = new Exceptions\OpenApiCall('Received response body is not valid JSON', $request, $response);

				$this->refreshTokenPromise->reject($error);
				$this->refreshTokenPromise = null;

				return Promise\reject($error);
			}

			$data = Utils\ArrayHash::from($decodedResponse);

			if (
				$data->offsetExists('success')
				&& boolval($data->offsetGet('success')) !== true
			) {
				// TUYA api has something wrong and refreshing token is not allowed
				// According to response, /v1.0/token/{refresh_token} is not allowed to access
				// Workaround is to reconnect to obtain new tokens pair
				if (
					$data->offsetExists('code')
					&& intval($data->offsetGet('code')) === self::TUYA_ERROR_CODE_API_ACCESS_NOT_ALLOWED
				) {
					$this->tokenInfo = null;

					$this->connect();

					$this->refreshTokenPromise->resolve(true);
					$this->refreshTokenPromise = null;

					return Promise\resolve(true);
				} else {
					if ($data->offsetExists('msg')) {
						$error = new Exceptions\OpenApiCall(strval($data->offsetGet('msg')), $request, $response);

						$this->refreshTokenPromise->reject($error);
						$this->refreshTokenPromise = null;

						return Promise\reject($error);
					}

					$error = new Exceptions\OpenApiCall('Received response is not success', $request, $response);

					$this->refreshTokenPromise->reject($error);
					$this->refreshTokenPromise = null;

					return Promise\reject($error);
				}
			}

			try {
				$this->tokenInfo = $this->parseRefreshAccessToken($request, $response)->getResult();
			} catch (Exceptions\OpenApiCall $ex) {
				$this->refreshTokenPromise->reject($ex);
				$this->refreshTokenPromise = null;

				return Promise\reject($ex);
			}

			$this->refreshTokenPromise->resolve(true);
			$this->refreshTokenPromise = null;

			return Promise\resolve(true);
		} catch (GuzzleHttp\Exception\GuzzleException | InvalidArgumentException $ex) {
			$error = new Exceptions\OpenApiCall('Could not refresh access token', $request, null, $ex->getCode(), $ex);

			$this->refreshTokenPromise->reject($error);
			$this->refreshTokenPromise = null;

			return Promise\reject($error);
		}
	}

	/**
	 * @param array<string, mixed> $params
	 *
	 * @return array<string, string|int>
	 *
	 * @throws Exceptions\OpenApiError
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
			Utils\Strings::startsWith($path, self::GET_ACCESS_TOKEN_API_ENDPOINT) ? '' : $accessToken,
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
			'access_token' => Utils\Strings::startsWith($path, self::GET_ACCESS_TOKEN_API_ENDPOINT) ? '' : $accessToken,
			't' => $sign->getTimestamp(),
			'lang' => $this->lang,
			'dev_lang' => 'php',
			'dev_version' => self::VERSION,
			'dev_channel' => 'cloud_' . $this->devChannel,
		];
	}

	/**
	 * @param array<string, mixed> $params
	 *
	 * @throws Exceptions\OpenApiError
	 */
	private function calculateSign(
		string $accessToken,
		string $method,
		string $path,
		array $params = [],
		string|null $body = null,
	): ValueObjects\OpenApiRequestSign
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

		try {
			return $this->objectMapper->process(
				[
					'sign' => $sign,
					'timestamp' => $timestamp,
				],
				ValueObjects\OpenApiRequestSign::class,
			);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\OpenApiError('Request sign could not be created: ' . $errorPrinter->printError($ex));
		}
	}

	/**
	 * @throws Exceptions\OpenApiError
	 */
	private function getSchema(string $schemaFilename): string
	{
		$key = md5($schemaFilename);

		if (!array_key_exists($key, $this->validationSchemas)) {
			try {
				$this->validationSchemas[$key] = Utils\FileSystem::read(
					Tuya\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
				);

			} catch (Nette\IOException) {
				throw new Exceptions\OpenApiError('Validation schema for response could not be loaded');
			}
		}

		return $this->validationSchemas[$key];
	}

}
