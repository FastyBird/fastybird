<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Cases\Unit\Publishers;

use DateTime;
use DateTimeInterface;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RedisDb\Clients;
use FastyBird\Plugin\RedisDb\Publishers;
use FastyBird\Plugin\RedisDb\Utilities;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;

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
				'source' => MetadataTypes\ModuleSource::DEVICES,
				'routing_key' => MetadataTypes\RoutingKey::DEVICE_DOCUMENT_UPDATED,
				'created' => $now->format(DateTimeInterface::ATOM),
				'data' => [
					'action' => MetadataTypes\PropertyAction::SET,
					'channel' => '06a64596-ca03-478b-ad1e-4f53731e66a5',
					'property' => '60d754c2-4590-4eff-af1e-5c45f4234c7b',
					'expected_value' => 10,
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

		$publisher = new Publishers\Publisher($identifierGenerator, 'exchange_channel', $client, $dateTimeFactory);

		$publisher->publish(
			MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::DEVICES),
			MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::DEVICE_DOCUMENT_UPDATED),
			new MetadataDocuments\Actions\ActionChannelProperty(
				MetadataTypes\PropertyAction::get(MetadataTypes\PropertyAction::SET),
				Uuid\Uuid::fromString('06a64596-ca03-478b-ad1e-4f53731e66a5'),
				Uuid\Uuid::fromString('60d754c2-4590-4eff-af1e-5c45f4234c7b'),
				10,
			),
		);
	}

}
