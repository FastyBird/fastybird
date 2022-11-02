<?php declare(strict_types = 1);

namespace Tests\Cases;

use Doctrine\ORM;
use FastyBird\Exchange\Entities as ExchangeEntities;
use FastyBird\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Exceptions;
use FastyBird\Module\Triggers\Models;
use FastyBird\Module\Triggers\Subscribers;
use Mockery;
use Ninjify\Nunjuck\TestCase\BaseMockeryTestCase;
use stdClass;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';

/**
 * @testCase
 */
final class EntitiesSubscriberTest extends BaseMockeryTestCase
{

	public function testSubscriberEvents(): void
	{
		$publisher = Mockery::mock(ExchangePublisher\Publisher::class);
		$entityManager = Mockery::mock(ORM\EntityManagerInterface::class);

		$actionStateRepository = Mockery::mock(Models\States\ActionsRepository::class);
		$actionStateRepository
			->shouldReceive('findOne')
			->andThrow(Exceptions\NotImplemented::class);

		$conditionStateRepository = Mockery::mock(Models\States\ConditionsRepository::class);
		$conditionStateRepository
			->shouldReceive('findOne')
			->andThrow(Exceptions\NotImplemented::class);

		$entityFactory = Mockery::mock(ExchangeEntities\EntityFactory::class);

		$subscriber = new Subscribers\ModuleEntities(
			$actionStateRepository,
			$conditionStateRepository,
			$entityFactory,
			$entityManager,
			$publisher
		);

		Assert::same(['onFlush', 'prePersist', 'postPersist', 'postUpdate'], $subscriber->getSubscribedEvents());
	}

	public function testPublishCreatedEntity(): void
	{
		$publisher = Mockery::mock(ExchangePublisher\Publisher::class);
		$publisher
			->shouldReceive('publish')
			->withArgs(function (string $source, string $key, MetadataEntities\IEntity $data): bool {
				Assert::same(Metadata\Constants::MODULE_TRIGGERS_SOURCE, $source);

				$asArray = $data->toArray();

				unset($asArray['id']);

				Assert::same(Metadata\Constants::MESSAGE_BUS_TRIGGER_ENTITY_CREATED_ROUTING_KEY, $key);
				Assert::equal([
					'name'         => 'Trigger name',
					'comment'      => null,
					'enabled'      => true,
					'owner'        => null,
					'type'         => 'manual',
					'is_triggered' => false,
				], $asArray);

				return true;
			})
			->times(1);

		$entityManager = $this->getEntityManager();

		$actionStateRepository = Mockery::mock(Models\States\ActionsRepository::class);
		$actionStateRepository
			->shouldReceive('findOne')
			->andThrow(Exceptions\NotImplemented::class);

		$conditionStateRepository = Mockery::mock(Models\States\ConditionsRepository::class);
		$conditionStateRepository
			->shouldReceive('findOne')
			->andThrow(Exceptions\NotImplemented::class);

		$entity = Mockery::mock(MetadataEntities\Modules\TriggersModule\ManualTriggerEntity::class);
		$entity
			->shouldReceive('toArray')
			->andReturn([
				'name'         => 'Trigger name',
				'comment'      => null,
				'enabled'      => true,
				'owner'        => null,
				'type'         => 'manual',
				'is_triggered' => false,
			]);

		$entityFactory = Mockery::mock(ExchangeEntities\EntityFactory::class);
		$entityFactory
			->shouldReceive('create')
			->andReturn($entity);

		$subscriber = new Subscribers\ModuleEntities(
			$actionStateRepository,
			$conditionStateRepository,
			$entityFactory,
			$entityManager,
			$publisher
		);

		$entity = new Entities\Triggers\ManualTrigger(
			'Trigger name'
		);

		$eventArgs = Mockery::mock(ORM\Event\LifecycleEventArgs::class);
		$eventArgs
			->shouldReceive('getObject')
			->withNoArgs()
			->andReturn($entity)
			->times(1);

		$subscriber->postPersist($eventArgs);
	}

