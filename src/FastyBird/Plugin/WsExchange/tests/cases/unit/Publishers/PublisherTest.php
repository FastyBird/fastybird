<?php declare(strict_types = 1);

namespace FastyBird\Plugin\WsExchange\Tests\Cases\Unit\Publishers;

use FastyBird\Library\Exchange\Entities as ExchangeEntities;
use FastyBird\Library\Exchange\Exceptions as ExchangeExceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Plugin\WsExchange\Publishers;
use FastyBird\Plugin\WsExchange\Tests\Cases\Unit\BaseTestCase;
use IPub\Phone\Exceptions as PhoneExceptions;
use IPub\WebSockets;
use IPub\WebSocketsWAMP;
use Nette;
use Nette\Utils;

final class PublisherTest extends BaseTestCase
{

	/**
	 * @throws ExchangeExceptions\InvalidState
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Nette\DI\MissingServiceException
	 * @throws Utils\JsonException
	 *
	 * @dataProvider deviceSuccessfulMessage
	 */
	public function testPublishSuccessfulDeviceMessage(
		Utils\ArrayHash $data,
		MetadataTypes\RoutingKey $routingKey,
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source,
	): void
	{
		$entityFactory = $this->container->getByType(ExchangeEntities\EntityFactory::class);

		$entity = $entityFactory->create(Utils\Json::encode($data), $routingKey);

		$linkGenerator = $this->createMock(WebSockets\Router\LinkGenerator::class);
		$linkGenerator
			->expects(self::exactly(1))
			->method('link')
			->with('Exchange:')
			->willReturn('topic-link');

		$this->mockContainerService(WebSockets\Router\LinkGenerator::class, $linkGenerator);

		$topicMock = $this->createMock(WebSocketsWAMP\Entities\Topics\Topic::class);
		$topicMock
			->expects(self::exactly(1))
			->method('broadcast')
			->with(self::callback(
				static function ($message) use (
					$source,
					$routingKey,
					$entity,
				): bool {
					$mockedData = [
						'routing_key' => $routingKey->getValue(),
						'origin' => $source->getValue(),
						'data' => $entity->toArray(),
					];

					self::assertSame(Utils\Json::encode($mockedData), $message);

					return true;
				},
			));

		$topicsStorage = $this->createMock(WebSocketsWAMP\Topics\Storage::class);
		$topicsStorage
			->expects(self::exactly(1))
			->method('hasTopic')
			->willReturn(true);

		$topicsStorage
			->expects(self::exactly(1))
			->method('getTopic')
			->willReturn($topicMock);

		$this->mockContainerService(WebSocketsWAMP\Topics\Storage::class, $topicsStorage);

		$this->expectOutputString("DEBUG: Broadcasting message to topic\r\nDEBUG: Successfully published message\r\n");

		$publisher = $this->container->getByType(Publishers\Publisher::class);
		$publisher->publish($source, $routingKey, $entity);
	}

	/**
	 * @throws ExchangeExceptions\InvalidState
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Nette\DI\MissingServiceException
	 * @throws Utils\JsonException
	 *
	 * @dataProvider devicePropertySuccessfulMessage
	 */
	public function testPublishSuccessfulDevicePropertyMessage(
		Utils\ArrayHash $data,
		MetadataTypes\RoutingKey $routingKey,
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source,
	): void
	{
		$entityFactory = $this->container->getByType(ExchangeEntities\EntityFactory::class);

		$entity = $entityFactory->create(Utils\Json::encode($data), $routingKey);

		$linkGenerator = $this->createMock(WebSockets\Router\LinkGenerator::class);
		$linkGenerator
			->expects(self::exactly(1))
			->method('link')
			->with('Exchange:')
			->willReturn('topic-link');

		$this->mockContainerService(WebSockets\Router\LinkGenerator::class, $linkGenerator);

		$topicMock = $this->createMock(WebSocketsWAMP\Entities\Topics\Topic::class);
		$topicMock
			->expects(self::exactly(1))
			->method('broadcast')
			->with(self::callback(
				static function ($message) use (
					$source,
					$routingKey,
					$entity,
				): bool {
					$mockedData = [
						'routing_key' => $routingKey->getValue(),
						'origin' => $source->getValue(),
						'data' => $entity->toArray(),
					];

					self::assertSame(Utils\Json::encode($mockedData), $message);

					return true;
				},
			));

		$topicsStorage = $this->createMock(WebSocketsWAMP\Topics\Storage::class);
		$topicsStorage
			->expects(self::exactly(1))
			->method('hasTopic')
			->willReturn(true);
		$topicsStorage
			->expects(self::exactly(1))
			->method('getTopic')
			->willReturn($topicMock);

		$this->mockContainerService(WebSocketsWAMP\Topics\Storage::class, $topicsStorage);

		$this->expectOutputString("DEBUG: Broadcasting message to topic\r\nDEBUG: Successfully published message\r\n");

		$publisher = $this->container->getByType(Publishers\Publisher::class);
		$publisher->publish($source, $routingKey, $entity);
	}

