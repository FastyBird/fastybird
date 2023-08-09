<?php declare(strict_types = 1);

/**
 * Client.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Clients
 * @since          1.0.0
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
use FastyBird\Connector\FbMqtt\Writers;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
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
use function sprintf;

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

	protected bool $isConnected = false;

	protected bool $isConnecting = false;

	protected bool $isDisconnecting = false;

	/** @var Closure(Mqtt\Connection $connection): void|null */
	protected Closure|null $onCloseCallback = null;

	/** @var array<EventLoop\TimerInterface> */
	protected array $timer = [];

	/** @var array<Flow> */
	protected array $receivingFlows = [];

	/** @var array<Flow> */
	protected array $sendingFlows = [];

	protected Flow|null $writtenFlow = null;

	protected Stream\DuplexStreamInterface|null $stream = null;

	protected Mqtt\StreamParser $parser;

	protected Mqtt\ClientIdentifierGenerator $identifierGenerator;

	protected Mqtt\Connection|null $connection = null;

	protected Mqtt\FlowFactory $flowFactory;

	public function __construct(
		protected readonly Entities\FbMqttConnector $connector,
		protected readonly Consumers\Messages $consumer,
		protected readonly Writers\Writer $writer,
		protected readonly EventLoop\LoopInterface $eventLoop,
		Mqtt\ClientIdentifierGenerator|null $identifierGenerator = null,
		Mqtt\FlowFactory|null $flowFactory = null,
		Mqtt\StreamParser|null $parser = null,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
	)
	{
		$this->parser = $parser ?? new Mqtt\StreamParser(new Mqtt\DefaultPacketFactory());

		$this->parser->onError(function (Throwable $ex): void {
			$this->onWarning($ex);
		});

		$this->identifierGenerator = $identifierGenerator ?? new Mqtt\DefaultIdentifierGenerator();

		$this->flowFactory = $flowFactory ?? new Mqtt\DefaultFlowFactory(
			$this->identifierGenerator,
			new Mqtt\DefaultIdentifierGenerator(),
			new Mqtt\DefaultPacketFactory(),
		);
	}

	/**
	 * Write data to DPS
	 */
	abstract public function writeDeviceProperty(
		Entities\FbMqttDevice $device,
		DevicesEntities\Devices\Properties\Dynamic|MetadataEntities\DevicesModule\DeviceDynamicProperty $property,
	): Promise\PromiseInterface;

	/**
	 * Write data to DPS
	 */
	abstract public function writeChannelProperty(
		Entities\FbMqttDevice $device,
		DevicesEntities\Channels\Channel $channel,
		DevicesEntities\Channels\Properties\Dynamic|MetadataEntities\DevicesModule\ChannelDynamicProperty $property,
	): Promise\PromiseInterface;

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

		$this->writer->connect($this->connector, $this);

		if ($this->isConnected || $this->isConnecting) {
			$promise = Promise\reject(new Exceptions\Logic('The client is already connected'));
			assert($promise instanceof Promise\ExtendedPromiseInterface);

			return $promise;
		}

		$this->isConnecting = true;
		$this->isConnected = false;

		$connection = new Mqtt\DefaultConnection(
			$this->connector->getUsername() ?? '',
			$this->connector->getPassword() ?? '',
			null,
			$this->connector->getPlainId(),
		);

		if ($connection->getClientID() === '') {
			$connection = $connection->withClientID($this->identifierGenerator->generateClientIdentifier());
		}

		$this->establishConnection($this->connector->getServerAddress(), $this->connector->getServerPort(), $timeout)
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
					->otherwise(function (Throwable $ex) use ($connection, $deferred): void {
						$this->isConnecting = false;

						$this->onError($ex);

						$deferred->reject($ex);

						$this->stream?->close();

						$this->onClose($connection);
					});
			})
			->otherwise(function (Throwable $ex) use ($deferred): void {
				$this->isConnecting = false;

				$this->onError($ex);

				$deferred->reject($ex);
			});

		$promise = $deferred->promise();
		assert($promise instanceof Promise\ExtendedPromiseInterface);

		return $promise;
	}

	/**
	 * Disconnects from a broker
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

		$this->writer->disconnect($this->connector, $this);

		return $promise;
	}

	/**
	 * Subscribes to a topic filter
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

	public function publish(
		string $topic,
		string|null $payload = null,
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
	 * @param array<Mqtt\Subscription> $subscriptions
	 */
	protected function onUnsubscribe(array $subscriptions): void
	{
		// TODO: Implement onUnsubscribe() method.
	}

	protected function onPublish(Mqtt\Message $message): void
	{
		// TODO: Implement onPublish() method.
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

					$stream->on('error', function (Throwable $ex): void {
						$this->handleError($ex);
					});

					$deferred->resolve($stream);
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});
		}

		$promise = $deferred->promise();
		assert($promise instanceof Promise\ExtendedPromiseInterface);

		return $promise;
	}

	/**
	 * Registers a new client with the broker
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
			->otherwise(static function (Throwable $ex) use ($deferred): void {
				$deferred->reject($ex);
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

		throw new DevicesExceptions\Terminate(
			'There was an error during handling requests',
			$error->getCode(),
			$error,
		);
	}

	/**
	 * Starts the given flow
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

		} catch (Throwable $ex) {
			$this->onError($ex);

			throw new DevicesExceptions\Terminate(
				'There was an error during handling requests',
				$ex->getCode(),
				$ex,
			);
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
						/** @var array<Mqtt\Subscription> $result */
						$result = $flow->getResult();

						$this->onUnsubscribe($result);

						break;
				}
			}

			$flow->getDeferred()->resolve($flow->getResult());

		} else {
			$result = new Exceptions\Runtime($flow->getErrorMessage());

			$flow->getDeferred()->reject($result);

			$this->onWarning($result);
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
