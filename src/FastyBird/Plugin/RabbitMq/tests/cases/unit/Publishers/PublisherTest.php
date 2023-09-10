<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Tests\Cases\Unit\Publishers;

use DateTime;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\RabbitMq\Channels;
use FastyBird\Plugin\RabbitMq\Publishers;
use FastyBird\Plugin\RabbitMq\Utilities;
use Nette;
use Nette\Utils;
use PHPUnit\Framework\TestCase;
use Psr\Log;
use const DATE_ATOM;

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
					'action' => MetadataTypes\PropertyAction::ACTION_SET,
					'property' => '60d754c2-4590-4eff-af1e-5c45f4234c7b',
					'expected_value' => 10,
					'device' => '593397b2-fd40-4da2-a66a-3687ca50761b',
					'channel' => '06a64596-ca03-478b-ad1e-4f53731e66a5',
				]),
				[
					'sender_id' => 'rabbitmq_client_identifier',
					'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
					'created' => $now->format(DATE_ATOM),
				],
				'exchange_name',
				MetadataTypes\RoutingKey::ROUTE_DEVICE_ENTITY_UPDATED,
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
