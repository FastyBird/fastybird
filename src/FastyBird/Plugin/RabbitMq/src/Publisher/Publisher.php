<?php declare(strict_types = 1);

/**
 * Publisher.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Publisher
 * @since          1.0.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Plugin\RabbitMq\Publisher;

use FastyBird\DateTimeFactory;
use FastyBird\Plugin\RabbitMq;
use FastyBird\Plugin\RabbitMq\Connections;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\Promise;
use Throwable;

/**
 * RabbitMQ exchange publisher
 *
 * @package        FastyBird:RabbitMqPlugin!
 * @subpackage     Publisher
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Publisher implements IPublisher
{

	use Nette\SmartObject;

	/** @var Connections\IRabbitMqConnection */
	private Connections\IRabbitMqConnection $connection;

	/** @var DateTimeFactory\DateTimeFactory */
	private DateTimeFactory\DateTimeFactory $dateTimeFactory;

	/** @var Log\LoggerInterface */
	private Log\LoggerInterface $logger;

	public function __construct(
		Connections\IRabbitMqConnection $connection,
		DateTimeFactory\DateTimeFactory $dateTimeFactory,
		?Log\LoggerInterface $logger = null
	) {
		$this->connection = $connection;
		$this->dateTimeFactory = $dateTimeFactory;
		$this->logger = $logger ?? new Log\NullLogger();
	}

	/**
	 * {@inheritDoc}
	 */
	public function publish(
		string $origin,
		string $routingKey,
		array $data
	): void {
		try {
			// Compose message
			$message = Utils\Json::encode($data);

		} catch (Utils\JsonException $ex) {
			$this->logger->error('[FB:PLUGIN:RABBITMQ] Data could not be converted to message', [
				'message'   => [
					'routingKey' => $routingKey,
					'headers'    => [
						'origin' => $origin,
					],
				],
				'exception' => [
					'message' => $ex->getMessage(),
					'code'    => $ex->getCode(),
				],
			]);

			return;
		}

		$result = $this->connection->getChannel()
			->publish(
				$message,
				[
					'origin'  => $origin,
					'created' => $this->dateTimeFactory->getNow()->format(DATE_ATOM),
				],
				RabbitMqPlugin\Constants::RABBIT_MQ_MESSAGE_BUS_EXCHANGE_NAME,
				$routingKey
			);

		if (is_bool($result)) {
			if ($result) {
				$this->logger->info('[FB:PLUGIN:RABBITMQ] Received message was pushed into data exchange', [
					'message' => [
						'routingKey' => $routingKey,
						'headers'    => [
							'origin'  => $origin,
							'created' => $this->dateTimeFactory->getNow()
								->format(DATE_ATOM),
						],
						'body'       => $message,
					],
				]);
			} else {
				$this->logger->error('[FB:PLUGIN:RABBITMQ] Received message could not be pushed into data exchange', [
					'message' => [
						'routingKey' => $routingKey,
						'headers'    => [
							'origin'  => $origin,
							'created' => $this->dateTimeFactory->getNow()
								->format(DATE_ATOM),
						],
						'body'       => $message,
					],
				]);
			}

		} elseif ($result instanceof Promise\PromiseInterface) {
			$result
				->then(
					function () use ($origin, $routingKey, $message): void {
						$this->logger->info('[FB:PLUGIN:RABBITMQ] Received message was pushed into data exchange', [
							'message' => [
								'routingKey' => $routingKey,
								'headers'    => [
									'origin'  => $origin,
									'created' => $this->dateTimeFactory->getNow()
										->format(DATE_ATOM),
								],
								'body'       => $message,
							],
						]);
					},
					function (Throwable $ex) use ($origin, $routingKey, $message): void {
						$this->logger->error('[FB:PLUGIN:RABBITMQ] Received message could not be pushed into data exchange', [
							'exception' => [
								'message' => $ex->getMessage(),
								'code'    => $ex->getCode(),
							],
							'message' => [
								'routingKey' => $routingKey,
								'headers'    => [
									'origin'  => $origin,
									'created' => $this->dateTimeFactory->getNow()
										->format(DATE_ATOM),
								],
								'body'       => $message,
							],
						]);
					}
				);
		}
	}

}
