<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Subscribers;

use Doctrine\ORM;
use Doctrine\Persistence;
use Exception;
use FastyBird\Library\Application\Events as ApplicationEvents;
use FastyBird\Library\Exchange\Documents as ExchangeDocuments;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Documents;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Subscribers;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use function is_string;

final class ModuleEntitiesTest extends TestCase
{

	public function testSubscriberEvents(): void
	{
		$publisher = $this->createMock(ExchangePublisher\Publisher::class);

		$asyncPublisher = $this->createMock(ExchangePublisher\Async\Publisher::class);

		$entityManager = $this->createMock(ORM\EntityManagerInterface::class);

		$documentFactory = $this->createMock(ExchangeDocuments\DocumentFactory::class);

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$documentFactory,
			$publisher,
			$asyncPublisher,
		);

		self::assertSame([
			0 => ORM\Events::prePersist,
			1 => ORM\Events::postPersist,
			2 => ORM\Events::postUpdate,
			3 => ORM\Events::postRemove,

			ApplicationEvents\EventLoopStarted::class => 'enableAsync',
			ApplicationEvents\EventLoopStopped::class => 'disableAsync',
			ApplicationEvents\EventLoopStopping::class => 'disableAsync',
		], $subscriber->getSubscribedEvents());
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
					self::assertTrue($source instanceof MetadataTypes\Sources\Module);
					self::assertSame(Metadata\Constants::MODULE_TRIGGERS_SOURCE, $source->getValue());

					return true;
				}),
				self::callback(static function ($key): bool {
					self::assertTrue(is_string($key));
					self::assertSame(
						Triggers\Constants::MESSAGE_BUS_TRIGGER_DOCUMENT_CREATED_ROUTING_KEY,
						$key,
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

		$asyncPublisher = $this->createMock(ExchangePublisher\Async\Publisher::class);

		$entityManager = $this->getEntityManager();

		$document = $this->createMock(Documents\Triggers\Manual::class);
		$document
			->method('toArray')
			->willReturn([
				'name' => 'Trigger name',
				'comment' => null,
				'enabled' => true,
				'owner' => null,
				'type' => 'manual',
				'is_triggered' => false,
			]);

		$documentFactory = $this->createMock(ExchangeDocuments\DocumentFactory::class);
		$documentFactory
			->method('create')
			->willReturn($document);

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$documentFactory,
			$publisher,
			$asyncPublisher,
		);

		$entity = new Entities\Triggers\Manual('Trigger name');

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
					self::assertTrue($source instanceof MetadataTypes\Sources\Module);
					self::assertSame(Metadata\Constants::MODULE_TRIGGERS_SOURCE, $source->getValue());

					return true;
				}),
				self::callback(static function ($key): bool {
					self::assertTrue(is_string($key));
					self::assertSame(
						Triggers\Constants::MESSAGE_BUS_TRIGGER_DOCUMENT_UPDATED_ROUTING_KEY,
						$key,
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

		$asyncPublisher = $this->createMock(ExchangePublisher\Async\Publisher::class);

		$entityManager = $this->getEntityManager(true);

		$document = $this->createMock(Documents\Triggers\Manual::class);
		$document
			->method('toArray')
			->willReturn([
				'name' => 'Trigger name',
				'comment' => null,
				'enabled' => true,
				'owner' => null,
				'type' => 'manual',
				'is_triggered' => false,
			]);

		$documentFactory = $this->createMock(ExchangeDocuments\DocumentFactory::class);
		$documentFactory
			->method('create')
			->willReturn($document);

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$documentFactory,
			$publisher,
			$asyncPublisher,
		);

		$entity = new Entities\Triggers\Manual('Trigger name');

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
					self::assertTrue($source instanceof MetadataTypes\Sources\Module);
					self::assertSame(Metadata\Constants::MODULE_TRIGGERS_SOURCE, $source->getValue());

					return true;
				}),
				self::callback(static function ($key): bool {
					self::assertTrue(is_string($key));
					self::assertSame(
						Triggers\Constants::MESSAGE_BUS_TRIGGER_DOCUMENT_DELETED_ROUTING_KEY,
						$key,
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

		$asyncPublisher = $this->createMock(ExchangePublisher\Async\Publisher::class);

		$entity = new Entities\Triggers\Manual('Trigger name');

		$entityManager = $this->getEntityManager();

		$document = $this->createMock(Documents\Triggers\Manual::class);
		$document
			->method('toArray')
			->willReturn([
				'name' => 'Trigger name',
				'comment' => null,
				'enabled' => true,
				'owner' => null,
				'type' => 'manual',
				'is_triggered' => false,
			]);

		$documentFactory = $this->createMock(ExchangeDocuments\DocumentFactory::class);
		$documentFactory
			->method('create')
			->willReturn($document);

		$subscriber = new Subscribers\ModuleEntities(
			$entityManager,
			$documentFactory,
			$publisher,
			$asyncPublisher,
		);

		$eventArgs = $this->createMock(Persistence\Event\LifecycleEventArgs::class);
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
