<?php declare(strict_types = 1);

/**
 * LanApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           10.07.23
 */

namespace FastyBird\Connector\NsPanel\API;

use Evenement;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
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
use function assert;
use function count;
use function http_build_query;
use function sprintf;
use function strval;
use const DIRECTORY_SEPARATOR;

/**
 * NS Panel LAN API interface
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class LanApi
{

	use Nette\SmartObject;
	use Evenement\EventEmitterTrait;

	public const GATEWAY_PORT = 8_081;

	public const API_VERSION = '1';

	private const GET_GATEWAY_INFO_MESSAGE_SCHEMA_FILENAME = 'get_gateway_info.json';

	private const GET_GATEWAY_ACCESS_TOKEN_MESSAGE_SCHEMA_FILENAME = 'get_gateway_access_token.json';

	private const SYNCHRONISE_DEVICES_MESSAGE_SCHEMA_FILENAME = 'synchronise_devices.json';

	private const REPORT_DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME = 'report_device_status.json';

	private const REPORT_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME = 'report_device_state.json';

	private const GET_SUB_DEVICES_MESSAGE_SCHEMA_FILENAME = 'get_sub_devices.json';

	private const SET_SUB_DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME = 'set_sub_device_status.json';

	private const EVENT_ERROR_MESSAGE_SCHEMA_FILENAME = 'event_error.json';

	public function __construct(
		private readonly string $identifier,
		private readonly HttpClientFactory $httpClientFactory,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly ObjectMapper\Processing\Processor $entityMapper,
		private readonly NsPanel\Logger $logger,
	)
	{
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Response\GetGatewayInfo)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	public function getGatewayInfo(
		string $ipAddress,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Response\GetGatewayInfo
	{
		$deferred = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf('http://%s:%d/open-api/v1/rest/bridge', $ipAddress, $port),
			[
				'Content-Type' => 'application/json',
			],
			[],
			null,
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$deferred->resolve($this->parseGetGatewayInfo($response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\LanApiCall('Could send data to cloud server');
		}

		return $this->parseGetGatewayInfo($result);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Response\GetGatewayAccessToken)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	public function getGatewayAccessToken(
		string $name,
		string $ipAddress,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Response\GetGatewayAccessToken
	{
		$deferred = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf('http://%s:%d/open-api/v1/rest/bridge/access_token', $ipAddress, $port),
			[
				'Content-Type' => 'application/json',
			],
			[
				'app_name' => $name,
			],
			null,
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$deferred->resolve($this->parseGetGatewayAccessToken($response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\LanApiCall('Could send data to cloud server');
		}

		return $this->parseGetGatewayAccessToken($result);
	}

	/**
	 * @param array<mixed> $devices
	 *
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Response\SyncDevices)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	public function synchroniseDevices(
		array $devices,
		string $ipAddress,
		string $accessToken,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Response\SyncDevices
	{
		$deferred = new Promise\Deferred();

		$entity = $this->createEntity(
			Entities\API\Request\SyncDevices::class,
			Utils\ArrayHash::from([
				'event' => [
					'header' => [
						'name' => NsPanel\Types\Header::DISCOVERY_REQUEST,
						'message_id' => Uuid\Uuid::uuid4()->toString(),
						'version' => self::API_VERSION,
					],
					'payload' => [
						'endpoints' => $devices,
					],
				],
			]),
		);

		try {
			$result = $this->callRequest(
				'POST',
				sprintf('http://%s:%d/open-api/v1/rest/thirdparty/event', $ipAddress, $port),
				[
					'Content-Type' => 'application/json',
					'Authorization' => sprintf('Bearer %s', $accessToken),
				],
				[],
				Utils\Json::encode($entity->toJson()),
				$async,
			);
		} catch (Utils\JsonException $ex) {
			$this->logger->error(
				'Could not encode request content',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'lan-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'body' => $entity->toArray(),
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			if ($async) {
				return Promise\reject(new Exceptions\LanApiCall('Could not prepare request'));
			}

			throw new Exceptions\LanApiCall('Could not prepare request');
		}

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$deferred->resolve($this->parseSynchroniseDevices($response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\LanApiCall('Could send data to cloud server');
		}

		return $this->parseSynchroniseDevices($result);
	}

	/**
	 * @param array<mixed> $status
	 *
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Response\ReportDeviceStatus)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	public function reportDeviceStatus(
		string $serialNumber,
		array $status,
		string $ipAddress,
		string $accessToken,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Response\ReportDeviceStatus
	{
		$deferred = new Promise\Deferred();

		$entity = $this->createEntity(
			Entities\API\Request\ReportDeviceStatus::class,
			Utils\ArrayHash::from([
				'event' => [
					'header' => [
						'name' => NsPanel\Types\Header::DEVICE_STATES_CHANGE_REPORT,
						'message_id' => Uuid\Uuid::uuid4()->toString(),
						'version' => self::API_VERSION,
					],
					'endpoint' => [
						'serial_number' => $serialNumber,
					],
					'payload' => [
						'state' => $status,
					],
				],
			]),
		);

		try {
			$result = $this->callRequest(
				'POST',
				sprintf('http://%s:%d/open-api/v1/rest/thirdparty/event', $ipAddress, $port),
				[
					'Content-Type' => 'application/json',
					'Authorization' => sprintf('Bearer %s', $accessToken),
				],
				[],
				Utils\Json::encode($entity->toJson()),
				$async,
			);
		} catch (Utils\JsonException $ex) {
			$this->logger->error(
				'Could not encode request content',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'lan-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'body' => $entity->toArray(),
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			if ($async) {
				return Promise\reject(new Exceptions\LanApiCall('Could not prepare request'));
			}

			throw new Exceptions\LanApiCall('Could not prepare request');
		}

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$deferred->resolve($this->parseReportDeviceStatus($response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\LanApiCall('Could send data to cloud server');
		}

		return $this->parseReportDeviceStatus($result);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Response\ReportDeviceState)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	public function reportDeviceState(
		string $serialNumber,
		bool $online,
		string $ipAddress,
		string $accessToken,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Response\ReportDeviceState
	{
		$deferred = new Promise\Deferred();

		$entity = $this->createEntity(
			Entities\API\Request\ReportDeviceState::class,
			Utils\ArrayHash::from([
				'event' => [
					'header' => [
						'name' => NsPanel\Types\Header::DEVICE_ONLINE_CHANGE_REPORT,
						'message_id' => Uuid\Uuid::uuid4()->toString(),
						'version' => self::API_VERSION,
					],
					'endpoint' => [
						'serial_number' => $serialNumber,
					],
					'payload' => [
						'online' => $online,
					],
				],
			]),
		);

		try {
			$result = $this->callRequest(
				'POST',
				sprintf('http://%s:%d/open-api/v1/rest/thirdparty/event', $ipAddress, $port),
				[
					'Content-Type' => 'application/json',
					'Authorization' => sprintf('Bearer %s', $accessToken),
				],
				[],
				Utils\Json::encode($entity->toJson()),
				$async,
			);
		} catch (Utils\JsonException $ex) {
			$this->logger->error(
				'Could not encode request content',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'lan-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'body' => $entity->toArray(),
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			if ($async) {
				return Promise\reject(new Exceptions\LanApiCall('Could not prepare request'));
			}

			throw new Exceptions\LanApiCall('Could not prepare request');
		}

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$deferred->resolve($this->parseReportDeviceState($response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\LanApiCall('Could send data to cloud server');
		}

		return $this->parseReportDeviceState($result);
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : bool)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	public function removeDevice(
		string $serialNumber,
		string $ipAddress,
		string $accessToken,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|bool
	{
		$deferred = new Promise\Deferred();

		$result = $this->callRequest(
			'DELETE',
			sprintf('http://%s:%d/open-api/v1/rest/devices/%s', $ipAddress, $port, $serialNumber),
			[
				'Content-Type' => 'application/json',
				'Authorization' => sprintf('Bearer %s', $accessToken),
			],
			[],
			null,
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(static function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$deferred->resolve(true);
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\LanApiCall('Could send data to cloud server');
		}

		return true;
	}

	/**
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Response\GetSubDevices)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	public function getSubDevices(
		string $ipAddress,
		string $accessToken,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Response\GetSubDevices
	{
		$deferred = new Promise\Deferred();

		$result = $this->callRequest(
			'GET',
			sprintf('http://%s:%d/open-api/v1/rest/devices', $ipAddress, $port),
			[
				'Content-Type' => 'application/json',
				'Authorization' => sprintf('Bearer %s', $accessToken),
			],
			[],
			null,
			$async,
		);

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$deferred->resolve($this->parseGetSubDevices($response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\LanApiCall('Could send data to cloud server');
		}

		return $this->parseGetSubDevices($result);
	}

	/**
	 * @param array<mixed> $status
	 *
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Entities\API\Response\SetSubDeviceStatus)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	public function setSubDeviceStatus(
		string $serialNumber,
		array $status,
		string $ipAddress,
		string $accessToken,
		int $port = self::GATEWAY_PORT,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Entities\API\Response\SetSubDeviceStatus
	{
		$deferred = new Promise\Deferred();

		$entity = $this->createEntity(
			Entities\API\Request\SetSubDeviceStatus::class,
			Utils\ArrayHash::from([
				'state' => $status,
			]),
		);

		try {
			$result = $this->callRequest(
				'PUT',
				sprintf('http://%s:%d/open-api/v1/rest/devices/%s', $ipAddress, $port, $serialNumber),
				[
					'Content-Type' => 'application/json',
					'Authorization' => sprintf('Bearer %s', $accessToken),
				],
				[],
				Utils\Json::encode($entity->toJson()),
				$async,
			);
		} catch (Utils\JsonException $ex) {
			$this->logger->error(
				'Could not encode request content',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'lan-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'body' => $entity->toArray(),
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			if ($async) {
				return Promise\reject(new Exceptions\LanApiCall('Could not prepare request'));
			}

			throw new Exceptions\LanApiCall('Could not prepare request');
		}

		if ($result instanceof Promise\PromiseInterface) {
			$result
				->then(function (Message\ResponseInterface $response) use ($deferred): void {
					try {
						$deferred->resolve($this->parseSetSubDeviceStatus($response));
					} catch (Throwable $ex) {
						$deferred->reject($ex);
					}
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});

			return $deferred->promise();
		}

		if ($result === false) {
			throw new Exceptions\LanApiCall('Could send data to cloud server');
		}

		return $this->parseSetSubDeviceStatus($result);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 */
	private function parseGetGatewayInfo(Message\ResponseInterface $response): Entities\API\Response\GetGatewayInfo
	{
		$body = $this->validateResponseBody($response, self::GET_GATEWAY_INFO_MESSAGE_SCHEMA_FILENAME);

		$error = $body->offsetGet('error');

		$data = $body->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		if ($error !== 0) {
			$this->logger->error(
				'Read NS Panel status failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'lan-api',
					'error' => $body->offsetGet('message'),
					'response' => [
						'headers' => $response->getHeaders(),
						'body' => $this->getResponseBody($response),
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\LanApiCall(
				sprintf('Getting gateway status failed: %s', strval($body->offsetGet('message'))),
			);
		}

		return $this->createEntity(Entities\API\Response\GetGatewayInfo::class, $body);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 */
	private function parseGetGatewayAccessToken(
		Message\ResponseInterface $response,
	): Entities\API\Response\GetGatewayAccessToken
	{
		$body = $this->validateResponseBody($response, self::GET_GATEWAY_ACCESS_TOKEN_MESSAGE_SCHEMA_FILENAME);

		$error = $body->offsetGet('error');

		$data = $body->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		if ($error !== 0) {
			$this->logger->error(
				'Read NS Panel access token failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'lan-api',
					'error' => $body->offsetGet('message'),
					'response' => [
						'headers' => $response->getHeaders(),
						'body' => $this->getResponseBody($response),
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\LanApiCall(
				sprintf('Getting gateway access token failed: %s', strval($body->offsetGet('message'))),
			);
		}

		return $this->createEntity(Entities\API\Response\GetGatewayAccessToken::class, $body);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 */
	private function parseSynchroniseDevices(Message\ResponseInterface $response): Entities\API\Response\SyncDevices
	{
		$errorBody = $this->validateResponseBody($response, self::EVENT_ERROR_MESSAGE_SCHEMA_FILENAME, false);

		if ($errorBody !== false) {
			$error = $this->createEntity(Entities\API\Response\ErrorEvent::class, $errorBody);

			$this->logger->error(
				'Read NS Panel access token failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'lan-api',
					'error' => $error->getPayload()->getDescription(),
					'response' => [
						'headers' => $response->getHeaders(),
						'body' => $this->getResponseBody($response),
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\LanApiCall(
				sprintf('Getting gateway access token failed: %s', $error->getPayload()->getDescription()),
			);
		}

		return $this->createEntity(
			Entities\API\Response\SyncDevices::class,
			$this->validateResponseBody($response, self::SYNCHRONISE_DEVICES_MESSAGE_SCHEMA_FILENAME),
		);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 */
	private function parseReportDeviceStatus(
		Message\ResponseInterface $response,
	): Entities\API\Response\ReportDeviceStatus
	{
		$errorBody = $this->validateResponseBody($response, self::EVENT_ERROR_MESSAGE_SCHEMA_FILENAME, false);

		if ($errorBody !== false) {
			$error = $this->createEntity(Entities\API\Response\ErrorEvent::class, $errorBody);

			$this->logger->error(
				'Read NS Panel access token failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'lan-api',
					'error' => $error->getPayload()->getDescription(),
					'response' => [
						'headers' => $response->getHeaders(),
						'body' => $this->getResponseBody($response),
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\LanApiCall(
				sprintf('Getting gateway access token failed: %s', $error->getPayload()->getDescription()),
			);
		}

		return $this->createEntity(
			Entities\API\Response\ReportDeviceStatus::class,
			$this->validateResponseBody($response, self::REPORT_DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME),
		);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 */
	private function parseReportDeviceState(
		Message\ResponseInterface $response,
	): Entities\API\Response\ReportDeviceState
	{
		$errorBody = $this->validateResponseBody($response, self::EVENT_ERROR_MESSAGE_SCHEMA_FILENAME, false);

		if ($errorBody !== false) {
			$error = $this->createEntity(Entities\API\Response\ErrorEvent::class, $errorBody);

			$this->logger->error(
				'Read NS Panel access token failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'lan-api',
					'error' => $error->getPayload()->getDescription(),
					'response' => [
						'headers' => $response->getHeaders(),
						'body' => $this->getResponseBody($response),
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\LanApiCall(
				sprintf('Getting gateway access token failed: %s', $error->getPayload()->getDescription()),
			);
		}

		return $this->createEntity(
			Entities\API\Response\ReportDeviceState::class,
			$this->validateResponseBody($response, self::REPORT_DEVICE_STATE_MESSAGE_SCHEMA_FILENAME),
		);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 */
	private function parseGetSubDevices(
		Message\ResponseInterface $response,
	): Entities\API\Response\GetSubDevices
	{
		$body = $this->validateResponseBody($response, self::GET_SUB_DEVICES_MESSAGE_SCHEMA_FILENAME);

		$error = $body->offsetGet('error');

		$data = $body->offsetGet('data');
		assert($data instanceof Utils\ArrayHash);

		if ($error !== 0) {
			$this->logger->error(
				'Get sub-devices list from NS Panel failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'lan-api',
					'error' => $body->offsetGet('message'),
					'response' => [
						'headers' => $response->getHeaders(),
						'body' => $this->getResponseBody($response),
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\LanApiCall(
				sprintf('Get sub-devices list failed: %s', strval($body->offsetGet('message'))),
			);
		}

		return $this->createEntity(Entities\API\Response\GetSubDevices::class, $body);
	}

	/**
	 * @throws Exceptions\LanApiCall
	 */
	private function parseSetSubDeviceStatus(
		Message\ResponseInterface $response,
	): Entities\API\Response\SetSubDeviceStatus
	{
		$body = $this->validateResponseBody($response, self::SET_SUB_DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME);

		$error = $body->offsetGet('error');

		if ($error !== 0) {
			$this->logger->error(
				'Send sub-device set status to NS Panel failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'lan-api',
					'error' => $body->offsetGet('message'),
					'response' => [
						'headers' => $response->getHeaders(),
						'body' => $this->getResponseBody($response),
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				],
			);

			throw new Exceptions\LanApiCall(
				sprintf('Set sub-device status failed: %s', strval($body->offsetGet('message'))),
			);
		}

		return $this->createEntity(Entities\API\Response\SetSubDeviceStatus::class, $body);
	}

	/**
	 * @return ($throw is true ? Utils\ArrayHash : Utils\ArrayHash|false)
	 *
	 * @throws Exceptions\LanApiCall
	 */
	private function validateResponseBody(
		Message\ResponseInterface $response,
		string $schemaFilename,
		bool $throw = true,
	): Utils\ArrayHash|bool
	{
		$body = $this->getResponseBody($response);

		try {
			return $this->schemaValidator->validate(
				$body,
				$this->getSchema($schemaFilename),
			);
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			if ($throw) {
				$this->logger->error(
					'Could not decode received response payload',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
						'type' => 'lan-api',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'response' => [
							'headers' => $response->getHeaders(),
							'body' => $body,
							'schema' => $schemaFilename,
						],
						'connector' => [
							'identifier' => $this->identifier,
						],
					],
				);

				throw new Exceptions\LanApiCall('Could not validate received response payload');
			}

			return false;
		}
	}

	/**
	 * @throws Exceptions\LanApiCall
	 */
	private function getResponseBody(
		Message\ResponseInterface $response,
	): string
	{
		try {
			$response->getBody()->rewind();

			return $response->getBody()->getContents();
		} catch (RuntimeException $ex) {
			throw new Exceptions\LanApiCall('Could not get content from response body', $ex->getCode(), $ex);
		}
	}

	/**
	 * @template T of Entities\API\Entity
	 *
	 * @param class-string<T> $entity
	 *
	 * @return T
	 *
	 * @throws Exceptions\LanApiCall
	 */
	private function createEntity(string $entity, Utils\ArrayHash $data): Entities\API\Entity
	{
		try {
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			return $this->entityMapper->process(
				Utils\Json::decode(Utils\Json::encode($data), Utils\Json::FORCE_ARRAY),
				$entity,
				$options,
			);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\LanApiCall('Could not map data to entity: ' . $errorPrinter->printError($ex));
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\LanApiCall('Could not create entity from response', $ex->getCode(), $ex);
		}
	}

	/**
	 * @param array<string, mixed> $headers
	 * @param array<string, mixed> $params
	 *
	 * @return ($async is true ? Promise\ExtendedPromiseInterface|Promise\PromiseInterface : Message\ResponseInterface|false)
	 */
	private function callRequest(
		string $method,
		string $url,
		array $headers = [],
		array $params = [],
		string|null $body = null,
		bool $async = true,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface|Message\ResponseInterface|false
	{
		$deferred = new Promise\Deferred();

		$this->logger->debug(sprintf(
			'Request: method = %s url = %s',
			$method,
			$url,
		), [
			'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
			'type' => 'lan-api',
			'request' => [
				'method' => $method,
				'url' => $url,
				'headers' => $headers,
				'params' => $params,
				'body' => $body,
			],
			'connector' => [
				'identifier' => $this->identifier,
			],
		]);

		if (count($params) > 0) {
			$url .= '?';
			$url .= http_build_query($params);
		}

		if ($async) {
			try {
				$request = $this->httpClientFactory->createClient()->request(
					$method,
					$url,
					$headers,
					$body ?? '',
				);

				$request
					->then(
						function (Message\ResponseInterface $response) use ($deferred, $method, $url, $headers, $params, $body): void {
							try {
								$responseBody = $response->getBody()->getContents();

								$response->getBody()->rewind();
							} catch (RuntimeException $ex) {
								$this->logger->error('Received payload is not valid', [
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
									'type' => 'lan-api',
									'exception' => BootstrapHelpers\Logger::buildException($ex),
									'request' => [
										'method' => $method,
										'url' => $url,
										'params' => $params,
										'body' => $body,
									],
									'connector' => [
										'identifier' => $this->identifier,
									],
								]);

								$deferred->reject(
									new Exceptions\LanApiCall('Could not get content from response body'),
								);

								return;
							}

							$this->logger->debug('Received response', [
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
								'type' => 'lan-api',
								'request' => [
									'method' => $method,
									'url' => $url,
									'headers' => $headers,
									'params' => $params,
									'body' => $body,
								],
								'response' => [
									'status_code' => $response->getStatusCode(),
									'body' => $responseBody,
								],
								'connector' => [
									'identifier' => $this->identifier,
								],
							]);

							$deferred->resolve($response);
						},
						function (Throwable $ex) use ($deferred, $method, $url, $params, $body): void {
							$this->logger->error('Calling api endpoint failed', [
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
								'type' => 'lan-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'request' => [
									'method' => $method,
									'url' => $url,
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
				return Promise\reject($ex);
			}

			return $deferred->promise();
		}

		try {
			$response = $this->httpClientFactory->createClient(false)->request(
				$method,
				$url,
				[
					'headers' => $headers,
					'body' => $body ?? '',
				],
			);

			try {
				$responseBody = $response->getBody()->getContents();

				$response->getBody()->rewind();
			} catch (RuntimeException $ex) {
				$this->logger->error('Received payload is not valid', [
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'lan-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'request' => [
						'method' => $method,
						'url' => $url,
						'params' => $params,
						'body' => $body,
					],
					'connector' => [
						'identifier' => $this->identifier,
					],
				]);

				return false;
			}

			$this->logger->debug('Received response', [
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'lan-api',
				'request' => [
					'method' => $method,
					'url' => $url,
					'headers' => $headers,
					'params' => $params,
					'body' => $body,
				],
				'response' => [
					'status_code' => $response->getStatusCode(),
					'body' => $responseBody,
				],
				'connector' => [
					'identifier' => $this->identifier,
				],
			]);

			return $response;
		} catch (GuzzleHttp\Exception\GuzzleException | InvalidArgumentException $ex) {
			$this->logger->error('Calling api endpoint failed', [
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'lan-api',
				'exception' => BootstrapHelpers\Logger::buildException($ex),
				'request' => [
					'method' => $method,
					'url' => $url,
					'params' => $params,
					'body' => $body,
				],
				'connector' => [
					'identifier' => $this->identifier,
				],
			]);

			return false;
		}
	}

	/**
	 * @throws Exceptions\LanApiCall
	 */
	private function getSchema(string $schemaFilename, bool $response = true): string
	{
		try {
			$schema = $response ? Utils\FileSystem::read(
				NsPanel\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'response' . DIRECTORY_SEPARATOR . $schemaFilename,
			) : Utils\FileSystem::read(
				NsPanel\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . 'request' . DIRECTORY_SEPARATOR . $schemaFilename,
			);
		} catch (Nette\IOException) {
			throw new Exceptions\LanApiCall('Validation schema for response could not be loaded');
		}

		return $schema;
	}

}