	/**
	 * @throws ExchangeExceptions\InvalidState
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Nette\DI\MissingServiceException
	 * @throws Utils\JsonException
	 *
	 * @dataProvider channelSuccessfulMessage
	 */
	public function testPublishSuccessfulChannelMessage(
		Utils\ArrayHash $data,
		MetadataTypes\RoutingKey $routingKey,
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source,
	): void
	{
		$entityFactory = $this->container->getByType(ExchangeEntities\EntityFactory::class);

		$entity = $entityFactory->create(Utils\Json::encode($data), $routingKey);

		$linkGenerator = $this->createMock(WebSockets\Router\LinkGenerator::class);
		$linkGenerator
			->expects(self::exactly(1))
			->method('link')
			->with('Exchange:')
			->willReturn('topic-link');

		$this->mockContainerService(WebSockets\Router\LinkGenerator::class, $linkGenerator);

		$topicMock = $this->createMock(WebSocketsWAMP\Entities\Topics\Topic::class);
		$topicMock
			->expects(self::exactly(1))
			->method('broadcast')
			->with(self::callback(
				static function ($message) use (
					$source,
					$routingKey,
					$entity,
				): bool {
					$mockedData = [
						'routing_key' => $routingKey->getValue(),
						'origin' => $source->getValue(),
						'data' => $entity->toArray(),
					];

					self::assertSame(Utils\Json::encode($mockedData), $message);

					return true;
				},
			));

		$topicsStorage = $this->createMock(WebSocketsWAMP\Topics\Storage::class);
		$topicsStorage
			->expects(self::exactly(1))
			->method('hasTopic')
			->willReturn(true);
		$topicsStorage
			->expects(self::exactly(1))
			->method('getTopic')
			->willReturn($topicMock);

		$this->mockContainerService(WebSocketsWAMP\Topics\Storage::class, $topicsStorage);

		$this->expectOutputString("DEBUG: Broadcasting message to topic\r\nDEBUG: Successfully published message\r\n");

		$publisher = $this->container->getByType(Publishers\Publisher::class);
		$publisher->publish($source, $routingKey, $entity);
	}

	/**
	 * @throws ExchangeExceptions\InvalidState
	 * @throws PhoneExceptions\NoValidCountryException
	 * @throws PhoneExceptions\NoValidPhoneException
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 * @throws Nette\DI\MissingServiceException
	 * @throws Utils\JsonException
	 *
	 * @dataProvider channelPropertySuccessfulMessage
	 */
	public function testPublishSuccessfulChannelPropertyMessage(
		Utils\ArrayHash $data,
		MetadataTypes\RoutingKey $routingKey,
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source,
	): void
	{
		$entityFactory = $this->container->getByType(ExchangeEntities\EntityFactory::class);

		$entity = $entityFactory->create(Utils\Json::encode($data), $routingKey);

		$linkGenerator = $this->createMock(WebSockets\Router\LinkGenerator::class);
		$linkGenerator
			->expects(self::exactly(1))
			->method('link')
			->with('Exchange:')
			->willReturn('topic-link');

		$this->mockContainerService(WebSockets\Router\LinkGenerator::class, $linkGenerator);

		$topicMock = $this->createMock(WebSocketsWAMP\Entities\Topics\Topic::class);
		$topicMock
			->expects(self::exactly(1))
			->method('broadcast')
			->with(self::callback(
				static function ($message) use (
					$source,
					$routingKey,
					$entity,
				): bool {
					$mockedData = [
						'routing_key' => $routingKey->getValue(),
						'origin' => $source->getValue(),
						'data' => $entity->toArray(),
					];

					self::assertSame(Utils\Json::encode($mockedData), $message);

					return true;
				},
			));

		$topicsStorage = $this->createMock(WebSocketsWAMP\Topics\Storage::class);
		$topicsStorage
			->expects(self::exactly(1))
			->method('hasTopic')
			->willReturn(true);
		$topicsStorage
			->expects(self::exactly(1))
			->method('getTopic')
			->willReturn($topicMock);

		$this->mockContainerService(WebSocketsWAMP\Topics\Storage::class, $topicsStorage);

		$this->expectOutputString("DEBUG: Broadcasting message to topic\r\nDEBUG: Successfully published message\r\n");

		$publisher = $this->container->getByType(Publishers\Publisher::class);
		$publisher->publish($source, $routingKey, $entity);
	}

