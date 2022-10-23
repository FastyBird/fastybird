<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Subscribers;

use Doctrine\ORM;
use Exception;
use FastyBird\Module\Devices\DataStorage;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Subscribers;
use League\Flysystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid;
use stdClass;

final class EntitiesSubscriberTest extends TestCase
{

	public function testSubscriberEvents(): void
	{
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
		);

		self::assertSame([
			'postPersist',
			'postUpdate',
			'postRemove',
		], $subscriber->getSubscribedEvents());
	}

	/**
	 * @throws Exception
	 * @throws Flysystem\FilesystemException
	 */
	public function testPublishCreatedEntity(): void
	{
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

	/**
	 * @throws Exception
	 * @throws Flysystem\FilesystemException
	 */
	public function testPublishUpdatedEntity(): void
	{
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

	/**
	 * @throws Exception
	 * @throws Flysystem\FilesystemException
	 */
	public function testPublishDeletedEntity(): void
	{
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
