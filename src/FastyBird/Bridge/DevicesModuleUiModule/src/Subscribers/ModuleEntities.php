<?php declare(strict_types = 1);

/**
 * ModuleEntities.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           09.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Bridge\DevicesModuleUiModule\Documents;
use FastyBird\Bridge\DevicesModuleUiModule\Queries;
use FastyBird\Library\Application\Events as ApplicationEvents;
use FastyBird\Library\Exchange\Publisher as ExchangePublisher;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Ui;
use FastyBird\Module\Ui\Caching as UiCaching;
use FastyBird\Module\Ui\Exceptions as UiExceptions;
use FastyBird\Module\Ui\Models as UiModels;
use Nette;
use Nette\Caching;
use React\Promise;
use function array_map;
use function assert;
use function count;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ModuleEntities implements Common\EventSubscriber
{

	use Nette\SmartObject;

	private bool $useAsync = false;

	public function __construct(
		private readonly UiModels\Configuration\Widgets\DataSources\Repository $dataSourcesRepository,
		private readonly UiCaching\Container $uiModuleCaching,
		private readonly ORM\EntityManagerInterface $entityManager,
		private readonly ExchangePublisher\Publisher $publisher,
		private readonly ExchangePublisher\Async\Publisher $asyncPublisher,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			0 => ORM\Events::postUpdate,

			ApplicationEvents\EventLoopStarted::class => 'enableAsync',
			ApplicationEvents\EventLoopStopped::class => 'disableAsync',
			ApplicationEvents\EventLoopStopping::class => 'disableAsync',
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws UiExceptions\InvalidState
	 */
	public function postUpdate(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$uow = $this->entityManager->getUnitOfWork();

		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Get changes => should be already computed here (is a listener)
		$changeSet = $uow->getEntityChangeSet($entity);

		// If we have no changes left => don't create revision log
		if (count($changeSet) === 0) {
			return;
		}

		// Check for valid entity
		if (!$entity instanceof DevicesEntities\Entity || $uow->isScheduledForDelete($entity)) {
			return;
		}

		$this->processEntity($entity);
	}

	public function enableAsync(): void
	{
		$this->useAsync = true;
	}

	public function disableAsync(): void
	{
		$this->useAsync = false;
	}

	/**
	 * @throws UiExceptions\InvalidState
	 */
	private function processEntity(DevicesEntities\Entity $entity): void
	{
		if (
			!$entity instanceof DevicesEntities\Connectors\Properties\Property
			&& !$entity instanceof DevicesEntities\Devices\Properties\Property
			&& !$entity instanceof DevicesEntities\Channels\Properties\Property
		) {
			return;
		}

		$findDataSources = new Queries\Configuration\FindWidgetDataSources();
		$findDataSources->byPropertyId($entity->getId());

		$dataSources = $this->dataSourcesRepository->findAllBy(
			$findDataSources,
			Documents\Widgets\DataSources\Property::class,
		);

		if ($dataSources === []) {
			return;
		}

		$this->uiModuleCaching->getConfigurationBuilderCache()->clean([
			Caching\Cache::Tags => array_map(
				static fn (Documents\Widgets\DataSources\Property $dataSource): string => $dataSource->getId()->toString(),
				$dataSources,
			),
		]);

		$this->uiModuleCaching->getConfigurationRepositoryCache()->clean([
			Caching\Cache::Tags => array_map(
				static fn (Documents\Widgets\DataSources\Property $dataSource): string => $dataSource->getId()->toString(),
				$dataSources,
			),
		]);

		foreach ($dataSources as $dataSource) {
			$findDataSources = new Queries\Configuration\FindWidgetDataSources();
			$findDataSources->byId($dataSource->getId());

			$dataSource = $this->dataSourcesRepository->findOneBy(
				$findDataSources,
				Documents\Widgets\DataSources\Property::class,
			);
			assert($dataSource !== null);

			$this->publishDocument($this->useAsync, $dataSource);
		}
	}

	/**
	 * @return ($async is true ? Promise\PromiseInterface<bool> : bool)
	 */
	private function publishDocument(
		bool $async,
		Documents\Widgets\DataSources\Property $dataSource,
	): Promise\PromiseInterface|bool
	{
		return $this->getPublisher($async)->publish(
			MetadataTypes\Sources\Bridge::DEVICES_MODULE_UI_MODULE,
			Ui\Constants::MESSAGE_BUS_WIDGET_DATA_SOURCE_DOCUMENT_REPORTED_ROUTING_KEY,
			$dataSource,
		);
	}

	private function getPublisher(bool $async): ExchangePublisher\Publisher|ExchangePublisher\Async\Publisher
	{
		return $async ? $this->asyncPublisher : $this->publisher;
	}

}
