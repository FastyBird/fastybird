<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           05.09.22
 */

namespace FastyBird\Connector\Tuya\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
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
final class Properties implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Devices\Properties\PropertiesManager $propertiesManager,
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
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\TuyaDevice) {
			return;
		}

		$stateProperty = $entity->getProperty(Types\DevicePropertyIdentifier::IDENTIFIER_STATE);

		if ($stateProperty !== null) {
			$entity->removeProperty($stateProperty);
		}

		$this->propertiesManager->create(Utils\ArrayHash::from([
			'device' => $entity,
			'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
			'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_STATE,
			'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
			'unit' => null,
			'format' => [
				MetadataTypes\ConnectionState::STATE_CONNECTED,
				MetadataTypes\ConnectionState::STATE_DISCONNECTED,
				MetadataTypes\ConnectionState::STATE_STOPPED,
				MetadataTypes\ConnectionState::STATE_LOST,
				MetadataTypes\ConnectionState::STATE_UNKNOWN,
			],
			'settable' => false,
			'queryable' => false,
		]));
	}

}
