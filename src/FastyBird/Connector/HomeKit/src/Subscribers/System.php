<?php declare(strict_types = 1);

/**
 * System.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           15.03.23
 */

namespace FastyBird\Connector\HomeKit\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use IPub\DoctrineCrud;
use Nette;
use Nette\Utils;
use function intval;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class System implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Connectors\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Connectors\Properties\PropertiesManager $propertiesManager,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
			ORM\Events::postUpdate,
			ORM\Events::postRemove,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$this->processVersionUpdate($eventArgs);
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function postUpdate(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$this->processVersionUpdate($eventArgs);
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function postRemove(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$this->processVersionUpdate($eventArgs);
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function processVersionUpdate(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		if (
			$entity instanceof DevicesEntities\Connectors\Properties\Variable
			&& $entity->getIdentifier() === Types\ConnectorPropertyIdentifier::IDENTIFIER_CONFIG_VERSION
		) {
			return;
		}

		$connector = null;

		if ($entity instanceof Entities\HomeKitDevice) {
			$connector = $entity->getConnector();
		} elseif ($entity instanceof DevicesEntities\Devices\Properties\Property) {
			$connector = $entity->getDevice()->getConnector();
		} elseif ($entity instanceof DevicesEntities\Devices\Controls\Control) {
			$connector = $entity->getDevice()->getConnector();
		} elseif ($entity instanceof Entities\HomeKitChannel) {
			$connector = $entity->getDevice()->getConnector();
		} elseif ($entity instanceof DevicesEntities\Channels\Properties\Property) {
			$connector = $entity->getChannel()->getDevice()->getConnector();
		} elseif ($entity instanceof DevicesEntities\Channels\Controls\Control) {
			$connector = $entity->getChannel()->getDevice()->getConnector();
		}

		if ($connector === null) {
			return;
		}

		$findPropertyQuery = new DevicesQueries\FindConnectorProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(Types\ConnectorPropertyIdentifier::IDENTIFIER_CONFIG_VERSION);

		$property = $this->propertiesRepository->findOneBy(
			$findPropertyQuery,
			DevicesEntities\Connectors\Properties\Variable::class,
		);

		if ($property !== null) {
			$this->propertiesManager->update($property, Utils\ArrayHash::from([
				'value' => intval($property->getValue()) + 1,
			]));
		}
	}

}
