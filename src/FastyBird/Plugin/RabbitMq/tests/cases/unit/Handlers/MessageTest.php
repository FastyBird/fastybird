<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Tests\Cases\Unit\Connections;

use FastyBird\Plugin\RabbitMq\Connections;
use PHPUnit\Framework\TestCase;

final class MessageTest extends TestCase
{

	public function testEmptyHandlers(): void
	{
		$loader = Mockery::mock(ModulesMetadataLoaders\ISchemaLoader::class);

		$validator = Mockery::mock(ModulesMetadataSchemas\IValidator::class);

		$dispatcher = Mockery::mock(EventDispatcher\EventDispatcherInterface::class);

		$consumerProxy = new Consumer\ConsumerProxy($loader, $validator, $dispatcher);

		$message = new Bunny\Message(
			'consumerTag',
			'deliveryTag',
			'redelivered',
			'exchange',
			'',
			[],
			''
		);

		Assert::equal(Consumer\IConsumer::MESSAGE_REJECT, $consumerProxy->consume($message));
	}

	public function testNotSetQueueName(): void
	{
		$loader = Mockery::mock(ModulesMetadataLoaders\ISchemaLoader::class);

		$validator = Mockery::mock(ModulesMetadataSchemas\IValidator::class);

		$dispatcher = Mockery::mock(EventDispatcher\EventDispatcherInterface::class);

		$consumerProxy = new Consumer\ConsumerProxy($loader, $validator, $dispatcher);

		Assert::null($consumerProxy->getQueueName());
	}

	public function testSetQueueName(): void
	{
		$loader = Mockery::mock(ModulesMetadataLoaders\ISchemaLoader::class);

		$validator = Mockery::mock(ModulesMetadataSchemas\IValidator::class);

		$dispatcher = Mockery::mock(EventDispatcher\EventDispatcherInterface::class);

		$consumerProxy = new Consumer\ConsumerProxy($loader, $validator, $dispatcher);

		$consumerProxy->setQueueName('queueNameSet');

		Assert::equal('queueNameSet', $consumerProxy->getQueueName());
	}

	/**
	 * @param mixed[] $data
	 *
	 * @dataProvider ./../../../fixtures/Handlers/consumeValidMessage.php
	 */
	public function testConsumeNoOriginMessage(
		array $data
	): void {
		try {
			$body = Utils\Json::encode($data);

		} catch (Utils\JsonException $ex) {
			throw new Exceptions\InvalidState('Test data could not be prepared');
		}

		$loader = Mockery::mock(ModulesMetadataLoaders\ISchemaLoader::class);

		$validator = Mockery::mock(ModulesMetadataSchemas\IValidator::class);

		$dispatcher = Mockery::mock(EventDispatcher\EventDispatcherInterface::class);

		$consumerProxy = new Consumer\ConsumerProxy($loader, $validator, $dispatcher);

		$consumer = Mockery::mock(ApplicationExchangeConsumer\IConsumer::class);

		$consumerProxy->registerConsumer($consumer);

		$message = new Bunny\Message(
			'consumerTag',
			'deliveryTag',
			'redelivered',
			'exchange',
			'routing.key.one',
			[],
			$body
		);

		Assert::equal(Consumer\IConsumer::MESSAGE_REJECT, $consumerProxy->consume($message));
	}

