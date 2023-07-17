<?php declare(strict_types = 1);

/**
 * WsApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           08.01.23
 */

namespace FastyBird\Connector\Shelly\API;

use DateTimeInterface;
use Evenement;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Utils;
use Psr\Log;
use Ratchet;
use Ratchet\RFC6455;
use React\EventLoop;
use React\Promise;
use stdClass;
use Throwable;
use function array_key_exists;
use function gethostbyname;
use function hash;
use function implode;
use function intval;
use function preg_match;
use function property_exists;
use function React\Async\async;
use function strval;
use function time;
use function uniqid;

/**
 * Websockets API interface
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class WsApi implements Evenement\EventEmitterInterface
{

	use Gen2;
	use Nette\SmartObject;
	use Evenement\EventEmitterTrait;

	private const REQUEST_SRC = 'fb_ws_client';

	private const DEVICE_STATUS_METHOD = 'Shelly.GetStatus';

	private const SWITCH_SET_METHOD = 'Switch.Set';

	private const COVER_GO_TO_POSITION_METHOD = 'Cover.GoToPosition';

	private const LIGHT_SET_METHOD = 'Light.Set';

	private const NOTIFY_STATUS_METHOD = 'NotifyStatus';

	private const NOTIFY_FULL_STATUS_METHOD = 'NotifyFullStatus';

	private const NOTIFY_EVENT_METHOD = 'NotifyEvent';

	private const DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME = 'gen2_ws_status.json';

	private const DEVICE_EVENT_MESSAGE_SCHEMA_FILENAME = 'gen2_ws_event.json';

	private const WAIT_FOR_REPLY_TIMEOUT = 10.0;

	private bool $connecting = false;

	private bool $connected = false;

	/** @var array<string, Entities\API\Gen2\WsMessage> */
	private array $messages = [];

	private DateTimeInterface|null $lastConnectAttempt = null;

	private DateTimeInterface|null $disconnected = null;

	private DateTimeInterface|null $lost = null;

	private Ratchet\Client\WebSocket|null $connection = null;

	private Entities\API\Gen2\WsSession|null $session = null;

	public function __construct(
		private readonly string $identifier,
		private readonly string|null $ipAddress,
		private readonly string|null $domain,
		private readonly string|null $username,
		private readonly string|null $password,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
	}

	public function connect(): Promise\PromiseInterface
	{
		$this->messages = [];

		$this->connection = null;
		$this->connecting = true;
		$this->connected = false;

		$this->session = null;

		$this->lastConnectAttempt = $this->dateTimeFactory->getNow();
		$this->lost = null;
		$this->disconnected = null;

		$address = null;

		$deferred = new Promise\Deferred();

		try {
			if ($this->domain !== null) {
				$address = gethostbyname($this->domain);
			} elseif ($this->ipAddress !== null) {
				$address = $this->ipAddress;
			}

			if ($address === null) {
				Promise\reject(new DevicesExceptions\InvalidState('Device ip address or domain is not configured'));
			}

			$connector = new Ratchet\Client\Connector($this->eventLoop);

			$connector(
				'ws://' . $address . '/rpc',
				[],
				[
					'Connection' => 'Upgrade',
				],
			)
				->then(function (Ratchet\Client\WebSocket $connection) use ($deferred): void {
					$this->connection = $connection;
					$this->connecting = false;
					$this->connected = true;

					$this->lost = null;
					$this->disconnected = null;

					$this->logger->debug(
						'Connected to device sockets server',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'ws-api',
							'device' => [
								'identifier' => $this->identifier,
							],
						],
					);

					$connection->on(
						'message',
						function (RFC6455\Messaging\MessageInterface $message): void {
							try {
								$payload = Utils\Json::decode($message->getPayload());

							} catch (Utils\JsonException $ex) {
								$this->logger->debug(
									'Received message from device could not be parsed',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
										'type' => 'ws-api',
										'exception' => BootstrapHelpers\Logger::buildException($ex),
										'device' => [
											'identifier' => $this->identifier,
										],
									],
								);

								$this->emit('error', [$ex]);

								return;
							}

							if (!$payload instanceof stdClass) {
								return;
							}

							if (
								property_exists($payload, 'method')
								&& property_exists($payload, 'params')
							) {
								if (
									$payload->method === self::NOTIFY_STATUS_METHOD
									|| $payload->method === self::NOTIFY_FULL_STATUS_METHOD
								) {
									try {
										$entity = $this->parseDeviceStatusResponse(
											Utils\Json::encode($payload->params),
											self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME,
										);

										$this->emit('message', [$entity]);
									} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
										$this->logger->error(
											'Could not decode received payload',
											[
												'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
												'type' => 'ws-api',
												'exception' => BootstrapHelpers\Logger::buildException($ex),
												'response' => [
													'body' => Utils\Json::encode($payload->params),
													'schema' => self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME,
												],
											],
										);
									}
								} elseif ($payload->method === self::NOTIFY_EVENT_METHOD) {
									try {
										$entity = $this->parseDeviceEventResponse(
											Utils\Json::encode($payload->params),
											self::DEVICE_EVENT_MESSAGE_SCHEMA_FILENAME,
										);

										$this->emit('message', [$entity]);
									} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
										$this->logger->error(
											'Could not decode received payload',
											[
												'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
												'type' => 'ws-api',
												'exception' => BootstrapHelpers\Logger::buildException($ex),
												'response' => [
													'body' => Utils\Json::encode($payload->params),
													'schema' => self::DEVICE_EVENT_MESSAGE_SCHEMA_FILENAME,
												],
											],
										);
									}
								} else {
									$this->logger->warning(
										'Device respond with unsupported method',
										[
											'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
											'type' => 'ws-api',
											'device' => [
												'identifier' => $this->identifier,
											],
											'response' => [
												'method' => $payload->method,
												'payload' => $message->getPayload(),
											],
										],
									);
								}
							}

							if (
								!property_exists($payload, 'id')
								|| !array_key_exists($payload->id, $this->messages)
							) {
								return;
							}

							if (property_exists($payload, 'result')) {
								if ($this->messages[$payload->id]->getFrame()->getMethod() === self::DEVICE_STATUS_METHOD) {
									try {
										$entity = $this->parseDeviceStatusResponse(
											Utils\Json::encode($payload->result),
											self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME,
										);

										$this->messages[$payload->id]->getDeferred()?->resolve($entity);
									} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
										$this->logger->error(
											'Could not decode received payload',
											[
												'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
												'type' => 'ws-api',
												'exception' => BootstrapHelpers\Logger::buildException($ex),
												'response' => [
													'body' => Utils\Json::encode($payload->result),
													'schema' => self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME,
												],
											],
										);

										$this->messages[$payload->id]->getDeferred()?->reject(
											new Exceptions\WsCall('Could not decode received payload'),
										);
									}
								} elseif (
									$this->messages[$payload->id]->getFrame()->getMethod() === self::SWITCH_SET_METHOD
									|| $this->messages[$payload->id]->getFrame()->getMethod() === self::COVER_GO_TO_POSITION_METHOD
									|| $this->messages[$payload->id]->getFrame()->getMethod() === self::LIGHT_SET_METHOD
								) {
									$this->messages[$payload->id]->getDeferred()?->resolve();
								} else {
									$this->messages[$payload->id]->getDeferred()?->reject(
										new Exceptions\WsCall('Received response could not be processed'),
									);
								}

								if ($this->messages[$payload->id]->getTimer() !== null) {
									$this->eventLoop->cancelTimer($this->messages[$payload->id]->getTimer());
								}

								unset($this->messages[$payload->id]);

								return;
							} elseif (property_exists($payload, 'error')) {
								if (
									property_exists($payload->error, 'code')
									&& property_exists($payload->error, 'message')
									&& $payload->error->code === StatusCodeInterface::STATUS_UNAUTHORIZED
									&& $this->session === null
								) {
									$errorMessage = Utils\Json::decode(strval($payload->error->message));

									if (
										$errorMessage instanceof stdClass
										&& property_exists($errorMessage, 'realm')
										&& property_exists($errorMessage, 'nonce')
									) {
										$nc = 1;
										$clientNonce = time();

										$ha1 = hash(
											'sha256',
											implode(
												':',
												[
													$this->username,
													$errorMessage->realm,
													$this->password,
												],
											),
										);
										$ha2 = hash('sha256', 'dummy_method:dummy_uri');
										$response = hash(
											'sha256',
											implode(
												':',
												[
													$ha1,
													$errorMessage->nonce,
													$nc,
													$clientNonce,
													'auth',
													$ha2,
												],
											),
										);

										$this->session = new Entities\API\Gen2\WsSession(
											$errorMessage->realm,
											strval($this->username),
											$errorMessage->nonce,
											$clientNonce,
											$response,
											$nc,
											'SHA-256',
										);

										$messageFrame = new Entities\API\Gen2\WsFrame(
											$this->messages[$payload->id]->getFrame()->getId(),
											$this->messages[$payload->id]->getFrame()->getSrc(),
											$this->messages[$payload->id]->getFrame()->getMethod(),
											$this->messages[$payload->id]->getFrame()->getParams(),
											$this->session->toArray(),
										);

										$this->sendRequest(
											$messageFrame,
											$this->messages[$payload->id]->getDeferred(),
										);

										return;
									}
								}

								$this->logger->warning(
									'Device respond with error',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
										'type' => 'ws-api',
										'device' => [
											'identifier' => $this->identifier,
										],
										'error' => [
											'code' => property_exists(
												$payload->error,
												'message',
											) ? $payload->code : null,
											'message' => property_exists(
												$payload->error,
												'message',
											) ? $payload->message : null,
										],
									],
								);
							}

							$this->messages[$payload->id]->getDeferred()?->reject(
								new Exceptions\WsCall('Received device response could not be processed'),
							);

							if ($this->messages[$payload->id]->getTimer() !== null) {
								$this->eventLoop->cancelTimer($this->messages[$payload->id]->getTimer());
							}

							unset($this->messages[$payload->id]);
						},
					);

					$connection->on('error', function (Throwable $ex): void {
						$this->logger->error(
							'An error occurred on device connection',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
								'type' => 'ws-api',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'device' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$this->lost();

						$this->emit('error', [$ex]);
					});

					$connection->on('close', function ($code = null, $reason = null): void {
						$this->logger->debug(
							'Connection with device was closed',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
								'type' => 'ws-api',
								'connection' => [
									'code' => $code,
									'reason' => $reason,
								],
								'device' => [
									'identifier' => $this->identifier,
								],
							],
						);

						$this->disconnect();

						$this->emit('disconnected');
					});

					$this->emit('connected');

					$deferred->resolve();
				})
				->otherwise(function (Throwable $ex) use ($deferred): void {
					$this->logger->error(
						'Connection to device failed',
						[
							'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
							'type' => 'ws-api',
							'exception' => BootstrapHelpers\Logger::buildException($ex),
							'device' => [
								'identifier' => $this->identifier,
							],
						],
					);

					$this->connection = null;

					$this->connecting = false;
					$this->connected = false;

					$this->emit('error', [$ex]);

					$deferred->reject($ex);
				});
		} catch (Throwable $ex) {
			$this->connection = null;

			$this->connecting = false;
			$this->connected = false;

			$this->logger->error(
				'Could not create device client',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
					'type' => 'ws-api',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'device' => [
						'identifier' => $this->identifier,
					],
				],
			);

			$this->emit('error', [$ex]);

			$deferred->reject($ex);
		}

		return $deferred->promise();
	}

	public function disconnect(): void
	{
		$this->connection?->close();
		$this->connection = null;

		$this->connecting = false;
		$this->connected = false;

		$this->disconnected = $this->dateTimeFactory->getNow();

		$this->session = null;

		foreach ($this->messages as $message) {
			$message->getDeferred()?->reject(new Exceptions\WsCall('Closing connection to device'));

			if ($message->getTimer() !== null) {
				$this->eventLoop->cancelTimer($message->getTimer());
			}
		}

		$this->messages = [];
	}

	public function isConnecting(): bool
	{
		return $this->connecting;
	}

	public function isConnected(): bool
	{
		return $this->connection !== null && !$this->connecting && $this->connected;
	}

	public function getLastConnectAttempt(): DateTimeInterface|null
	{
		return $this->lastConnectAttempt;
	}

	public function getDisconnected(): DateTimeInterface|null
	{
		return $this->disconnected;
	}

	public function getLost(): DateTimeInterface|null
	{
		return $this->lost;
	}

	public function readStates(): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$messageFrame = new Entities\API\Gen2\WsFrame(
			uniqid(),
			self::REQUEST_SRC,
			self::DEVICE_STATUS_METHOD,
			null,
			$this->session?->toArray(),
		);

		$this->sendRequest($messageFrame, $deferred);

		return $deferred->promise();
	}

	public function writeState(
		string $component,
		int|float|string|bool $value,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($this->connection === null) {
			return Promise\reject(
				new Exceptions\InvalidState('Connection with device is not established'),
			);
		}

		if (
			preg_match(self::$PROPERTY_COMPONENT, $component, $propertyMatches) !== 1
			|| !array_key_exists('component', $propertyMatches)
			|| !array_key_exists('identifier', $propertyMatches)
			|| !array_key_exists('attribute', $propertyMatches)
		) {
			return Promise\reject(new Exceptions\InvalidState('Property identifier is not in expected format'));
		}

		try {
			$componentMethod = $this->buildComponentMethod($component);

		} catch (Exceptions\InvalidState) {
			return Promise\reject(new Exceptions\InvalidState('Component action could not be created'));
		}

		$messageFrame = new Entities\API\Gen2\WsFrame(
			uniqid(),
			self::REQUEST_SRC,
			$componentMethod,
			[
				'id' => intval($propertyMatches['identifier']),
				$propertyMatches['attribute'] => $value,
			],
			$this->session?->toArray(),
		);

		$this->sendRequest($messageFrame, $deferred);

		return $deferred->promise();
	}

	private function lost(): void
	{
		$this->lost = $this->dateTimeFactory->getNow();

		$this->emit('lost');

		$this->disconnect();
	}

	private function sendRequest(Entities\API\Gen2\WsFrame $frame, Promise\Deferred|null $deferred = null): void
	{
		$timeout = $this->eventLoop->addTimer(
			self::WAIT_FOR_REPLY_TIMEOUT,
			async(function () use ($deferred, $frame): void {
				$deferred?->reject(new Exceptions\WsCallTimeout('Sending command to device failed'));

				if (array_key_exists($frame->getId(), $this->messages)) {
					if ($this->messages[$frame->getId()]->getTimer() !== null) {
						$this->eventLoop->cancelTimer($this->messages[$frame->getId()]->getTimer());
					}

					unset($this->messages[$frame->getId()]);
				}
			}),
		);

		$this->messages[$frame->getId()] = new Entities\API\Gen2\WsMessage(
			$frame,
			$deferred,
			$timeout,
		);

		$this->connection?->send(strval($frame));
	}

}
