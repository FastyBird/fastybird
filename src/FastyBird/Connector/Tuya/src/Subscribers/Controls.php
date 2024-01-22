<?php declare(strict_types = 1);

/**
 * Controls.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           17.02.23
 */

namespace FastyBird\Connector\Tuya\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Nette\Utils;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Controls implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Entities\Connectors\Controls\ControlsRepository $controlsRepository,
		private readonly DevicesModels\Entities\Connectors\Controls\ControlsManager $controlsManager,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws ApplicationExceptions\InvalidState
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if ($entity instanceof Entities\TuyaConnector) {
			$findConnectorControlQuery = new DevicesQueries\Entities\FindConnectorControls();
			$findConnectorControlQuery->forConnector($entity);
			$findConnectorControlQuery->byName(Types\ConnectorControlName::REBOOT);

			$rebootControl = $this->controlsRepository->findOneBy($findConnectorControlQuery);

			if ($rebootControl === null) {
				$this->controlsManager->create(Utils\ArrayHash::from([
					'name' => Types\ConnectorControlName::REBOOT,
					'connector' => $entity,
				]));
			}

			$findConnectorControlQuery = new DevicesQueries\Entities\FindConnectorControls();
			$findConnectorControlQuery->forConnector($entity);
			$findConnectorControlQuery->byName(Types\ConnectorControlName::DISCOVER);

			$discoverControl = $this->controlsRepository->findOneBy($findConnectorControlQuery);

			if ($discoverControl === null) {
				$this->controlsManager->create(Utils\ArrayHash::from([
					'name' => Types\ConnectorControlName::DISCOVER,
					'connector' => $entity,
				]));
			}
		}
	}

}