	/**
	 * @return Array<string, Array<Utils\ArrayHash|MetadataTypes\RoutingKey|MetadataTypes\ModuleSource>>
	 */
	public function deviceSuccessfulMessage(): array
	{
		return [
			'create' => [
				Utils\ArrayHash::from([
					'id' => '633c7f7c-f73b-456f-b65f-5359c3b23d9c',
					'type' => 'custom',
					'connector' => 'de9fbeaa-cc93-42a3-88db-23a393651ee4',
					'identifier' => 'device-name',
					'name' => 'Device name',
					'title' => null,
					'comment' => null,
					'state' => 'ready',
					'enabled' => true,
				]),
				MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::ROUTE_DEVICE_ENTITY_CREATED),
				MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
			],
			'update' => [
				Utils\ArrayHash::from([
					'id' => '633c7f7c-f73b-456f-b65f-5359c3b23d9c',
					'type' => 'custom',
					'connector' => 'de9fbeaa-cc93-42a3-88db-23a393651ee4',
					'identifier' => 'device-name',
					'name' => 'Device name',
					'title' => null,
					'comment' => null,
					'state' => 'ready',
					'enabled' => true,
				]),
				MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::ROUTE_DEVICE_ENTITY_UPDATED),
				MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
			],
			'delete' => [
				Utils\ArrayHash::from([
					'id' => '633c7f7c-f73b-456f-b65f-5359c3b23d9c',
					'type' => 'custom',
					'connector' => 'de9fbeaa-cc93-42a3-88db-23a393651ee4',
					'identifier' => 'device-name',
					'name' => 'Device name',
					'title' => null,
					'comment' => null,
					'state' => 'ready',
					'enabled' => true,
				]),
				MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::ROUTE_DEVICE_ENTITY_DELETED),
				MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
			],
		];
	}

	/**
	 * @return Array<string, Array<Utils\ArrayHash|MetadataTypes\RoutingKey|MetadataTypes\ModuleSource>>
	 */
	public function devicePropertySuccessfulMessage(): array
	{
		return [
			'create' => [
				Utils\ArrayHash::from([
					'id' => 'b41efd22-42e0-4e30-aab4-de471741cd30',
					'type' => MetadataTypes\PropertyType::TYPE_DYNAMIC,
					'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
					'property' => '667e159f-ad74-4ba7-94d7-775b1930ea11',
					'identifier' => 'property-name',
					'name' => null,
					'settable' => true,
					'queryable' => true,
					'data_type' => MetadataTypes\DataType::DATA_TYPE_UNKNOWN,
					'unit' => null,
					'format' => null,
					'invalid' => null,
					'number_of_decimals' => null,
				]),
				MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_CREATED),
				MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
			],
			'update' => [
				Utils\ArrayHash::from([
					'id' => 'b41efd22-42e0-4e30-aab4-de471741cd30',
					'type' => MetadataTypes\PropertyType::TYPE_DYNAMIC,
					'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
					'property' => '667e159f-ad74-4ba7-94d7-775b1930ea11',
					'identifier' => 'property-name',
					'name' => null,
					'settable' => true,
					'queryable' => true,
					'data_type' => MetadataTypes\DataType::DATA_TYPE_UNKNOWN,
					'unit' => null,
					'format' => null,
					'invalid' => null,
					'number_of_decimals' => null,
				]),
				MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_UPDATED),
				MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
			],
			'delete' => [
				Utils\ArrayHash::from([
					'id' => 'b41efd22-42e0-4e30-aab4-de471741cd30',
					'type' => MetadataTypes\PropertyType::TYPE_DYNAMIC,
					'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
					'property' => '667e159f-ad74-4ba7-94d7-775b1930ea11',
					'identifier' => 'property-name',
					'name' => null,
					'settable' => true,
					'queryable' => true,
					'data_type' => MetadataTypes\DataType::DATA_TYPE_UNKNOWN,
					'unit' => null,
					'format' => null,
					'invalid' => null,
					'number_of_decimals' => null,
				]),
				MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::ROUTE_DEVICE_PROPERTY_ENTITY_DELETED),
				MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
			],
		];
	}

	/**
	 * @return Array<string, Array<Utils\ArrayHash|MetadataTypes\RoutingKey|MetadataTypes\ModuleSource>>
	 */
	public function channelSuccessfulMessage(): array
	{
		return [
			'create' => [
				Utils\ArrayHash::from([
					'id' => 'd627e987-a9aa-4f23-90c3-4fc38ae81ee1',
					'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
					'channel' => '740a1615-8a61-46ed-8d72-192ed20c7aed',
					'identifier' => 'channel-name',
					'name' => 'Channel custom name',
					'title' => null,
					'comment' => null,
				]),
				MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::ROUTE_CHANNEL_ENTITY_CREATED),
				MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
			],
			'update' => [
				Utils\ArrayHash::from([
					'id' => 'd627e987-a9aa-4f23-90c3-4fc38ae81ee1',
					'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
					'channel' => '740a1615-8a61-46ed-8d72-192ed20c7aed',
					'identifier' => 'channel-name',
					'name' => 'Channel custom name',
					'title' => null,
					'comment' => null,
				]),
				MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::ROUTE_CHANNEL_ENTITY_UPDATED),
				MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
			],
			'delete' => [
				Utils\ArrayHash::from([
					'id' => 'd627e987-a9aa-4f23-90c3-4fc38ae81ee1',
					'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
					'channel' => '740a1615-8a61-46ed-8d72-192ed20c7aed',
					'identifier' => 'channel-name',
					'name' => 'Channel custom name',
					'title' => null,
					'comment' => null,
				]),
				MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::ROUTE_CHANNEL_ENTITY_DELETED),
				MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
			],
		];
	}

	/**
	 * @return Array<string, Array<Utils\ArrayHash|MetadataTypes\RoutingKey|MetadataTypes\ModuleSource>>
	 */
	public function channelPropertySuccessfulMessage(): array
	{
		return [
			'create' => [
				Utils\ArrayHash::from([
					'id' => '73b106f6-bbd4-4bed-b6ac-bc4e8cab9e52',
					'type' => MetadataTypes\PropertyType::TYPE_DYNAMIC,
					'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
					'channel' => '740a1615-8a61-46ed-8d72-192ed20c7aed',
					'property' => '667e159f-ad74-4ba7-94d7-775b1930ea11',
					'identifier' => 'property-name',
					'name' => null,
					'settable' => true,
					'queryable' => true,
					'data_type' => MetadataTypes\DataType::DATA_TYPE_UNKNOWN,
					'unit' => null,
					'format' => null,
					'invalid' => null,
					'number_of_decimals' => null,
				]),
				MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_CREATED),
				MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
			],
			'update' => [
				Utils\ArrayHash::from([
					'id' => '73b106f6-bbd4-4bed-b6ac-bc4e8cab9e52',
					'type' => MetadataTypes\PropertyType::TYPE_DYNAMIC,
					'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
					'channel' => '740a1615-8a61-46ed-8d72-192ed20c7aed',
					'property' => '667e159f-ad74-4ba7-94d7-775b1930ea11',
					'identifier' => 'property-name',
					'name' => null,
					'settable' => true,
					'queryable' => true,
					'data_type' => MetadataTypes\DataType::DATA_TYPE_UNKNOWN,
					'unit' => null,
					'format' => null,
					'invalid' => null,
					'number_of_decimals' => null,
				]),
				MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_UPDATED),
				MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
			],
			'delete' => [
				Utils\ArrayHash::from([
					'id' => '73b106f6-bbd4-4bed-b6ac-bc4e8cab9e52',
					'type' => MetadataTypes\PropertyType::TYPE_DYNAMIC,
					'device' => '6a6a8525-eb24-4f48-8698-4f446b4a2664',
					'channel' => '740a1615-8a61-46ed-8d72-192ed20c7aed',
					'property' => '667e159f-ad74-4ba7-94d7-775b1930ea11',
					'identifier' => 'property-name',
					'name' => null,
					'settable' => true,
					'queryable' => true,
					'data_type' => MetadataTypes\DataType::DATA_TYPE_UNKNOWN,
					'unit' => null,
					'format' => null,
					'invalid' => null,
					'number_of_decimals' => null,
				]),
				MetadataTypes\RoutingKey::get(MetadataTypes\RoutingKey::ROUTE_CHANNEL_PROPERTY_ENTITY_DELETED),
				MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES),
			],
		];
	}

}
