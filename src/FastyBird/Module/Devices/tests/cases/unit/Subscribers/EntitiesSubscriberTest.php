<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Subscribers;

use Doctrine\ORM;
use FastyBird\Library\Exchange\Entities as ExchangeEntities;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Devices\DataStorage;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Subscribers;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;
use stdClass;

final class EntitiesSubscriberTest extends TestCase
{

	public function testSubscriberEvents(): void
	{
		$publisher = $this->createMock(ExchangePublisher\Publisher::class);

		$entityManager = $this->createMock(ORM\EntityManagerInterface::class);

		$connectorPropertiesStateRepository = $this->createMock(Models\States\ConnectorPropertiesRepository::class);
		$connectorPropertiesStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$connectorPropertiesStateManager = $this->createMock(Models\States\ConnectorPropertiesManager::class);

		$devicePropertiesStateRepository = $this->createMock(Models\States\DevicePropertiesRepository::class);
		$devicePropertiesStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$devicePropertiesStateManager = $this->createMock(Models\States\DevicePropertiesManager::class);

		$channelPropertiesStateRepository = $this->createMock(Models\States\ChannelPropertiesRepository::class);
		$channelPropertiesStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$channelPropertiesStateManager = $this->createMock(Models\States\ChannelPropertiesManager::class);

		$entityFactory = $this->createMock(ExchangeEntities\EntityFactory::class);

		$configurationWriter = $this->createMock(DataStorage\Writer::class);
		$configurationWriter
			->method('write');

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$devicePropertiesStateRepository,
			$devicePropertiesStateManager,
			$channelPropertiesStateRepository,
			$channelPropertiesStateManager,
			$connectorPropertiesStateRepository,
			$connectorPropertiesStateManager,
			$configurationWriter,
			$entityFactory,
			$publisher,
		);

