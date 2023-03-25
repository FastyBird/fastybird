<?php declare(strict_types = 1);

/**
 * ConsumerProxy.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Consumer
 * @since          1.0.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Plugin\RabbitMq\Consumer;

use Bunny;
use FastyBird\ApplicationExchange\Consumer as ApplicationExchangeConsumer;
use FastyBird\ApplicationExchange\Events as ApplicationExchangeEvents;
use FastyBird\ModulesMetadata\Exceptions as ModulesMetadataExceptions;
use FastyBird\ModulesMetadata\Loaders as ModulesMetadataLoaders;
use FastyBird\ModulesMetadata\Schemas as ModulesMetadataSchemas;
use FastyBird\Plugin\RabbitMq\Exceptions;
use Nette;
use Nette\Utils;
use Psr\Log;
use SplObjectStorage;
use Symfony\Contracts\EventDispatcher;
use Throwable;

/**
 * Exchange message consumer proxy
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Consumer
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConsumerProxy implements IConsumer
{

	use Nette\SmartObject;

	/** @var string|null */
	private ?string $queueName = null;

	/** @var SplObjectStorage<ApplicationExchangeConsumer\IConsumer, null> */
	private SplObjectStorage $consumers;

	/** @var ModulesMetadataLoaders\ISchemaLoader */
	private ModulesMetadataLoaders\ISchemaLoader $schemaLoader;

	/** @var ModulesMetadataSchemas\IValidator */
	private ModulesMetadataSchemas\IValidator $validator;

	/** @var EventDispatcher\EventDispatcherInterface */
	private $dispatcher;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		ModulesMetadataLoaders\ISchemaLoader $schemaLoader,
		ModulesMetadataSchemas\IValidator $validator,
		EventDispatcher\EventDispatcherInterface $dispatcher,
		?Log\LoggerInterface $logger = null
	) {
		$this->schemaLoader = $schemaLoader;
		$this->validator = $validator;

		$this->dispatcher = $dispatcher;

		$this->consumers = new SplObjectStorage();

		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getQueueName(): ?string
	{
		return $this->queueName;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setQueueName(?string $queueName): void
	{
		$this->queueName = $queueName;
	}

	/**
	 * {@inheritDoc}
	 */
	public function registerConsumer(ApplicationExchangeConsumer\IConsumer $consumer): void
	{
		if (!$this->consumers->contains($consumer)) {
			$this->consumers->attach($consumer);
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\TerminateException
	 */
	public function consume(Bunny\Message $message): int
	{
		if (!$message->hasHeader('origin') || !is_string($message->getHeader('origin'))) {
			return self::MESSAGE_REJECT;
		}

		$routingKey = $message->routingKey;
		$origin = $message->getHeader('origin');
		$payload = $message->content;

		try {
			$schema = $this->schemaLoader->load($origin, $routingKey);

		} catch (ModulesMetadataExceptions\InvalidArgumentException $ex) {
			return self::MESSAGE_REJECT;
		}

		try {
			$data = $this->validator->validate($payload, $schema);

		} catch (Throwable $ex) {
			return self::MESSAGE_REJECT;
		}

		/** @var ApplicationExchangeConsumer\IConsumer $consumer */
		foreach ($this->consumers as $consumer) {
			try {
				$this->processMessage($origin, $routingKey, $data, $consumer);

			} catch (Exceptions\UnprocessableMessageException $ex) {
				// Log error consume reason
				$this->logger->error('[FB:PLUGIN:RABBITMQ] Message could not be consumed', [
					'exception' => [
						'message' => $ex->getMessage(),
						'code'    => $ex->getCode(),
					],
				]);

				return self::MESSAGE_REJECT;
			}
		}

		$this->dispatcher->dispatch(new ApplicationExchangeEvents\MessageConsumedEvent($origin, $routingKey, $data));

		return self::MESSAGE_ACK;
	}

	/**
	 * @param string $origin
	 * @param string $routingKey
	 * @param Utils\ArrayHash $data
	 * @param ApplicationExchangeConsumer\IConsumer $consumer
	 *
	 * @return void
	 *
	 * @throws Exceptions\TerminateException
	 */
	private function processMessage(
		string $origin,
		string $routingKey,
		Utils\ArrayHash $data,
		ApplicationExchangeConsumer\IConsumer $consumer
	): void {
		try {
			$consumer->consume($origin, $routingKey, $data);

		} catch (Exceptions\TerminateException $ex) {
			throw $ex;

		} catch (Bunny\Exception\ClientException $ex) {
			throw new Exceptions\TerminateException($ex->getMessage(), $ex->getCode(), $ex);

		} catch (Throwable $ex) {
			throw new Exceptions\UnprocessableMessageException('Received message could not be consumed', $ex->getCode(), $ex);
		}
	}

}
