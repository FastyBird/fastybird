<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Subscribers
 * @since          0.13.0
 *
 * @date           05.09.22
 */

namespace FastyBird\Connector\Tuya\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Types;
use FastyBird\DevicesModule\Entities as DevicesModuleEntities;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\Metadata\Types as MetadataTypes;
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
		if (!$entity instanceof Entities\TuyaDevice) {
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
