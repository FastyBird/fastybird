<?php declare(strict_types = 1);

namespace FastyBird\RedisDbPlugin\Tests\Cases\Unit\Publisher;

use DateTime;
use FastyBird\DateTimeFactory;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\RedisDbPlugin\Client;
use FastyBird\RedisDbPlugin\Publishers;
use FastyBird\RedisDbPlugin\Utils;
use Nette;
use PHPUnit\Framework\TestCase;
use const DATE_ATOM;

final class PublisherTest extends TestCase
{

	/**
	 * @throws Nette\Utils\JsonException
	 */
	public function testPublish(): void
	{
		$now = new DateTime();

		$client = $this->createMock(Client\Client::class);
		$client
			->expects(self::once())
			->method('publish')
			->with('exchange_channel', Nette\Utils\Json::encode([
				'sender_id' => 'redis_client_identifier',
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
				'routing_key' => MetadataTypes\RoutingKey::ROUTE_DEVICE_ENTITY_UPDATED,
				'created' => $now->format(DATE_ATOM),
				'data' => [
					'action' => MetadataTypes\PropertyAction::ACTION_SET,
					'property' => '60d754c2-4590-4eff-af1e-5c45f4234c7b',
					'expected_value' => 10,
					'device' => '593397b2-fd40-4da2-a66a-3687ca50761b',
					'channel' => '06a64596-ca03-478b-ad1e-4f53731e66a5',
				],
			]))
			->willReturn(true);

		$dateTimeFactory = $this->createMock(DateTimeFactory\Factory::class);
		$dateTimeFactory
			->expects(self::once())
			->method('getNow')
			->willReturn($now);

		$identifierGenerator = $this->createMock(Utils\IdentifierGenerator::class);
		$identifierGenerator
			->expects(self::once())
			->method('getIdentifier')
			->willReturn('redis_client_identifier');

		$publisher = new Publishers\Publisher($identifierGenerator, 'exchange_channel', $client, $dateTimeFactory);

		$publisher->publish(
			MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
			MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::ROUTE_DEVICE_ENTITY_UPDATED),
			new MetadataEntities\Actions\ActionChannelProperty(
				MetadataTypes\PropertyAction::ACTION_SET,
				'593397b2-fd40-4da2-a66a-3687ca50761b',
				'06a64596-ca03-478b-ad1e-4f53731e66a5',
				'60d754c2-4590-4eff-af1e-5c45f4234c7b',
				10,
			),
		);
	}

}
