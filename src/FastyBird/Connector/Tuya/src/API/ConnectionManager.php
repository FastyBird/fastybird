<?php declare(strict_types = 1);

/**
 * ConnectionManager.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           01.07.23
 */

namespace FastyBird\Connector\Tuya\API;

use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Helpers;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Connector\Tuya\ValueObjects;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use Orisai\ObjectMapper;
use function array_key_exists;
use function array_map;
use function assert;
use function is_string;

/**
 * API connections manager
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectionManager
{

	use Nette\SmartObject;

	/** @var array<string, LocalApi> */
	private array $localConnections = [];

	private OpenApi|null $cloudApiConnection = null;

	private OpenPulsar|null $cloudWsConnection = null;

	public function __construct(
		private readonly LocalApiFactory $localApiFactory,
		private readonly OpenApiFactory $openApiFactory,
		private readonly OpenPulsarFactory $openPulsarFactory,
		private readonly Helpers\Connector $connectorHelper,
		private readonly Helpers\Device $deviceHelper,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly ObjectMapper\Processing\Processor $objectMapper,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getLocalConnection(MetadataDocuments\DevicesModule\Device $device): LocalApi
	{
		$connector = $device->getConnector();
		assert($connector instanceof Entities\TuyaConnector);

		if (!array_key_exists($device->getId()->toString(), $this->localConnections)) {
			$findChildrenDevicesQuery = new DevicesQueries\Configuration\FindDevices();
			$findChildrenDevicesQuery->forParent($device);

			$children = $this->devicesConfigurationRepository->findAllBy($findChildrenDevicesQuery);

			assert(is_string($this->deviceHelper->getLocalKey($device)));
			assert(is_string($this->deviceHelper->getIpAddress($device)));

			$connection = $this->localApiFactory->create(
				$device->getIdentifier(),
				null,
				null,
				$this->deviceHelper->getLocalKey($device),
				$this->deviceHelper->getIpAddress($device),
				$this->deviceHelper->getProtocolVersion($device),
				array_map(function (MetadataDocuments\DevicesModule\Device $child): ValueObjects\LocalChild {
					assert(is_string($this->deviceHelper->getNodeId($child)));

					return $this->objectMapper->process(
						[
							'identifier' => $child->getIdentifier(),
							'node_id' => $this->deviceHelper->getNodeId($child),
							'type' => Types\LocalDeviceType::ZIGBEE,
						],
						ValueObjects\LocalChild::class,
					);
				}, $children),
			);

			$this->localConnections[$device->getId()->toString()] = $connection;
		}

		return $this->localConnections[$device->getId()->toString()];
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getCloudApiConnection(MetadataDocuments\DevicesModule\Connector $connector): OpenApi
	{
		if ($this->cloudApiConnection === null) {
			assert(is_string($this->connectorHelper->getAccessId($connector)));
			assert(is_string($this->connectorHelper->getAccessSecret($connector)));

			$this->cloudApiConnection = $this->openApiFactory->create(
				$connector->getIdentifier(),
				$this->connectorHelper->getAccessId($connector),
				$this->connectorHelper->getAccessSecret($connector),
				$this->connectorHelper->getOpenApiEndpoint($connector),
			);
		}

		return $this->cloudApiConnection;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getCloudWsConnection(MetadataDocuments\DevicesModule\Connector $connector): OpenPulsar
	{
		if ($this->cloudWsConnection === null) {
			assert(is_string($this->connectorHelper->getAccessId($connector)));
			assert(is_string($this->connectorHelper->getAccessSecret($connector)));

			$this->cloudWsConnection = $this->openPulsarFactory->create(
				$connector->getIdentifier(),
				$this->connectorHelper->getAccessId($connector),
				$this->connectorHelper->getAccessSecret($connector),
				$this->connectorHelper->getOpenPulsarTopic($connector),
				$this->connectorHelper->getOpenPulsarEndpoint($connector),
			);
		}

		return $this->cloudWsConnection;
	}

	public function __destruct()
	{
		foreach ($this->localConnections as $key => $connection) {
			$connection->disconnect();

			unset($this->localConnections[$key]);
		}

		$this->cloudApiConnection?->disconnect();
		$this->cloudWsConnection?->disconnect();
	}

}