		self::assertSame([
			'postPersist',
			'postUpdate',
			'postRemove',
		], $subscriber->getSubscribedEvents());
	}

	public function testPublishCreatedEntity(): void
	{
		$publisher = $this->createMock(ExchangePublisher\Publisher::class);
		$publisher
			->expects(self::once())
			->method('publish')
			->with(
				self::callback(static function ($source): bool {
					self::assertTrue($source instanceof Metadata\Types\ModuleSource);
					self::assertSame(Metadata\Constants::MODULE_DEVICES_SOURCE, $source->getValue());

					return true;
				}),
				self::callback(static function ($key): bool {
					self::assertTrue($key instanceof Metadata\Types\RoutingKey);
					self::assertSame(
						Metadata\Constants::MESSAGE_BUS_DEVICE_ENTITY_CREATED_ROUTING_KEY,
						$key->getValue(),
					);

					return true;
				}),
				self::callback(static function ($data): bool {
					$asArray = $data->toArray();

					unset($asArray['id']);

					self::assertEquals([
						'identifier' => 'device-name',
						'type' => 'blank',
						'owner' => null,
						'name' => 'Device custom name',
						'comment' => null,
						'connector' => 'dd6aa4bc-2611-40c3-84ef-0a438cf51e67',
						'parents' => [],
						'children' => [],
					], $asArray);

					return true;
				}),
			);

		$entityManager = $this->getEntityManager();

		$connectorPropertiesStateRepository = $this->createMock(Models\States\ConnectorPropertiesRepository::class);
		$connectorPropertiesStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$connectorPropertiesStateManager = $this->createMock(Models\States\ConnectorPropertiesManager::class);

		$devicePropertiesStateRepository = $this->createMock(Models\States\DevicePropertiesRepository::class);
		$devicePropertiesStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$devicePropertiesStateManager = $this->createMock(Models\States\DevicePropertiesManager::class);

		$channelPropertiesStateRepository = $this->createMock(Models\States\ChannelPropertiesRepository::class);
		$channelPropertiesStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$channelPropertiesStateManager = $this->createMock(Models\States\ChannelPropertiesManager::class);

		$entityItem = $this->createMock(MetadataEntities\DevicesModule\Device::class);
		$entityItem
			->method('toArray')
			->willReturn([
				'identifier' => 'device-name',
				'type' => 'blank',
				'owner' => null,
				'name' => 'Device custom name',
				'comment' => null,
				'connector' => 'dd6aa4bc-2611-40c3-84ef-0a438cf51e67',
				'parents' => [],
				'children' => [],
			]);

		$entityFactory = $this->createMock(ExchangeEntities\EntityFactory::class);
		$entityFactory
			->method('create')
			->willReturn($entityItem);

		$configurationWriter = $this->createMock(DataStorage\Writer::class);
		$configurationWriter
			->method('write');

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$devicePropertiesStateRepository,
			$devicePropertiesStateManager,
			$channelPropertiesStateRepository,
			$channelPropertiesStateManager,
			$connectorPropertiesStateRepository,
			$connectorPropertiesStateManager,
			$configurationWriter,
			$entityFactory,
			$publisher,
		);

		$connectorEntity = new Entities\Connectors\Blank(
			'blank-connector-name',
			Uuid\Uuid::fromString('dd6aa4bc-2611-40c3-84ef-0a438cf51e67'),
		);

		$entity = new Entities\Devices\Blank('device-name', $connectorEntity, 'device-name');
		$entity->setName('Device custom name');

		$eventArgs = $this->createMock(ORM\Event\LifecycleEventArgs::class);
		$eventArgs
			->expects(self::once())
			->method('getObject')
			->willReturn($entity);

		$subscriber->postPersist($eventArgs);
	}

	public function testPublishUpdatedEntity(): void
	{
		$publisher = $this->createMock(ExchangePublisher\Publisher::class);
		$publisher
			->expects(self::once())
			->method('publish')
			->with(
				self::callback(static function ($source): bool {
					self::assertTrue($source instanceof Metadata\Types\ModuleSource);
					self::assertSame(Metadata\Constants::MODULE_DEVICES_SOURCE, $source->getValue());

					return true;
				}),
				self::callback(static function ($key): bool {
					self::assertTrue($key instanceof Metadata\Types\RoutingKey);
					self::assertSame(
						Metadata\Constants::MESSAGE_BUS_DEVICE_ENTITY_UPDATED_ROUTING_KEY,
						$key->getValue(),
					);

					return true;
				}),
				self::callback(static function ($data): bool {
					$asArray = $data->toArray();

					unset($asArray['id']);

					self::assertEquals([
						'identifier' => 'device-name',
						'type' => 'blank',
						'owner' => null,
						'name' => 'Device custom name',
						'comment' => null,
						'connector' => 'dd6aa4bc-2611-40c3-84ef-0a438cf51e67',
						'parents' => [],
						'children' => [],
					], $asArray);

					return true;
				}),
			);

		$entityManager = $this->getEntityManager(true);

		$connectorPropertiesStateRepository = $this->createMock(Models\States\ConnectorPropertiesRepository::class);
		$connectorPropertiesStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$connectorPropertiesStateManager = $this->createMock(Models\States\ConnectorPropertiesManager::class);

		$devicePropertiesStateRepository = $this->createMock(Models\States\DevicePropertiesRepository::class);
		$devicePropertiesStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$devicePropertiesStateManager = $this->createMock(Models\States\DevicePropertiesManager::class);

		$channelPropertiesStateRepository = $this->createMock(Models\States\ChannelPropertiesRepository::class);
		$channelPropertiesStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$channelPropertiesStateManager = $this->createMock(Models\States\ChannelPropertiesManager::class);

		$entityItem = $this->createMock(MetadataEntities\DevicesModule\Device::class);
		$entityItem
			->method('toArray')
			->willReturn([
				'identifier' => 'device-name',
				'type' => 'blank',
				'owner' => null,
				'name' => 'Device custom name',
				'comment' => null,
				'connector' => 'dd6aa4bc-2611-40c3-84ef-0a438cf51e67',
				'parents' => [],
				'children' => [],
			]);

		$entityFactory = $this->createMock(ExchangeEntities\EntityFactory::class);
		$entityFactory
			->method('create')
			->willReturn($entityItem);

		$configurationWriter = $this->createMock(DataStorage\Writer::class);
		$configurationWriter
			->method('write');

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$devicePropertiesStateRepository,
			$devicePropertiesStateManager,
			$channelPropertiesStateRepository,
			$channelPropertiesStateManager,
			$connectorPropertiesStateRepository,
			$connectorPropertiesStateManager,
			$configurationWriter,
			$entityFactory,
			$publisher,
		);

		$connectorEntity = new Entities\Connectors\Blank(
			'blank-connector-name',
			Uuid\Uuid::fromString('dd6aa4bc-2611-40c3-84ef-0a438cf51e67'),
		);

		$entity = new Entities\Devices\Blank('device-name', $connectorEntity, 'device-name');
		$entity->setName('Device custom name');

		$eventArgs = $this->createMock(ORM\Event\LifecycleEventArgs::class);
		$eventArgs
			->expects(self::once())
			->method('getObject')
			->willReturn($entity);

		$subscriber->postUpdate($eventArgs);
	}

	public function testPublishDeletedEntity(): void
	{
		$publisher = $this->createMock(ExchangePublisher\Publisher::class);
		$publisher
			->expects(self::once())
			->method('publish')
			->with(
				self::callback(static function ($source): bool {
					self::assertTrue($source instanceof Metadata\Types\ModuleSource);
					self::assertSame(Metadata\Constants::MODULE_DEVICES_SOURCE, $source->getValue());

					return true;
				}),
				self::callback(static function ($key): bool {
					self::assertTrue($key instanceof Metadata\Types\RoutingKey);
					self::assertSame(
						Metadata\Constants::MESSAGE_BUS_DEVICE_ENTITY_DELETED_ROUTING_KEY,
						$key->getValue(),
					);

					return true;
				}),
				self::callback(static function ($data): bool {
					$asArray = $data->toArray();

					unset($asArray['id']);

					self::assertEquals([
						'identifier' => 'device-name',
						'type' => 'blank',
						'owner' => null,
						'name' => 'Device custom name',
						'comment' => null,
						'connector' => 'dd6aa4bc-2611-40c3-84ef-0a438cf51e67',
						'parents' => [],
						'children' => [],
					], $asArray);

					return true;
				}),
			);

		$connectorEntity = new Entities\Connectors\Blank(
			'blank-connector-name',
			Uuid\Uuid::fromString('dd6aa4bc-2611-40c3-84ef-0a438cf51e67'),
		);

		$entity = new Entities\Devices\Blank('device-name', $connectorEntity, 'device-name');
		$entity->setName('Device custom name');

		$entityManager = $this->getEntityManager();

		$connectorPropertiesStateRepository = $this->createMock(Models\States\ConnectorPropertiesRepository::class);
		$connectorPropertiesStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$connectorPropertiesStateManager = $this->createMock(Models\States\ConnectorPropertiesManager::class);

		$devicePropertiesStateRepository = $this->createMock(Models\States\DevicePropertiesRepository::class);
		$devicePropertiesStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$devicePropertiesStateManager = $this->createMock(Models\States\DevicePropertiesManager::class);

		$channelPropertiesStateRepository = $this->createMock(Models\States\ChannelPropertiesRepository::class);
		$channelPropertiesStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$channelPropertiesStateManager = $this->createMock(Models\States\ChannelPropertiesManager::class);

		$entityItem = $this->createMock(MetadataEntities\DevicesModule\Device::class);
		$entityItem
			->method('toArray')
			->willReturn([
				'identifier' => 'device-name',
				'type' => 'blank',
				'owner' => null,
				'name' => 'Device custom name',
				'comment' => null,
				'connector' => 'dd6aa4bc-2611-40c3-84ef-0a438cf51e67',
				'parents' => [],
				'children' => [],
			]);

		$entityFactory = $this->createMock(ExchangeEntities\EntityFactory::class);
		$entityFactory
			->method('create')
			->willReturn($entityItem);

		$configurationWriter = $this->createMock(DataStorage\Writer::class);
		$configurationWriter
			->method('write');

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$devicePropertiesStateRepository,
			$devicePropertiesStateManager,
			$channelPropertiesStateRepository,
			$channelPropertiesStateManager,
			$connectorPropertiesStateRepository,
			$connectorPropertiesStateManager,
			$configurationWriter,
			$entityFactory,
			$publisher,
		);

		$eventArgs = $this->createMock(ORM\Event\LifecycleEventArgs::class);
		$eventArgs
			->expects(self::once())
			->method('getObject')
			->willReturn($entity);

		$subscriber->postRemove($eventArgs);
	}

	private function getEntityManager(bool $withUow = false): ORM\EntityManagerInterface&MockObject
	{
		$metadata = new stdClass();
		$metadata->fieldMappings = [
			[
				'fieldName' => 'identifier',
			],
			[
				'fieldName' => 'name',
			],
		];

		$entityManager = $this->createMock(ORM\EntityManagerInterface::class);
		$entityManager
			->method('getClassMetadata')
			->with([Entities\Devices\Device::class])
			->willReturn($metadata);

		if ($withUow) {
			$uow = $this->createMock(ORM\UnitOfWork::class);
			$uow
				->expects(self::once())
				->method('getEntityChangeSet')
				->willReturn(['name']);
			$uow
				->method('isScheduledForDelete')
				->willReturn(false);

			$entityManager
				->expects(self::once())
				->method('getUnitOfWork')
				->willReturn($uow);
		}

		return $entityManager;
	}

}
