<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Tests\Cases\Unit\Publishers;

use DateTime;
use DateTimeInterface;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RabbitMq\Channels;
use FastyBird\Plugin\RabbitMq\Publishers;
use FastyBird\Plugin\RabbitMq\Tests\Fixtures;
use FastyBird\Plugin\RabbitMq\Utilities;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\TestCase;
use Psr\Log;

final class PublisherTest extends TestCase
{

	/**
	 * @throws Utils\JsonException
	 */
	public function testPublishMessage(): void
	{
		$now = new DateTime();

		$channel = $this->createMock(Channels\Channel::class);
		$channel
			->expects(self::once())
			->method('publish')
			->with(
				Nette\Utils\Json::encode([
					'attribute' => 'someAttribute',
					'value' => 10,
				]),
				[
					'sender_id' => 'rabbitmq_client_identifier',
					'source' => MetadataTypes\Sources\Module::DEVICES,
					'created' => $now->format(DateTimeInterface::ATOM),
				],
				'exchange_name',
				'testing.routing.key',
			)
			->willReturn(true);

		$dateTimeFactory = $this->createMock(DateTimeFactory\Factory::class);
		$dateTimeFactory
			->expects(self::once())
			->method('getNow')
			->willReturn($now);

		$logger = $this->createMock(Log\LoggerInterface::class);
		$logger
			->expects(self::once())
			->method('debug')
			->with(self::callback(static function ($message): bool {
				self::assertSame('Received message was pushed into data exchange', $message);

				return true;
			}));

		$identifierGenerator = $this->createMock(Utilities\IdentifierGenerator::class);
		$identifierGenerator
			->expects(self::once())
			->method('getIdentifier')
			->willReturn('rabbitmq_client_identifier');

		$publisher = new Publishers\Publisher(
			'exchange_name',
			$channel,
			$identifierGenerator,
			$dateTimeFactory,
			$logger,
		);

		$publisher->publish(
			MetadataTypes\Sources\Module::get(MetadataTypes\Sources\Module::DEVICES),
			'testing.routing.key',
			new Fixtures\Dummy\DummyDocument(
				'someAttribute',
				10,
			),
		);
	}

}
