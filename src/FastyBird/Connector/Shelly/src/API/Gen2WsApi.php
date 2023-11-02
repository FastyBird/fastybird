<?php declare(strict_types = 1);

/**
 * Gen2WsApi.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           08.01.23
 */

namespace FastyBird\Connector\Shelly\API;

use DateTimeInterface;
use Evenement;
use FastyBird\Connector\Shelly;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Connector\Shelly\ValueObjects;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Schemas as MetadataSchemas;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Fig\Http\Message\StatusCodeInterface;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
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
use const DIRECTORY_SEPARATOR;

/**
 * Generation 2 device websockets API interface
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Gen2WsApi implements Evenement\EventEmitterInterface
{

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

	private const DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME = 'gen2_ws_state.json';

	private const DEVICE_EVENT_MESSAGE_SCHEMA_FILENAME = 'gen2_ws_event.json';

	private const WAIT_FOR_REPLY_TIMEOUT = 10.0;

	private const PROPERTY_COMPONENT = '/^(?P<component>[a-zA-Z]+)_(?P<identifier>[0-9]+)(_(?P<attribute>[a-zA-Z0-9]+))?$/';

	private const COMPONENT_KEY = '/^(?P<component>[a-zA-Z]+)(:(?P<channel>[0-9_]+))?$/';

	private bool $connecting = false;

	private bool $connected = false;

	/** @var array<string, ValueObjects\WsMessage> */
	private array $messages = [];

	private DateTimeInterface|null $lastConnectAttempt = null;

	private DateTimeInterface|null $disconnected = null;

	private DateTimeInterface|null $lost = null;

	private Ratchet\Client\WebSocket|null $connection = null;

	private ValueObjects\WsSession|null $session = null;

	public function __construct(
		private readonly Uuid\UuidInterface $id,
		private readonly string|null $ipAddress,
		private readonly string|null $domain,
		private readonly string|null $username,
		private readonly string|null $password,
		private readonly Helpers\Entity $entityHelper,
		private readonly Shelly\Logger $logger,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly MetadataSchemas\Validator $schemaValidator,
		private readonly ObjectMapper\Processing\Processor $objectMapper,
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
								'id' => $this->id->toString(),
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
									'Received message from device could not be decoded',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
										'type' => 'ws-api',
										'exception' => BootstrapHelpers\Logger::buildException($ex),
										'device' => [
											'id' => $this->id->toString(),
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
										);

										$this->emit('message', [$entity]);
									} catch (Exceptions\WsCall | Exceptions\WsError $ex) {
										$this->logger->error(
											'Could not handle received device status message',
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

										$this->emit('error', [$ex]);
									}
								} elseif ($payload->method === self::NOTIFY_EVENT_METHOD) {
									try {
										$entity = $this->parseDeviceEventResponse(Utils\Json::encode($payload->params));

										$this->emit('message', [$entity]);
									} catch (Exceptions\WsCall | Exceptions\WsError $ex) {
										$this->logger->error(
											'Could not handle received event message',
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

										$this->emit('error', [$ex]);
									}
								} else {
									$this->logger->warning(
										'Device respond with unsupported method',
										[
											'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
											'type' => 'ws-api',
											'device' => [
												'id' => $this->id->toString(),
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
										);

										$this->messages[$payload->id]->getDeferred()?->resolve($entity);
									} catch (Exceptions\WsCall | Exceptions\WsError $ex) {
										$this->logger->error(
											'Could not handle received response device status message',
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

										try {
											$this->session = $this->objectMapper->process(
												[
													'realm' => $errorMessage->realm,
													'username' => strval($this->username),
													'nonce' => $errorMessage->nonce,
													'cnonce' => $clientNonce,
													'response' => $response,
													'nc' => $nc,
													'algorithm' => 'SHA-256',
												],
												ValueObjects\WsSession::class,
											);
										} catch (ObjectMapper\Exception\InvalidData $ex) {
											$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
												new ObjectMapper\Printers\TypeToStringConverter(),
											);

											throw new Exceptions\WsError(
												'Connection session could not be created: ' . $errorPrinter->printError(
													$ex,
												),
											);
										}

										try {
											$messageFrame = $this->objectMapper->process(
												[
													'id' => $this->messages[$payload->id]->getFrame()->getId(),
													'src' => $this->messages[$payload->id]->getFrame()->getSrc(),
													'method' => $this->messages[$payload->id]->getFrame()->getMethod(),
													'params' => $this->messages[$payload->id]->getFrame()->getParams(),
													'auth' => $this->session->toArray(),
												],
												ValueObjects\WsFrame::class,
											);
										} catch (ObjectMapper\Exception\InvalidData $ex) {
											$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
												new ObjectMapper\Printers\TypeToStringConverter(),
											);

											throw new Exceptions\WsError(
												'Message frame could not be created: ' . $errorPrinter->printError($ex),
											);
										}

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
											'id' => $this->id->toString(),
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
									'id' => $this->id->toString(),
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
									'id' => $this->id->toString(),
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
								'id' => $this->id->toString(),
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
						'id' => $this->id->toString(),
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

		try {
			$messageFrame = $this->objectMapper->process(
				[
					'id' => uniqid(),
					'src' => self::REQUEST_SRC,
					'method' => self::DEVICE_STATUS_METHOD,
					'params' => null,
					'auth' => $this->session?->toArray(),
				],
				ValueObjects\WsFrame::class,
			);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			return Promise\reject(
				new Exceptions\WsError('Message frame could not be created: ' . $errorPrinter->printError($ex)),
			);
		}

		try {
			$this->sendRequest($messageFrame, $deferred);
		} catch (Exceptions\WsError $ex) {
			return Promise\reject($ex);
		}

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
			preg_match(self::PROPERTY_COMPONENT, $component, $propertyMatches) !== 1
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

		try {
			$messageFrame = $this->objectMapper->process(
				[
					'id' => uniqid(),
					'src' => self::REQUEST_SRC,
					'method' => $componentMethod,
					'params' => [
						'id' => intval($propertyMatches['identifier']),
						$propertyMatches['attribute'] => $value,
					],
					'auth' => $this->session?->toArray(),
				],
				ValueObjects\WsFrame::class,
			);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			return Promise\reject(
				new Exceptions\WsError('Message frame could not be created: ' . $errorPrinter->printError($ex)),
			);
		}

		try {
			$this->sendRequest($messageFrame, $deferred);
		} catch (Exceptions\WsError $ex) {
			return Promise\reject($ex);
		}

		return $deferred->promise();
	}

	private function lost(): void
	{
		$this->lost = $this->dateTimeFactory->getNow();

		$this->emit('lost');

		$this->disconnect();
	}

	/**
	 * @throws Exceptions\WsError
	 */
	private function sendRequest(ValueObjects\WsFrame $frame, Promise\Deferred|null $deferred = null): void
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

		try {
			$this->messages[$frame->getId()] = $this->objectMapper->process(
				[
					'frame' => $frame,
					'deferred' => $deferred,
					'timer' => $timeout,
				],
				ValueObjects\WsMessage::class,
			);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\WsError('Message could not be created: ' . $errorPrinter->printError($ex));
		}

		$this->connection?->send(strval($frame));
	}

	/**
	 * @throws Exceptions\WsCall
	 * @throws Exceptions\WsError
	 */
	private function parseDeviceStatusResponse(
		string $payload,
	): Entities\API\Gen2\GetDeviceState
	{
		$data = $this->validatePayload(
			$payload,
			self::DEVICE_STATUS_MESSAGE_SCHEMA_FILENAME,
		);

		$switches = $covers = $lights = $inputs = $temperature = $humidity = [];
		$ethernet = $wifi = null;

		foreach ($data as $key => $state) {
			if (
				$state instanceof Utils\ArrayHash
				&& preg_match(self::COMPONENT_KEY, $key, $componentMatches) === 1
				&& array_key_exists('component', $componentMatches)
				&& Types\ComponentType::isValidValue($componentMatches['component'])
			) {
				if ($componentMatches['component'] === Types\ComponentType::SWITCH) {
					$switches[] = (array) $state;
				} elseif ($componentMatches['component'] === Types\ComponentType::COVER) {
					$covers[] = (array) $state;
				} elseif ($componentMatches['component'] === Types\ComponentType::LIGHT) {
					$lights[] = (array) $state;
				} elseif ($componentMatches['component'] === Types\ComponentType::INPUT) {
					$inputs[] = (array) $state;
				} elseif ($componentMatches['component'] === Types\ComponentType::TEMPERATURE) {
					$temperature[] = (array) $state;
				} elseif ($componentMatches['component'] === Types\ComponentType::HUMIDITY) {
					$humidity[] = (array) $state;
				} elseif ($componentMatches['component'] === Types\ComponentType::ETHERNET) {
					$ethernet = (array) $state;
				} elseif ($componentMatches['component'] === Types\ComponentType::WIFI) {
					$wifi = (array) $state;
				}
			}
		}

		return $this->createEntity(Entities\API\Gen2\GetDeviceState::class, Utils\ArrayHash::from([
			'switches' => $switches,
			'covers' => $covers,
			'inputs' => $inputs,
			'lights' => $lights,
			'temperature' => $temperature,
			'humidity' => $humidity,
			'ethernet' => $ethernet,
			'wifi' => $wifi,
		]));
	}

	/**
	 * @throws Exceptions\WsCall
	 * @throws Exceptions\WsError
	 */
	private function parseDeviceEventResponse(
		string $payload,
	): Entities\API\Gen2\DeviceEvent
	{
		$data = $this->validatePayload($payload, self::DEVICE_EVENT_MESSAGE_SCHEMA_FILENAME);

		$events = [];

		foreach ((array) $data->offsetGet('events') as $event) {
			if ($event instanceof Utils\ArrayHash) {
				$events[] = [
					'component' => $event->offsetGet('component'),
					'id' => $event->offsetGet('id'),
					'event' => $event->offsetGet('event'),
					'timestamp' => $event->offsetGet('ts'),
				];
			}
		}

		return $this->createEntity(
			Entities\API\Gen2\DeviceEvent::class,
			Utils\ArrayHash::from([
				'events' => $events,
			]),
		);
	}

	/**
	 * @template T of Entities\API\Entity
	 *
	 * @param class-string<T> $entity
	 *
	 * @return T
	 *
	 * @throws Exceptions\WsCall
	 */
	protected function createEntity(string $entity, Utils\ArrayHash $data): Entities\API\Entity
	{
		try {
			return $this->entityHelper->create(
				$entity,
				(array) Utils\Json::decode(Utils\Json::encode($data), Utils\Json::FORCE_ARRAY),
			);
		} catch (Exceptions\Runtime $ex) {
			throw new Exceptions\WsCall('Could not map payload to entity', $ex->getCode(), $ex);
		} catch (Utils\JsonException $ex) {
			throw new Exceptions\WsCall('Could not create entity from payload', $ex->getCode(), $ex);
		}
	}

	/**
	 * @return ($throw is true ? Utils\ArrayHash : Utils\ArrayHash|false)
	 *
	 * @throws Exceptions\WsCall
	 * @throws Exceptions\WsError
	 */
	protected function validatePayload(
		string $payload,
		string $schemaFilename,
		bool $throw = true,
	): Utils\ArrayHash|bool
	{
		try {
			return $this->schemaValidator->validate(
				$payload,
				$this->getSchema($schemaFilename),
			);
		} catch (MetadataExceptions\Logic | MetadataExceptions\MalformedInput | MetadataExceptions\InvalidData $ex) {
			if ($throw) {
				throw new Exceptions\WsCall(
					'Could not validate received payload',
					$ex->getCode(),
					$ex,
				);
			}

			return false;
		}
	}

	/**
	 * @throws Exceptions\WsError
	 */
	private function getSchema(string $schemaFilename): string
	{
		try {
			$schema = Utils\FileSystem::read(
				Shelly\Constants::RESOURCES_FOLDER . DIRECTORY_SEPARATOR . $schemaFilename,
			);

		} catch (Nette\IOException) {
			throw new Exceptions\WsError('Validation schema for payload could not be loaded');
		}

		return $schema;
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	private function buildComponentMethod(string $component): string
	{
		if (
			preg_match(self::PROPERTY_COMPONENT, $component, $componentMatches) !== 1
			|| !array_key_exists('component', $componentMatches)
			|| !array_key_exists('identifier', $componentMatches)
			|| !array_key_exists('attribute', $componentMatches)
		) {
			throw new Exceptions\InvalidState('Property identifier is not in expected format');
		}

		if (
			$componentMatches['component'] === Types\ComponentType::SWITCH
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::ON
		) {
			return self::SWITCH_SET_METHOD;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::COVER
			&& $componentMatches['attribute'] === Types\ComponentAttributeType::POSITION
		) {
			return self::COVER_GO_TO_POSITION_METHOD;
		}

		if (
			$componentMatches['component'] === Types\ComponentType::LIGHT
			&& (
				$componentMatches['description'] === Types\ComponentAttributeType::ON
				|| $componentMatches['attribute'] === Types\ComponentAttributeType::BRIGHTNESS
			)
		) {
			return self::LIGHT_SET_METHOD;
		}

		throw new Exceptions\InvalidState('Property method could not be build');
	}

}
