<?php declare(strict_types = 1);

/**
 * Client.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 * @since          0.25.0
 *
 * @date           23.02.20
 */

namespace FastyBird\Connector\FbMqtt\Clients;

use BinSoul\Net\Mqtt;
use Closure;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Consumers;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use InvalidArgumentException;
use Nette;
use Psr\Log;
use React\EventLoop;
use React\Promise;
use React\Socket;
use React\Stream;
use Throwable;
use function array_shift;
use function array_values;
use function assert;
use function call_user_func;
use function count;
use function floor;
use function intval;
use function is_string;
use function sprintf;
use function strval;

/**
 * MQTT client service
 *
 * The following events are emitted:
 *  - onOpen - The network connection to the server is established.
 *  - onClose - The network connection to the server is closed.
 *  - onWarning - An event of severity "warning" occurred.
 *  - onError - An event of severity "error" occurred.
 *  - onConnect - The client connected to the broker.
 *  - onDisconnect - The client disconnected from the broker.
 *  - onSubscribe - The client subscribed to a topic filter.
 *  - onUnsubscribe - The client unsubscribed from topic filter.
 *  - onPublish - A message was published.
 *  - onMessage - A message was received.
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Client
{

	use Nette\SmartObject;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	protected bool $isConnected = false;

	protected bool $isConnecting = false;

	protected bool $isDisconnecting = false;

	/** @var Closure(Mqtt\Connection $connection): void|null */
	protected Closure|null $onCloseCallback = null;

	/** @var Array<EventLoop\TimerInterface> */
	protected array $timer = [];

	/** @var Array<Flow> */
	protected array $receivingFlows = [];

	/** @var Array<Flow> */
	protected array $sendingFlows = [];

	protected Flow|null $writtenFlow = null;

	protected EventLoop\TimerInterface|null $handlerTimer;

	protected Stream\DuplexStreamInterface|null $stream = null;

	protected Mqtt\StreamParser $parser;

	protected Mqtt\ClientIdentifierGenerator $identifierGenerator;

	protected Mqtt\Connection|null $connection = null;

	protected Mqtt\FlowFactory $flowFactory;

	protected Log\LoggerInterface $logger;

	public function __construct(
		protected Entities\FbMqttConnector $connector,
		protected Helpers\Connector $connectorHelper,
		protected Consumers\Messages $consumer,
		protected EventLoop\LoopInterface $eventLoop,
		Mqtt\ClientIdentifierGenerator|null $identifierGenerator = null,
		Mqtt\FlowFactory|null $flowFactory = null,
		Mqtt\StreamParser|null $parser = null,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->parser = $parser ?? new Mqtt\StreamParser(new Mqtt\DefaultPacketFactory());

		$this->parser->onError(function (Throwable $error): void {
			$this->onWarning($error);
		});

		$this->identifierGenerator = $identifierGenerator ?? new Mqtt\DefaultIdentifierGenerator();

		$this->flowFactory = $flowFactory ?? new Mqtt\DefaultFlowFactory(
			$this->identifierGenerator,
			new Mqtt\DefaultIdentifierGenerator(),
			new Mqtt\DefaultPacketFactory(),
		);

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * Connects to a broker
	 *
	 * @throws InvalidArgumentException
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function connect(int $timeout = 5): Promise\ExtendedPromiseInterface
	{
		$deferred = new Promise\Deferred();

		if ($this->isConnected || $this->isConnecting) {
			$promise = Promise\reject(new Exceptions\Logic('The client is already connected'));
			assert($promise instanceof Promise\ExtendedPromiseInterface);

			return $promise;
		}

		$this->isConnecting = true;
		$this->isConnected = false;

		$username = $this->connectorHelper->getConfiguration(
			$this->connector,
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_USERNAME),
		);

		$password = $this->connectorHelper->getConfiguration(
			$this->connector,
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_PASSWORD),
		);

		$server = $this->connectorHelper->getConfiguration(
			$this->connector,
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_SERVER),
		);

		$port = $this->connectorHelper->getConfiguration(
			$this->connector,
			Types\ConnectorPropertyIdentifier::get(Types\ConnectorPropertyIdentifier::IDENTIFIER_PORT),
		);

		$connection = new Mqtt\DefaultConnection(
			is_string($username) ? $username : '',
			is_string($password) ? $password : '',
			null,
			$this->connector->getPlainId(),
		);

		if ($connection->getClientID() === '') {
			$connection = $connection->withClientID($this->identifierGenerator->generateClientIdentifier());
		}

		$this->establishConnection(strval($server), intval($port), $timeout)
			->then(function (Stream\DuplexStreamInterface $stream) use ($connection, $deferred, $timeout): void {
				$this->stream = $stream;

				$this->onOpen($connection);

				$this->registerClient($connection, $timeout)
					->then(function ($result) use ($connection, $deferred): void {
						$this->isConnecting = false;
						$this->isConnected = true;
						$this->connection = $connection;

						$this->onConnect($connection);

						$deferred->resolve($result ?? $connection);
					})
					->otherwise(function (Throwable $reason) use ($connection, $deferred): void {
						$this->isConnecting = false;

						$this->onError($reason);

						$deferred->reject($reason);

						$this->stream?->close();

						$this->onClose($connection);
					});
			})
			->otherwise(function (Throwable $reason) use ($deferred): void {
				$this->isConnecting = false;

				$this->onError($reason);

				$deferred->reject($reason);
			});

		$promise = $deferred->promise();
		assert($promise instanceof Promise\ExtendedPromiseInterface);

		return $promise;
	}

	/**
	 * Disconnects from a broker
	 *
	 * @throws DevicesExceptions\Terminate
	 */
	public function disconnect(int $timeout = 5): Promise\ExtendedPromiseInterface
	{
		if (!$this->isConnected || $this->isDisconnecting || $this->connection === null) {
			$promise = Promise\reject(new Exceptions\Logic('The client is not connected'));
			assert($promise instanceof Promise\ExtendedPromiseInterface);

			return $promise;
		}

		$this->isDisconnecting = true;

		$deferred = new Promise\Deferred();

		$isResolved = false;

		/** @var mixed $flowResult */
		$flowResult = null;

		$this->onCloseCallback = function ($connection) use ($deferred, &$isResolved, &$flowResult): void {
			if (!$isResolved) {
				$isResolved = true;

				if ($connection) {
					$this->onDisconnect($connection);
				}

				$deferred->resolve($flowResult ?? $connection);
			}
		};

		$this->startFlow($this->flowFactory->buildOutgoingDisconnectFlow($this->connection), true)
			->then(function ($result) use ($timeout, &$flowResult): void {
				$flowResult = $result;

				$this->timer[] = $this->eventLoop->addTimer(
					$timeout,
					function (): void {
						$this->stream?->close();
					},
				);
			})
			->otherwise(function ($exception) use ($deferred, &$isResolved): void {
				if (!$isResolved) {
					$isResolved = true;
					$this->isDisconnecting = false;
					$deferred->reject($exception);
				}
			});

		$promise = $deferred->promise();
		assert($promise instanceof Promise\ExtendedPromiseInterface);

		return $promise;
	}

	/**
	 * Subscribes to a topic filter
	 *
	 * @throws DevicesExceptions\Terminate
	 */
	public function subscribe(Mqtt\Subscription $subscription): Promise\ExtendedPromiseInterface
	{
		if (!$this->isConnected) {
			$promise = Promise\reject(new Exceptions\Logic('The client is not connected'));
			assert($promise instanceof Promise\ExtendedPromiseInterface);

			return $promise;
		}

		return $this->startFlow($this->flowFactory->buildOutgoingSubscribeFlow([$subscription]));
	}

	/**
	 * Unsubscribes from a topic filter
	 *
	 * @throws DevicesExceptions\Terminate
	 */
	public function unsubscribe(Mqtt\Subscription $subscription): Promise\ExtendedPromiseInterface
	{
		if (!$this->isConnected) {
			$promise = Promise\reject(new Exceptions\Logic('The client is not connected'));
			assert($promise instanceof Promise\ExtendedPromiseInterface);

			return $promise;
		}

		$deferred = new Promise\Deferred();

		$this->startFlow($this->flowFactory->buildOutgoingUnsubscribeFlow([$subscription]))
			->then(static function (array $subscriptions) use ($deferred): void {
				$deferred->resolve(array_shift($subscriptions));
			})
			->otherwise(static function ($exception) use ($deferred): void {
				$deferred->reject($exception);
			});

		$promise = $deferred->promise();
		assert($promise instanceof Promise\ExtendedPromiseInterface);

		return $promise;
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 */
	public function publish(
		string $topic,
		string|null $payload,
		int $qos = FbMqtt\Constants::MQTT_API_QOS_0,
		bool $retain = false,
	): Promise\ExtendedPromiseInterface
	{
		$message = new Mqtt\DefaultMessage($topic, ($payload ?? ''), $qos, $retain);

		if (!$this->isConnected) {
			$promise = Promise\reject(new Exceptions\Logic('The client is not connected'));
			assert($promise instanceof Promise\ExtendedPromiseInterface);

			return $promise;
		}

		return $this->startFlow($this->flowFactory->buildOutgoingPublishFlow($message));
	}

	abstract protected function handleCommunication(): void;

	protected function onOpen(Mqtt\Connection $connection): void
	{
		// Network connection established
		$this->logger->info(
			'Established connection to MQTT broker',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
				'type' => 'client',
				'credentials' => [
					'username' => $connection->getUsername(),
				],
				'connector' => [
					'id' => $this->connector->getPlainId(),
				],
			],
		);
	}

	protected function onClose(Mqtt\Connection $connection): void
	{
		// Network connection closed
		$this->logger->info(
			'Connection to MQTT broker',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
				'type' => 'client',
				'credentials' => [
					'username' => $connection->getUsername(),
				],
				'connector' => [
					'id' => $this->connector->getPlainId(),
				],
			],
		);

		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);
		}
	}

	protected function onConnect(Mqtt\Connection $connection): void
	{
		// Broker connected
		$this->logger->info(
			sprintf('Connected to MQTT broker with client id %s', $connection->getClientID()),
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
				'type' => 'client',
				'credentials' => [
					'username' => $connection->getUsername(),
				],
				'connector' => [
					'id' => $this->connector->getPlainId(),
				],
			],
		);

		$this->registerLoopHandler();
	}

	protected function onDisconnect(Mqtt\Connection $connection): void
	{
		// Broker disconnected
		$this->logger->info(
			sprintf('Disconnected from MQTT broker with client id %s', $connection->getClientID()),
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
				'type' => 'client',
				'credentials' => [
					'username' => $connection->getUsername(),
				],
				'connector' => [
					'id' => $this->connector->getPlainId(),
				],
			],
		);
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 */
	protected function onWarning(Throwable $ex): void
	{
		// Broker warning occur
		$this->logger->warning(
			sprintf('There was an error  %s', $ex->getMessage()),
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
				'type' => 'client',
				'error' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
				'connector' => [
					'id' => $this->connector->getPlainId(),
				],
			],
		);

		throw new DevicesExceptions\Terminate(
			'There was an error during handling requests',
			$ex->getCode(),
			$ex,
		);
	}

	/**
	 * @throws DevicesExceptions\Terminate
	 */
	protected function onError(Throwable $ex): void
	{
		// Broker error occur
		$this->logger->error(
			sprintf('There was an error  %s', $ex->getMessage()),
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
				'type' => 'client',
				'error' => [
					'message' => $ex->getMessage(),
					'code' => $ex->getCode(),
				],
				'connector' => [
					'id' => $this->connector->getPlainId(),
				],
			],
		);

		throw new DevicesExceptions\Terminate(
			'There was an error during handling requests',
			$ex->getCode(),
			$ex,
		);
	}

	protected function onMessage(Mqtt\Message $message): void
	{
		// Broker send message
		$this->logger->info(
			sprintf(
				'Received message in topic: %s with payload %s',
				$message->getTopic(),
				$message->getPayload(),
			),
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
				'type' => 'client',
				'message' => [
					'topic' => $message->getTopic(),
					'payload' => $message->getPayload(),
					'isRetained' => $message->isRetained(),
					'qos' => $message->getQosLevel(),
				],
				'connector' => [
					'id' => $this->connector->getPlainId(),
				],
			],
		);
	}

	protected function onSubscribe(Mqtt\Subscription $subscription): void
	{
		// TODO: Implement onSubscribe() method.
	}

	/**
	 * @param Array<Mqtt\Subscription> $subscriptions
	 */
	protected function onUnsubscribe(array $subscriptions): void
	{
		// TODO: Implement onUnsubscribe() method.
	}

	protected function onPublish(Mqtt\Message $message): void
	{
		// TODO: Implement onPublish() method.
	}

	protected function registerLoopHandler(): void
	{
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::HANDLER_PROCESSING_INTERVAL,
			function (): void {
				$this->handleCommunication();
			},
		);
	}

	/**
	 * Establishes a network connection to a server
	 *
	 * @throws InvalidArgumentException
	 */
	private function establishConnection(string $host, int $port, int $timeout): Promise\ExtendedPromiseInterface
	{
		$deferred = new Promise\Deferred();

		$future = null;

		$timer = $this->eventLoop->addTimer(
			$timeout,
			static function () use ($deferred, $timeout, &$future): void {
				$exception = new Exceptions\Runtime(sprintf('Connection timed out after %d seconds', $timeout));
				$deferred->reject($exception);

				/** @phpstan-ignore-next-line */
				if ($future instanceof Promise\CancellablePromiseInterface) {
					$future->cancel();
				}

				$future = null;
			},
		);

		$future = $this->getConnector()->connect($host . ':' . $port);

		if ($future instanceof Promise\ExtendedPromiseInterface) {
			$future
				->always(function () use ($timer): void {
					$this->eventLoop->cancelTimer($timer);
				})
				->then(function (Stream\DuplexStreamInterface $stream) use ($deferred): void {
					$stream->on('data', function ($data): void {
						$this->handleReceive($data);
					});

					$stream->on('close', function (): void {
						$this->handleClose();
					});

					$stream->on('error', function (Throwable $error): void {
						$this->handleError($error);
					});

					$deferred->resolve($stream);
				})
				->otherwise(static function (Throwable $reason) use ($deferred): void {
					$deferred->reject($reason);
				});
		}

		$promise = $deferred->promise();
		assert($promise instanceof Promise\ExtendedPromiseInterface);

		return $promise;
	}

	/**
	 * Registers a new client with the broker
	 *
	 * @throws DevicesExceptions\Terminate
	 */
	private function registerClient(Mqtt\Connection $connection, int $timeout): Promise\ExtendedPromiseInterface
	{
		$deferred = new Promise\Deferred();

		$responseTimer = $this->eventLoop->addTimer(
			$timeout,
			static function () use ($deferred, $timeout): void {
				$exception = new Exceptions\Runtime(sprintf('No response after %d seconds', $timeout));
				$deferred->reject($exception);
			},
		);

		$this->startFlow($this->flowFactory->buildOutgoingConnectFlow($connection), true)
			->always(function () use ($responseTimer): void {
				$this->eventLoop->cancelTimer($responseTimer);
			})
			->then(function ($result) use ($connection, $deferred): void {
				$this->timer[] = $this->eventLoop->addPeriodicTimer(
					floor($connection->getKeepAlive() * 0.75),
					function (): void {
						$this->startFlow($this->flowFactory->buildOutgoingPingFlow());
					},
				);

				$deferred->resolve($result ?? $connection);
			})
			->otherwise(static function (Throwable $reason) use ($deferred): void {
				$deferred->reject($reason);
			});

		$promise = $deferred->promise();
		assert($promise instanceof Promise\ExtendedPromiseInterface);

		return $promise;
	}

	/**
	 * Handles incoming data
	 *
	 * @throws DevicesExceptions\Terminate
	 * @throws Exceptions\Runtime
	 */
	private function handleReceive(string $data): void
	{
		if (!$this->isConnected && !$this->isConnecting) {
			return;
		}

		$flowCount = count($this->receivingFlows);

		$packets = $this->parser->push($data);

		foreach ($packets as $packet) {
			$this->handlePacket($packet);
		}

		if ($flowCount > count($this->receivingFlows)) {
			$this->receivingFlows = array_values($this->receivingFlows);
		}

		$this->handleSend();
	}

	/**
	 * Handles an incoming packet
	 *
	 * @throws DevicesExceptions\Terminate
	 * @throws Exceptions\Runtime
	 */
	private function handlePacket(Mqtt\Packet $packet): void
	{
		switch ($packet->getPacketType()) {
			case Mqtt\Packet::TYPE_PUBLISH:
				if (!($packet instanceof Mqtt\Packet\PublishRequestPacket)) {
					throw new Exceptions\Runtime(
						sprintf('Expected %s but got %s', Mqtt\Packet\PublishRequestPacket::class, $packet::class),
					);
				}

				$message = new Mqtt\DefaultMessage(
					$packet->getTopic(),
					$packet->getPayload(),
					$packet->getQosLevel(),
					$packet->isRetained(),
					$packet->isDuplicate(),
				);

				$this->startFlow($this->flowFactory->buildIncomingPublishFlow($message, $packet->getIdentifier()));

				break;
			case Mqtt\Packet::TYPE_CONNACK:
			case Mqtt\Packet::TYPE_PINGRESP:
			case Mqtt\Packet::TYPE_SUBACK:
			case Mqtt\Packet::TYPE_UNSUBACK:
			case Mqtt\Packet::TYPE_PUBREL:
			case Mqtt\Packet::TYPE_PUBACK:
			case Mqtt\Packet::TYPE_PUBREC:
			case Mqtt\Packet::TYPE_PUBCOMP:
				$flowFound = false;

				foreach ($this->receivingFlows as $index => $flow) {
					if ($flow->accept($packet)) {
						$flowFound = true;

						unset($this->receivingFlows[$index]);
						$this->continueFlow($flow, $packet);

						break;
					}
				}

				if (!$flowFound) {
					$this->onWarning(
						new Exceptions\Logic(
							sprintf('Received unexpected packet of type %d', $packet->getPacketType()),
						),
					);
				}

				break;
			default:
				$this->onWarning(
					new Exceptions\Logic(sprintf('Cannot handle packet of type %d', $packet->getPacketType())),
				);
		}
	}

	/**
	 * Handles outgoing packets
	 */
	private function handleSend(): void
	{
		$flow = null;

		if ($this->writtenFlow !== null) {
			$flow = $this->writtenFlow;
			$this->writtenFlow = null;
		}

		if (count($this->sendingFlows) > 0 && $this->stream !== null) {
			$this->writtenFlow = array_shift($this->sendingFlows);

			if ($this->writtenFlow !== null) {
				$this->stream->write($this->writtenFlow->getPacket());
			}
		}

		if ($flow !== null) {
			if ($flow->isFinished()) {
				$this->eventLoop->futureTick(function () use ($flow): void {
					$this->finishFlow($flow);
				});

			} else {
				$this->receivingFlows[] = $flow;
			}
		}
	}

	/**
	 * Handles closing of the stream
	 */
	private function handleClose(): void
	{
		foreach ($this->timer as $timer) {
			$this->eventLoop->cancelTimer($timer);
		}

		$connection = $this->connection;

		$this->isConnecting = false;
		$this->isDisconnecting = false;
		$this->isConnected = false;
		$this->connection = null;
		$this->stream = null;

		if ($this->onCloseCallback !== null) {
			call_user_func($this->onCloseCallback, $connection);

			$this->onCloseCallback = null;
		}

		if ($connection !== null) {
			$this->onClose($connection);
		}
	}

	/**
	 * Handles errors of the stream
	 *
	 * @throws DevicesExceptions\Terminate
	 */
	private function handleError(Throwable $error): void
	{
		$this->onError($error);
	}

	/**
	 * Starts the given flow
	 *
	 * @throws DevicesExceptions\Terminate
	 */
	private function startFlow(Mqtt\Flow $flow, bool $isSilent = false): Promise\ExtendedPromiseInterface
	{
		try {
			$packet = $flow->start();

		} catch (Throwable $ex) {
			$this->onError($ex);

			$promise = Promise\reject($ex);
			assert($promise instanceof Promise\ExtendedPromiseInterface);

			return $promise;
		}

		$deferred = new Promise\Deferred();
		$internalFlow = new Flow($flow, $deferred, $packet, $isSilent);

		if ($packet !== null) {
			if ($this->writtenFlow !== null) {
				$this->sendingFlows[] = $internalFlow;

			} elseif ($this->stream !== null) {
				$this->stream->write($packet);
				$this->writtenFlow = $internalFlow;
				$this->handleSend();
			}
		} else {
			$this->eventLoop->futureTick(function () use ($internalFlow): void {
				$this->finishFlow($internalFlow);
			});
		}

		$promise = $deferred->promise();
		assert($promise instanceof Promise\ExtendedPromiseInterface);

		return $promise;
	}

	/**
	 * Continues the given flow
	 *
	 * @throws DevicesExceptions\Terminate
	 */
	private function continueFlow(Flow $flow, Mqtt\Packet $packet): void
	{
		try {
			$response = $flow->next($packet);

		} catch (Throwable $t) {
			$this->onError($t);

			return;
		}

		if ($response !== null) {
			if ($this->writtenFlow !== null) {
				$this->sendingFlows[] = $flow;

			} elseif ($this->stream !== null) {
				$this->stream->write($response);
				$this->writtenFlow = $flow;
				$this->handleSend();
			}
		} elseif ($flow->isFinished()) {
			$this->eventLoop->futureTick(function () use ($flow): void {
				$this->finishFlow($flow);
			});
		}
	}

	/**
	 * Finishes the given flow
	 *
	 * @throws DevicesExceptions\Terminate
	 */
	private function finishFlow(Flow $flow): void
	{
		if ($flow->isSuccess()) {
			if (!$flow->isSilent()) {
				switch ($flow->getCode()) {
					case 'pong':
						break;
					case 'connect':
						$result = $flow->getResult();
						assert($result instanceof Mqtt\Connection);

						$this->onConnect($result);

						break;
					case 'disconnect':
						$result = $flow->getResult();
						assert($result instanceof Mqtt\Connection);

						$this->onDisconnect($result);

						break;
					case 'message':
						$result = $flow->getResult();
						assert($result instanceof Mqtt\Message);

						$this->onMessage($result);

						break;
					case 'publish':
						$result = $flow->getResult();
						assert($result instanceof Mqtt\Message);

						$this->onPublish($result);

						break;
					case 'subscribe':
						$result = $flow->getResult();
						assert($result instanceof Mqtt\Subscription);

						$this->onSubscribe($result);

						break;
					case 'unsubscribe':
						/** @var Array<Mqtt\Subscription> $result */
						$result = $flow->getResult();

						$this->onUnsubscribe($result);

						break;
				}
			}

			$flow->getDeferred()->resolve($flow->getResult());

		} else {
			$result = new Exceptions\Runtime($flow->getErrorMessage());

			$this->onWarning($result);

			$flow->getDeferred()->reject($result);
		}
	}

	/**
	 * @throws InvalidArgumentException
	 */
	private function getConnector(): Socket\ConnectorInterface
	{
		return new Socket\Connector($this->eventLoop);
	}

}
