<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           12.02.23
 */

namespace FastyBird\Connector\HomeKit\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\HomeKit\Entities;
use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Helpers;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use Nette\Utils;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Properties implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Connectors\Properties\PropertiesManager $propertiesManager,
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
	 * @throws Exceptions\InvalidState
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if ($entity instanceof Entities\HomeKitConnector) {
			$macAddressProperty = $entity->getProperty(Types\ConnectorPropertyIdentifier::IDENTIFIER_MAC_ADDRESS);

			if ($macAddressProperty === null) {
				$this->propertiesManager->create(Utils\ArrayHash::from([
					'device' => $entity,
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_MAC_ADDRESS,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'unit' => null,
					'format' => null,
					'settable' => false,
					'queryable' => false,
					'value' => Helpers\Protocol::generateMacAddress(),
				]));
			}

			$setupIdProperty = $entity->getProperty(Types\ConnectorPropertyIdentifier::IDENTIFIER_SETUP_ID);

			if ($setupIdProperty === null) {
				$this->propertiesManager->create(Utils\ArrayHash::from([
					'device' => $entity,
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_SETUP_ID,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'unit' => null,
					'format' => null,
					'settable' => false,
					'queryable' => false,
					'value' => Helpers\Protocol::generateSetupId(),
				]));
			}

			$pinCodeProperty = $entity->getProperty(Types\ConnectorPropertyIdentifier::IDENTIFIER_PIN_CODE);

			if ($pinCodeProperty === null) {
				$this->propertiesManager->create(Utils\ArrayHash::from([
					'device' => $entity,
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_PIN_CODE,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'unit' => null,
					'format' => null,
					'settable' => false,
					'queryable' => false,
					'value' => Helpers\Protocol::generatePinCode(),
				]));
			}

			$serverSecretProperty = $entity->getProperty(Types\ConnectorPropertyIdentifier::IDENTIFIER_SERVER_SECRET);

			if ($serverSecretProperty === null) {
				$this->propertiesManager->create(Utils\ArrayHash::from([
					'device' => $entity,
					'entity' => DevicesEntities\Connectors\Properties\Variable::class,
					'identifier' => Types\ConnectorPropertyIdentifier::IDENTIFIER_SERVER_SECRET,
					'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
					'unit' => null,
					'format' => null,
					'settable' => false,
					'queryable' => false,
					'value' => Helpers\Protocol::generateSignKey(),
				]));
			}
		}
	}

}
