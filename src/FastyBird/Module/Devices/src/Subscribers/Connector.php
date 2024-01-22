<?php declare(strict_types = 1);

/**
 * StateEntities.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           22.10.22
 */

namespace FastyBird\Module\Devices\Subscribers;

use Doctrine\DBAL;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Events;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\Utilities;
use Nette;
use Symfony\Component\EventDispatcher;

/**
 * Devices state entities events
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorsPropertiesConfigurationRepository,
		private readonly Models\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly Models\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly Models\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly Models\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly Models\States\ConnectorPropertiesManager $connectorPropertiesStatesManager,
		private readonly Models\States\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly Models\States\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly Utilities\ConnectorConnection $connectorConnectionManager,
		private readonly Utilities\DeviceConnection $deviceConnectionManager,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\BeforeConnectorExecutionStart::class => 'executionStarting',
			Events\AfterConnectorExecutionStart::class => 'executionStarted',

			Events\AfterConnectorExecutionTerminate::class => 'executionTerminated',
		];
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function executionStarting(Events\BeforeConnectorExecutionStart $event): void
	{
		$this->resetConnector(
			$event->getConnector(),
			MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::UNKNOWN),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function executionStarted(Events\AfterConnectorExecutionStart $event): void
	{
		$this->connectorConnectionManager->setState(
			$event->getConnector(),
			MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::RUNNING),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function executionTerminated(Events\AfterConnectorExecutionTerminate $event): void
	{
		$this->connectorConnectionManager->setState(
			$event->getConnector(),
			MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STOPPED),
		);

		$this->resetConnector(
			$event->getConnector(),
			MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::DISCONNECTED),
		);
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function resetConnector(
		MetadataDocuments\DevicesModule\Connector $connector,
		MetadataTypes\ConnectionState $state,
	): void
	{
		$findConnectorPropertiesQuery = new Queries\Configuration\FindConnectorDynamicProperties();
		$findConnectorPropertiesQuery->forConnector($connector);

		$properties = $this->connectorsPropertiesConfigurationRepository->findAllBy(
			$findConnectorPropertiesQuery,
			MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class,
		);

		foreach ($properties as $property) {
			$this->connectorPropertiesStatesManager->setValidState($property, false);
		}

		$findDevicesQuery = new Queries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($connector);

		$devices = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);

		foreach ($devices as $device) {
			$this->resetDevice($device, $state);
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function resetDevice(
		MetadataDocuments\DevicesModule\Device $device,
		MetadataTypes\ConnectionState $state,
	): void
	{
		$this->deviceConnectionManager->setState($device, $state);

		$findDevicePropertiesQuery = new Queries\Configuration\FindDeviceDynamicProperties();
		$findDevicePropertiesQuery->forDevice($device);

		$properties = $this->devicesPropertiesConfigurationRepository->findAllBy(
			$findDevicePropertiesQuery,
			MetadataDocuments\DevicesModule\DeviceDynamicProperty::class,
		);

		foreach ($properties as $property) {
			$this->devicePropertiesStatesManager->setValidState($property, false);
		}

		$findChannelsQuery = new Queries\Configuration\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

		foreach ($channels as $channel) {
			$findChannelPropertiesQuery = new Queries\Configuration\FindChannelDynamicProperties();
			$findChannelPropertiesQuery->forChannel($channel);

			$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
				$findChannelPropertiesQuery,
				MetadataDocuments\DevicesModule\ChannelDynamicProperty::class,
			);

			foreach ($properties as $property) {
				$this->channelPropertiesStatesManager->setValidState($property, false);
			}
		}
	}

}
