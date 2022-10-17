<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Subscribers
 * @since          0.37.0
 *
 * @date           04.08.22
 */

namespace FastyBird\ShellyConnector\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\Metadata\Types as MetadataTypes;
use FastyBird\ShellyConnector\Entities;
use FastyBird\ShellyConnector\Types;
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
final class Properties implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModuleModels\Devices\Properties\PropertiesManager $propertiesManager,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
		];
	}

	public function postPersist(ORM\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (!$entity instanceof Entities\ShellyDevice) {
			return;
		}

		$stateProperty = $entity->getProperty(Types\DevicePropertyIdentifier::IDENTIFIER_STATE);

		if ($stateProperty !== null) {
			$entity->removeProperty($stateProperty);
		}

		$this->propertiesManager->create(Utils\ArrayHash::from([
			'device' => $entity,
			'entity' => DevicesModuleEntities\Devices\Properties\Dynamic::class,
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
