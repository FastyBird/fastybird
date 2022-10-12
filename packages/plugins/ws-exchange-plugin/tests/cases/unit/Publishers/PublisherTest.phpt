<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use FastyBird\Exchange\Entities as ExchangeEntities;
use FastyBird\Metadata;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\WsExchangePlugin\Publishers;
use IPub\WebSockets;
use IPub\WebSocketsWAMP;
use Mockery;
use Nette\Utils;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../BaseTestCase.php';

/**
 * @testCase
 */
final class PublisherTest extends BaseTestCase
{

	/**
	 * @param Utils\ArrayHash $data
	 * @param Metadata\Types\RoutingKey $routingKey
	 * @param MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source
	 *
	 * @dataProvider ./../../../fixtures/Publishers/deviceSuccessfulMessage.php
	 */
	public function testPublishSuccessfulDeviceMessage(
		Utils\ArrayHash $data,
		Metadata\Types\RoutingKey $routingKey,
		$source
	): void {
		$entityFactory = $this->container->getByType(ExchangeEntities\EntityFactory::class);

		$entity = $entityFactory->create(Utils\Json::encode($data), $routingKey);

		$linkGenerator = Mockery::mock(WebSockets\Router\LinkGenerator::class);
		$linkGenerator
			->shouldReceive('link')
			->with('Exchange:')
			->andReturn('topic-link')
			->times(1);

		$this->mockContainerService(WebSockets\Router\LinkGenerator::class, $linkGenerator);

		$topicMock = Mockery::mock(WebSocketsWAMP\Entities\Topics\Topic::class);
		$topicMock
			->shouldReceive('broadcast')
			->withArgs(
				function ($message) use (
					$source,
					$routingKey,
					$entity
				): bool {
					$mockedData = [
						'routing_key' => $routingKey->getValue(),
						'origin'      => $source->getValue(),
						'data'        => $entity->toArray(),
					];

					Assert::same(Utils\Json::encode($mockedData), $message);

					return true;
				}
			)
			->times(1);

		$topicsStorage = Mockery::mock(WebSocketsWAMP\Topics\Storage::class);
		$topicsStorage
			->shouldReceive('hasTopic')
			->andReturn(true)
			->times(1)
			->getMock()
			->shouldReceive('getTopic')
			->andReturn($topicMock)
			->times(1);

		$this->mockContainerService(WebSocketsWAMP\Topics\Storage::class, $topicsStorage);

		$publisher = $this->container->getByType(Publishers\Publisher::class);
		$publisher->publish($source, $routingKey, $entity);
	}

	/**
	 * @param Utils\ArrayHash $data
	 * @param Metadata\Types\RoutingKey $routingKey
	 * @param MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source
	 *
	 * @dataProvider ./../../../fixtures/Publishers/devicePropertySuccessfulMessage.php
	 */
	public function testPublishSuccessfulDevicePropertyMessage(
		Utils\ArrayHash $data,
		Metadata\Types\RoutingKey $routingKey,
		$source
	): void {
		$entityFactory = $this->container->getByType(ExchangeEntities\EntityFactory::class);

		$entity = $entityFactory->create(Utils\Json::encode($data), $routingKey);

		$linkGenerator = Mockery::mock(WebSockets\Router\LinkGenerator::class);
		$linkGenerator
			->shouldReceive('link')
			->with('Exchange:')
			->andReturn('topic-link')
			->times(1);

		$this->mockContainerService(WebSockets\Router\LinkGenerator::class, $linkGenerator);

		$topicMock = Mockery::mock(WebSocketsWAMP\Entities\Topics\Topic::class);
		$topicMock
			->shouldReceive('broadcast')
			->withArgs(
				function ($message) use (
					$source,
					$routingKey,
					$entity
				): bool {
					$mockedData = [
						'routing_key' => $routingKey->getValue(),
						'origin'      => $source->getValue(),
						'data'        => $entity->toArray(),
					];

					Assert::same(Utils\Json::encode($mockedData), $message);

					return true;
				}
			)
			->times(1);

		$topicsStorage = Mockery::mock(WebSocketsWAMP\Topics\Storage::class);
		$topicsStorage
			->shouldReceive('hasTopic')
			->andReturn(true)
			->times(1)
			->getMock()
			->shouldReceive('getTopic')
			->andReturn($topicMock)
			->times(1);

		$this->mockContainerService(WebSocketsWAMP\Topics\Storage::class, $topicsStorage);

		$publisher = $this->container->getByType(Publishers\Publisher::class);
		$publisher->publish($source, $routingKey, $entity);
	}

