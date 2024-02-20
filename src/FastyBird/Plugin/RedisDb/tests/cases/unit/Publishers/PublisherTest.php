<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Cases\Unit\Publishers;

use DateTime;
use DateTimeInterface;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Publishers;
use FastyBird\Plugin\RedisDb\Tests\Fixtures;
use FastyBird\Plugin\RedisDb\Utilities;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\TestCase;

final class PublisherTest extends TestCase
{

	/**
	 * @throws Utils\JsonException
	 */
	public function testPublish(): void
	{
		$now = new DateTime();

		$client = $this->createMock(Clients\Client::class);
		$client
			->expects(self::once())
			->method('publish')
			->with('exchange_channel', Nette\Utils\Json::encode([
				'sender_id' => 'redis_client_identifier',
				'source' => MetadataTypes\Sources\Module::DEVICES->value,
				'routing_key' => 'testing.routing.key',
				'created' => $now->format(DateTimeInterface::ATOM),
				'data' => [
					'attribute' => 'someAttribute',
					'value' => 10,
				],
			]))
			->willReturn(true);

		$dateTimeFactory = $this->createMock(DateTimeFactory\Factory::class);
		$dateTimeFactory
			->expects(self::once())
			->method('getNow')
			->willReturn($now);

		$identifierGenerator = $this->createMock(Utilities\IdentifierGenerator::class);
		$identifierGenerator
			->expects(self::once())
			->method('getIdentifier')
			->willReturn('redis_client_identifier');

		$publisher = new Publishers\Publisher(
			$identifierGenerator,
			'exchange_channel',
			$client,
			$dateTimeFactory,
		);

		$publisher->publish(
			MetadataTypes\Sources\Module::DEVICES,
			'testing.routing.key',
			new Fixtures\Dummy\DummyDocument(
				'someAttribute',
				10,
			),
		);
	}

}