	public function testPublishUpdatedEntity(): void
	{
		$publisher = Mockery::mock(ExchangePublisher\Publisher::class);
		$publisher
			->shouldReceive('publish')
			->withArgs(function (string $source, string $key, MetadataEntities\IEntity $data): bool {
				Assert::same(Metadata\Constants::MODULE_TRIGGERS_SOURCE, $source);

				$asArray = $data->toArray();

				unset($asArray['id']);

				Assert::same(Metadata\Constants::MESSAGE_BUS_TRIGGER_ENTITY_UPDATED_ROUTING_KEY, $key);
				Assert::equal([
					'name'         => 'Trigger name',
					'comment'      => null,
					'enabled'      => true,
					'owner'        => null,
					'type'         => 'manual',
					'is_triggered' => false,
				], $asArray);

				return true;
			})
			->times(1);

		$entityManager = $this->getEntityManager(true);

		$actionStateRepository = Mockery::mock(Models\States\ActionsRepository::class);
		$actionStateRepository
			->shouldReceive('findOne')
			->andThrow(Exceptions\NotImplemented::class);

		$conditionStateRepository = Mockery::mock(Models\States\ConditionsRepository::class);
		$conditionStateRepository
			->shouldReceive('findOne')
			->andThrow(Exceptions\NotImplemented::class);

		$entity = Mockery::mock(MetadataEntities\Modules\TriggersModule\ManualTriggerEntity::class);
		$entity
			->shouldReceive('toArray')
			->andReturn([
				'name'         => 'Trigger name',
				'comment'      => null,
				'enabled'      => true,
				'owner'        => null,
				'type'         => 'manual',
				'is_triggered' => false,
			]);

		$entityFactory = Mockery::mock(ExchangeEntities\EntityFactory::class);
		$entityFactory
			->shouldReceive('create')
			->andReturn($entity);

		$subscriber = new Subscribers\ModuleEntities(
			$actionStateRepository,
			$conditionStateRepository,
			$entityFactory,
			$entityManager,
			$publisher
		);

		$entity = new Entities\Triggers\ManualTrigger(
			'Trigger name'
		);

		$eventArgs = Mockery::mock(ORM\Event\LifecycleEventArgs::class);
		$eventArgs
			->shouldReceive('getObject')
			->andReturn($entity)
			->times(1);

		$subscriber->postUpdate($eventArgs);
	}

	public function testPublishDeletedEntity(): void
	{
		$publisher = Mockery::mock(ExchangePublisher\Publisher::class);
		$publisher
			->shouldReceive('publish')
			->withArgs(function (string $source, string $key, MetadataEntities\IEntity $data): bool {
				Assert::same(Metadata\Constants::MODULE_TRIGGERS_SOURCE, $source);

				$asArray = $data->toArray();

				unset($asArray['id']);

				Assert::same(Metadata\Constants::MESSAGE_BUS_TRIGGER_ENTITY_DELETED_ROUTING_KEY, $key);
				Assert::equal([
					'name'         => 'Trigger name',
					'comment'      => null,
					'enabled'      => true,
					'owner'        => null,
					'type'         => 'manual',
					'is_triggered' => false,
				], $asArray);

				return true;
			})
			->times(1);

		$entity = new Entities\Triggers\ManualTrigger(
			'Trigger name'
		);

		$uow = Mockery::mock(ORM\UnitOfWork::class);
		$uow
			->shouldReceive('getScheduledEntityDeletions')
			->withNoArgs()
			->andReturn([$entity])
			->times(1)
			->getMock()
			->shouldReceive('getEntityIdentifier')
			->andReturn([
				123,
			])
			->times(1);

		$entityManager = $this->getEntityManager();
		$entityManager
			->shouldReceive('getUnitOfWork')
			->withNoArgs()
			->andReturn($uow)
			->times(1);

		$actionStateRepository = Mockery::mock(Models\States\ActionsRepository::class);
		$actionStateRepository
			->shouldReceive('findOne')
			->andThrow(Exceptions\NotImplemented::class);

		$conditionStateRepository = Mockery::mock(Models\States\ConditionsRepository::class);
		$conditionStateRepository
			->shouldReceive('findOne')
			->andThrow(Exceptions\NotImplemented::class);

		$entity = Mockery::mock(MetadataEntities\Modules\TriggersModule\ManualTriggerEntity::class);
		$entity
			->shouldReceive('toArray')
			->andReturn([
				'name'         => 'Trigger name',
				'comment'      => null,
				'enabled'      => true,
				'owner'        => null,
				'type'         => 'manual',
				'is_triggered' => false,
			]);

		$entityFactory = Mockery::mock(ExchangeEntities\EntityFactory::class);
		$entityFactory
			->shouldReceive('create')
			->andReturn($entity);

		$subscriber = new Subscribers\ModuleEntities(
			$actionStateRepository,
			$conditionStateRepository,
			$entityFactory,
			$entityManager,
			$publisher
		);

		$subscriber->onFlush();
	}

	/**
	 * @param bool $withUow
	 *
	 * @return ORM\EntityManagerInterface
	 */
	private function getEntityManager(bool $withUow = false): ORM\EntityManagerInterface
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

		$entityManager = Mockery::mock(ORM\EntityManagerInterface::class);
		$entityManager
			->shouldReceive('getClassMetadata')
			->withArgs([Entities\Triggers\Trigger::class])
			->andReturn($metadata);

		if ($withUow) {
			$uow = Mockery::mock(ORM\UnitOfWork::class);
			$uow
				->shouldReceive('getEntityChangeSet')
				->andReturn(['name'])
				->times(1)
				->getMock()
				->shouldReceive('isScheduledForDelete')
				->andReturn(false)
				->getMock();

			$entityManager
				->shouldReceive('getUnitOfWork')
				->withNoArgs()
				->andReturn($uow)
				->times(1);
		}

		return $entityManager;
	}

}

$test_case = new EntitiesSubscriberTest();
$test_case->run();
