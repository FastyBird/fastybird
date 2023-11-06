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
use FastyBird\Connector\Tuya\Queries;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Connector\Tuya\ValueObjects;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
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
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
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
	public function getLocalConnection(Entities\TuyaDevice $device): LocalApi
	{
		$connector = $device->getConnector();
		assert($connector instanceof Entities\TuyaConnector);

		if (!array_key_exists($device->getId()->toString(), $this->localConnections)) {
			$findChildrenDevicesQuery = new Queries\FindDevices();
			$findChildrenDevicesQuery->forParent($device);

			$children = $this->devicesRepository->findAllBy($findChildrenDevicesQuery, Entities\TuyaDevice::class);

			assert(is_string($device->getLocalKey()));
			assert(is_string($device->getIpAddress()));

			$connection = $this->localApiFactory->create(
				$device->getIdentifier(),
				null,
				null,
				$device->getLocalKey(),
				$device->getIpAddress(),
				$device->getProtocolVersion(),
				array_map(function (Entities\TuyaDevice $child): ValueObjects\LocalChild {
					assert(is_string($child->getNodeId()));

					return $this->objectMapper->process(
						[
							'identifier' => $child->getIdentifier(),
							'node_id' => $child->getNodeId(),
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getCloudApiConnection(Entities\TuyaConnector $connector): OpenApi
	{
		if ($this->cloudApiConnection === null) {
			assert(is_string($connector->getAccessId()));
			assert(is_string($connector->getAccessSecret()));

			$this->cloudApiConnection = $this->openApiFactory->create(
				$connector->getIdentifier(),
				$connector->getAccessId(),
				$connector->getAccessSecret(),
				$connector->getOpenApiEndpoint(),
			);
		}

		return $this->cloudApiConnection;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getCloudWsConnection(Entities\TuyaConnector $connector): OpenPulsar
	{
		if ($this->cloudWsConnection === null) {
			assert(is_string($connector->getAccessId()));
			assert(is_string($connector->getAccessSecret()));

			$this->cloudWsConnection = $this->openPulsarFactory->create(
				$connector->getIdentifier(),
				$connector->getAccessId(),
				$connector->getAccessSecret(),
				$connector->getOpenPulsarTopic(),
				$connector->getOpenPulsarEndpoint(),
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
