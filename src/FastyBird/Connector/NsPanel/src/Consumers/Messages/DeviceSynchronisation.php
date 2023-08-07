<?php declare(strict_types = 1);

/**
 * DeviceSynchronisation.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Consumers
 * @since          1.0.0
 *
 * @date           04.08.23
 */

namespace FastyBird\Connector\NsPanel\Consumers\Messages;

use Doctrine\DBAL;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Consumers;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;

/**
 * Device synchronisation message consumer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Consumers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class DeviceSynchronisation implements Consumers\Consumer
{

	use ConsumeDeviceProperty;
	use Nette\SmartObject;

	public function __construct(
		protected readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesModels\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		protected readonly DevicesModels\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		protected readonly DevicesUtilities\Database $databaseHelper,
		private readonly NsPanel\Logger $logger,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function consume(Entities\Messages\Entity $entity): bool
	{
		if (!$entity instanceof Entities\Messages\DeviceSynchronisation) {
			return false;
		}

		$findDeviceQuery = new Queries\FindThirdPartyDevices();
		$findDeviceQuery->byConnectorId($entity->getConnector());
		$findDeviceQuery->byIdentifier($entity->getIdentifier());

		$device = $this->devicesRepository->findOneBy($findDeviceQuery, Entities\Devices\ThirdPartyDevice::class);

		if ($device === null) {
			return true;
		}

		$this->setDeviceProperty(
			$device->getId(),
			$entity->getGatewayIdentifier(),
			MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_STRING),
			Types\DevicePropertyIdentifier::GATEWAY_IDENTIFIER,
			Helpers\Name::createName(Types\DevicePropertyIdentifier::GATEWAY_IDENTIFIER),
		);

		$this->logger->debug(
			'Consumed device synchronisation message',
			[
				'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
				'type' => 'device-synchronisation-message-consumer',
				'device' => [
					'id' => $device->getId()->toString(),
				],
				'data' => $entity->toArray(),
			],
		);

		return true;
	}

}
