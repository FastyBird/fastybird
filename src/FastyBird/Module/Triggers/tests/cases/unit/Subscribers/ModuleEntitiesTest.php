<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Subscribers;

use Doctrine\ORM;
use Doctrine\Persistence;
use Exception;
use FastyBird\Library\Exchange\Documents as ExchangeDocuments;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Subscribers;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ModuleEntitiesTest extends TestCase
{

	public function testSubscriberEvents(): void
	{
		$publisher = $this->createMock(ExchangePublisher\Publisher::class);

		$entityManager = $this->createMock(ORM\EntityManagerInterface::class);

		$actionStateRepository = $this->createMock(Models\States\ActionsRepository::class);
		$actionStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$conditionStateRepository = $this->createMock(Models\States\ConditionsRepository::class);
		$conditionStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$entityFactory = $this->createMock(ExchangeDocuments\DocumentFactory::class);

		$subscriber = new Subscribers\ModuleEntities(
			$actionStateRepository,
			$conditionStateRepository,
			$entityFactory,
			$entityManager,
			$publisher,
		);

		self::assertSame(['onFlush', 'prePersist', 'postPersist', 'postUpdate'], $subscriber->getSubscribedEvents());
	}

	/**
	 * @throws Exception
	 */
	public function testPublishCreatedEntity(): void
	{
		$publisher = $this->createMock(ExchangePublisher\Publisher::class);
		$publisher
			->expects(self::once())
			->method('publish')
			->with(
				self::callback(static function ($source): bool {
					self::assertTrue($source instanceof Metadata\Types\Sources\Module);
					self::assertSame(Metadata\Constants::MODULE_TRIGGERS_SOURCE, $source->getValue());

					return true;
				}),
				self::callback(static function ($key): bool {
					self::assertTrue($key instanceof Metadata\Types\RoutingKey);
					self::assertSame(
						Metadata\Constants::MESSAGE_BUS_TRIGGER_DOCUMENT_CREATED_ROUTING_KEY,
						$key->getValue(),
					);

					return true;
				}),
				self::callback(static function ($data): bool {
					$asArray = $data->toArray();

					unset($asArray['id']);

					self::assertEquals([
						'name' => 'Trigger name',
						'comment' => null,
						'enabled' => true,
						'owner' => null,
						'type' => 'manual',
						'is_triggered' => false,
					], $asArray);

					return true;
				}),
			);

		$entityManager = $this->getEntityManager();

		$actionStateRepository = $this->createMock(Models\States\ActionsRepository::class);
		$actionStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$conditionStateRepository = $this->createMock(Models\States\ConditionsRepository::class);
		$conditionStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$entityItem = $this->createMock(MetadataDocuments\TriggersModule\ManualTrigger::class);
		$entityItem
			->method('toArray')
			->willReturn([
				'name' => 'Trigger name',
				'comment' => null,
				'enabled' => true,
				'owner' => null,
				'type' => 'manual',
				'is_triggered' => false,
			]);

		$entityFactory = $this->createMock(ExchangeDocuments\DocumentFactory::class);
		$entityFactory
			->method('create')
			->willReturn($entityItem);

		$subscriber = new Subscribers\ModuleEntities(
			$actionStateRepository,
			$conditionStateRepository,
			$entityFactory,
			$entityManager,
			$publisher,
		);

		$entity = new Entities\Triggers\ManualTrigger('Trigger name');

		$eventArgs = $this->createMock(Persistence\Event\LifecycleEventArgs::class);
		$eventArgs
			->expects(self::once())
			->method('getObject')
			->willReturn($entity);

		$subscriber->postPersist($eventArgs);
	}

	/**
	 * @throws Exception
	 */
	public function testPublishUpdatedEntity(): void
	{
		$publisher = $this->createMock(ExchangePublisher\Publisher::class);
		$publisher
			->expects(self::once())
			->method('publish')
			->with(
				self::callback(static function ($source): bool {
					self::assertTrue($source instanceof Metadata\Types\Sources\Module);
					self::assertSame(Metadata\Constants::MODULE_TRIGGERS_SOURCE, $source->getValue());

					return true;
				}),
				self::callback(static function ($key): bool {
					self::assertTrue($key instanceof Metadata\Types\RoutingKey);
					self::assertSame(
						Metadata\Constants::MESSAGE_BUS_TRIGGER_DOCUMENT_UPDATED_ROUTING_KEY,
						$key->getValue(),
					);

					return true;
				}),
				self::callback(static function ($data): bool {
					$asArray = $data->toArray();

					unset($asArray['id']);

					self::assertEquals([
						'name' => 'Trigger name',
						'comment' => null,
						'enabled' => true,
						'owner' => null,
						'type' => 'manual',
						'is_triggered' => false,
					], $asArray);

					return true;
				}),
			);

		$entityManager = $this->getEntityManager(true);

		$actionStateRepository = $this->createMock(Models\States\ActionsRepository::class);
		$actionStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$conditionStateRepository = $this->createMock(Models\States\ConditionsRepository::class);
		$conditionStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$entityItem = $this->createMock(MetadataDocuments\TriggersModule\ManualTrigger::class);
		$entityItem
			->method('toArray')
			->willReturn([
				'name' => 'Trigger name',
				'comment' => null,
				'enabled' => true,
				'owner' => null,
				'type' => 'manual',
				'is_triggered' => false,
			]);

		$entityFactory = $this->createMock(ExchangeDocuments\DocumentFactory::class);
		$entityFactory
			->method('create')
			->willReturn($entityItem);

		$subscriber = new Subscribers\ModuleEntities(
			$actionStateRepository,
			$conditionStateRepository,
			$entityFactory,
			$entityManager,
			$publisher,
		);

		$entity = new Entities\Triggers\ManualTrigger('Trigger name');

		$eventArgs = $this->createMock(Persistence\Event\LifecycleEventArgs::class);
		$eventArgs
			->expects(self::once())
			->method('getObject')
			->willReturn($entity);

		$subscriber->postUpdate($eventArgs);
	}

	/**
	 * @throws Exception
	 */
	public function testPublishDeletedEntity(): void
	{
		$publisher = $this->createMock(ExchangePublisher\Publisher::class);
		$publisher
			->expects(self::once())
			->method('publish')
			->with(
				self::callback(static function ($source): bool {
					self::assertTrue($source instanceof Metadata\Types\Sources\Module);
					self::assertSame(Metadata\Constants::MODULE_TRIGGERS_SOURCE, $source->getValue());

					return true;
				}),
				self::callback(static function ($key): bool {
					self::assertTrue($key instanceof Metadata\Types\RoutingKey);
					self::assertSame(
						Metadata\Constants::MESSAGE_BUS_TRIGGER_DOCUMENT_DELETED_ROUTING_KEY,
						$key->getValue(),
					);

					return true;
				}),
				self::callback(static function ($data): bool {
					$asArray = $data->toArray();

					unset($asArray['id']);

					self::assertEquals([
						'name' => 'Trigger name',
						'comment' => null,
						'enabled' => true,
						'owner' => null,
						'type' => 'manual',
						'is_triggered' => false,
					], $asArray);

					return true;
				}),
			);

		$entity = new Entities\Triggers\ManualTrigger('Trigger name');

		$uow = $this->createMock(ORM\UnitOfWork::class);
		$uow
			->expects(self::once())
			->method('getScheduledEntityDeletions')
			->willReturn([$entity]);
		$uow
			->expects(self::once())
			->method('getEntityIdentifier')
			->willReturn([
				123,
			]);

		$entityManager = $this->getEntityManager();
		$entityManager
			->expects(self::once())
			->method('getUnitOfWork')
			->willReturn($uow);

		$actionStateRepository = $this->createMock(Models\States\ActionsRepository::class);
		$actionStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$conditionStateRepository = $this->createMock(Models\States\ConditionsRepository::class);
		$conditionStateRepository
			->method('findOne')
			->willThrowException(new Exceptions\NotImplemented());

		$entityItem = $this->createMock(MetadataDocuments\TriggersModule\ManualTrigger::class);
		$entityItem
			->method('toArray')
			->willReturn([
				'name' => 'Trigger name',
				'comment' => null,
				'enabled' => true,
				'owner' => null,
				'type' => 'manual',
				'is_triggered' => false,
			]);

		$entityFactory = $this->createMock(ExchangeDocuments\DocumentFactory::class);
		$entityFactory
			->method('create')
			->willReturn($entityItem);

		$subscriber = new Subscribers\ModuleEntities(
			$actionStateRepository,
			$conditionStateRepository,
			$entityFactory,
			$entityManager,
			$publisher,
		);

		$subscriber->onFlush();
	}

	private function getEntityManager(bool $withUow = false): ORM\EntityManagerInterface&MockObject
	{
		$metadata = new stdClass();
		$metadata->fieldMappings = [
			[
				'fieldName' => 'name',
			],
			[
				'fieldName' => 'comment',
			],
			[
				'fieldName' => 'enabled',
			],
		];

		$entityManager = $this->createMock(ORM\EntityManagerInterface::class);
		$entityManager
			->method('getClassMetadata')
			->with([Entities\Triggers\Trigger::class])
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