	/**
	 * @param mixed[] $data
	 *
	 * @dataProvider ./../../../fixtures/Handlers/consumeValidMessage.php
	 */
	public function testConsumeValidOriginMessage(
		array $data
	): void {
		try {
			$body = Utils\Json::encode($data);

		} catch (Utils\JsonException $ex) {
			throw new Exceptions\InvalidState('Test data could not be prepared');
		}

		$schema = '{key: value}';

		$loader = Mockery::mock(ModulesMetadataLoaders\ISchemaLoader::class);
		$loader
			->shouldReceive('load')
			->andReturn($schema)
			->getMock();

		$validator = Mockery::mock(ModulesMetadataSchemas\IValidator::class);
		$validator
			->shouldReceive('validate')
			->withArgs([$body, $schema])
			->andReturn(Utils\ArrayHash::from($data))
			->getMock();

		$validator
			->shouldReceive('validate')
			->withArgs([$body, $schema])
			->andReturn(Utils\ArrayHash::from($data))
			->getMock();

		$dispatcher = Mockery::mock(EventDispatcher\EventDispatcherInterface::class);
		$dispatcher
			->shouldReceive('dispatch');

		$consumerProxy = new Consumer\ConsumerProxy($loader, $validator, $dispatcher);

		$consumer = Mockery::mock(ApplicationExchangeConsumer\IConsumer::class);
		$consumer
			->shouldReceive('consume')
			->withArgs(function (string $origin, string $routingKey, Utils\ArrayHash $receivedData) use ($data): bool {
				Assert::same('routing.key.one', $routingKey);
				Assert::same('test.origin', $origin);
				Assert::equal($receivedData, Utils\ArrayHash::from($data));

				return true;
			})
			->andReturn(true)
			->times(1);

		$consumerProxy->registerConsumer($consumer);

		$message = new Bunny\Message(
			'consumerTag',
			'deliveryTag',
			'redelivered',
			'exchange',
			'routing.key.one',
			[
				'origin' => 'test.origin',
			],
			$body
		);

		Assert::equal(Consumer\IConsumer::MESSAGE_ACK, $consumerProxy->consume($message));
	}

	/**
	 * @param mixed[] $data
	 *
	 * @dataProvider ./../../../fixtures/Handlers/consumeValidMessage.php
	 */
	public function testConsumeInvalidMessage(
		array $data
	): void {
		try {
			$body = Utils\Json::encode($data);

		} catch (Utils\JsonException $ex) {
			throw new Exceptions\InvalidState('Test data could not be prepared');
		}

		$message = new Bunny\Message(
			'consumerTag',
			'deliveryTag',
			'redelivered',
			'exchange',
			'routing.key.one',
			[
				'origin' => 'test.origin',
			],
			$body
		);

		$schema = '{key: value}';

		$loader = Mockery::mock(ModulesMetadataLoaders\ISchemaLoader::class);
		$loader
			->shouldReceive('load')
			->andReturn($schema)
			->getMock();

		$validator = Mockery::mock(ModulesMetadataSchemas\IValidator::class);
		$validator
			->shouldReceive('validate')
			->withArgs([$body, $schema])
			->andReturn(Utils\ArrayHash::from($data))
			->getMock();

		$dispatcher = Mockery::mock(EventDispatcher\EventDispatcherInterface::class);
		$dispatcher
			->shouldReceive('dispatch');

		$consumerProxy = new Consumer\ConsumerProxy($loader, $validator, $dispatcher);

		$consumer = Mockery::mock(ApplicationExchangeConsumer\IConsumer::class);
		$consumer
			->shouldReceive('consume')
			->withArgs(function (string $origin, string $routingKey, Utils\ArrayHash $receivedData) use ($data): bool {
				Assert::same('routing.key.one', $routingKey);
				Assert::same('test.origin', $origin);
				Assert::equal($receivedData, Utils\ArrayHash::from($data));

				return true;
			})
			->andThrow(new Exceptions\InvalidState('Could not handle message'))
			->times(1);

		$consumerProxy->registerConsumer($consumer);

		Assert::equal(Consumer\IConsumer::MESSAGE_REJECT, $consumerProxy->consume($message));
	}

	public function testConsumeUnknownSchema(): void
	{
		$message = new Bunny\Message(
			'consumerTag',
			'deliveryTag',
			'redelivered',
			'exchange',
			'routing.key.one',
			[
				'origin' => 'test.origin',
			],
			''
		);

		$loader = Mockery::mock(ModulesMetadataLoaders\ISchemaLoader::class);
		$loader
			->shouldReceive('load')
			->andThrow(new ModulesMetadataExceptions\InvalidArgumentException('Message schema not found'))
			->getMock();

		$validator = Mockery::mock(ModulesMetadataSchemas\IValidator::class);

		$dispatcher = Mockery::mock(EventDispatcher\EventDispatcherInterface::class);

		$consumerProxy = new Consumer\ConsumerProxy($loader, $validator, $dispatcher);

		$consumer = Mockery::mock(ApplicationExchangeConsumer\IConsumer::class);

		$consumerProxy->registerConsumer($consumer);

		Assert::equal(Consumer\IConsumer::MESSAGE_REJECT, $consumerProxy->consume($message));
	}

}
