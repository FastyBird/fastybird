<?php declare(strict_types = 1);

/**
 * Controls.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           17.02.23
 */

namespace FastyBird\Connector\Shelly\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Types;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use Nette\Utils;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Controls implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Connectors\Controls\ControlsManager $controlsManager,
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
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if ($entity instanceof Entities\ShellyConnector) {
			$rebootControl = $entity->getProperty(Types\ConnectorControlName::NAME_REBOOT);

			if ($rebootControl === null) {
				$this->controlsManager->create(Utils\ArrayHash::from([
					'name' => Types\ConnectorControlName::NAME_REBOOT,
					'connector' => $entity,
				]));
			}

			$discoverControl = $entity->getProperty(Types\ConnectorControlName::NAME_DISCOVER);

			if ($discoverControl === null) {
				$this->controlsManager->create(Utils\ArrayHash::from([
					'name' => Types\ConnectorControlName::NAME_DISCOVER,
					'connector' => $entity,
				]));
			}
		}
	}

}