	/**
	 * @param Utils\ArrayHash $data
	 * @param Metadata\Types\RoutingKey $routingKey
	 * @param MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source
	 *
	 * @dataProvider ./../../../fixtures/Publishers/channelSuccessfulMessage.php
	 */
	public function testPublishSuccessfulChannelMessage(
		Utils\ArrayHash $data,
		Metadata\Types\RoutingKey $routingKey,
		$source
	): void {
		$entityFactory = $this->container->getByType(ExchangeEntities\EntityFactory::class);

		$entity = $entityFactory->create(Utils\Json::encode($data), $routingKey);

		$linkGenerator = Mockery::mock(WebSockets\Router\LinkGenerator::class);
		$linkGenerator
			->shouldReceive('link')
			->with('Exchange:')
			->andReturn('topic-link')
			->times(1);

		$this->mockContainerService(WebSockets\Router\LinkGenerator::class, $linkGenerator);

		$topicMock = Mockery::mock(WebSocketsWAMP\Entities\Topics\Topic::class);
		$topicMock
			->shouldReceive('broadcast')
			->withArgs(
				function ($message) use (
					$source,
					$routingKey,
					$entity
				): bool {
					$mockedData = [
						'routing_key' => $routingKey->getValue(),
						'origin'      => $source->getValue(),
						'data'        => $entity->toArray(),
					];

					Assert::same(Utils\Json::encode($mockedData), $message);

					return true;
				}
			)
			->times(1);

		$topicsStorage = Mockery::mock(WebSocketsWAMP\Topics\Storage::class);
		$topicsStorage
			->shouldReceive('hasTopic')
			->andReturn(true)
			->times(1)
			->getMock()
			->shouldReceive('getTopic')
			->andReturn($topicMock)
			->times(1);

		$this->mockContainerService(WebSocketsWAMP\Topics\Storage::class, $topicsStorage);

		$publisher = $this->container->getByType(Publishers\Publisher::class);
		$publisher->publish($source, $routingKey, $entity);
	}

	/**
	 * @param Utils\ArrayHash $data
	 * @param Metadata\Types\RoutingKey $routingKey
	 * @param MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource $source
	 *
	 * @dataProvider ./../../../fixtures/Publishers/channelPropertySuccessfulMessage.php
	 */
	public function testPublishSuccessfulChannelPropertyMessage(
		Utils\ArrayHash $data,
		Metadata\Types\RoutingKey $routingKey,
		$source
	): void {
		$entityFactory = $this->container->getByType(ExchangeEntities\EntityFactory::class);

		$entity = $entityFactory->create(Utils\Json::encode($data), $routingKey);

		$linkGenerator = Mockery::mock(WebSockets\Router\LinkGenerator::class);
		$linkGenerator
			->shouldReceive('link')
			->with('Exchange:')
			->andReturn('topic-link')
			->times(1);

		$this->mockContainerService(WebSockets\Router\LinkGenerator::class, $linkGenerator);

		$topicMock = Mockery::mock(WebSocketsWAMP\Entities\Topics\Topic::class);
		$topicMock
			->shouldReceive('broadcast')
			->withArgs(
				function ($message) use (
					$source,
					$routingKey,
					$entity
				): bool {
					$mockedData = [
						'routing_key' => $routingKey->getValue(),
						'origin'      => $source->getValue(),
						'data'        => $entity->toArray(),
					];

					Assert::same(Utils\Json::encode($mockedData), $message);

					return true;
				}
			)
			->times(1);

		$topicsStorage = Mockery::mock(WebSocketsWAMP\Topics\Storage::class);
		$topicsStorage
			->shouldReceive('hasTopic')
			->andReturn(true)
			->times(1)
			->getMock()
			->shouldReceive('getTopic')
			->andReturn($topicMock)
			->times(1);

		$this->mockContainerService(WebSocketsWAMP\Topics\Storage::class, $topicsStorage);

		$publisher = $this->container->getByType(Publishers\Publisher::class);
		$publisher->publish($source, $routingKey, $entity);
	}

}

$test_case = new PublisherTest();
$test_case->run();
