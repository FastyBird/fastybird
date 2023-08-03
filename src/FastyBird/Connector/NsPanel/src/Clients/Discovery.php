<?php declare(strict_types = 1);

/**
 * Discovery.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           23.07.23
 */

namespace FastyBird\Connector\NsPanel\Clients;

use Evenement;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Consumers;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use Orisai\ObjectMapper;
use React\EventLoop;
use function array_merge;

/**
 * Connector sub-devices discovery client
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Discovery implements Evenement\EventEmitterInterface
{

	use Nette\SmartObject;
	use Evenement\EventEmitterTrait;

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		private readonly Entities\NsPanelConnector $connector,
		private readonly API\LanApiFactory $lanApiApiFactory,
		private readonly Consumers\Messages $consumer,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly ObjectMapper\Processing\Processor $entityMapper,
		private readonly NsPanel\Logger $logger,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function discover(Entities\Devices\Gateway|null $onlyGateway = null): void
	{
		$foundSubDevices = [];

		if ($onlyGateway !== null) {
			$this->logger->debug(
				'Starting sub-devices discovery for selected NS Panel',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'discovery-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'device' => [
						'id' => $onlyGateway->getId()->toString(),
					],
				],
			);

			$foundSubDevices[$onlyGateway->getIdentifier()] = $this->discoverSubDevices($onlyGateway);

		} else {
			$this->logger->debug(
				'Starting sub-devices discovery for all registered NS Panels',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'discovery-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$findDevicesQuery = new Queries\FindGatewayDevices();
			$findDevicesQuery->forConnector($this->connector);

			foreach ($this->devicesRepository->findAllBy(
				$findDevicesQuery,
				Entities\Devices\Gateway::class,
			) as $gateway) {
				$foundSubDevices[$gateway->getId()->toString()] = $this->discoverSubDevices($gateway);
			}
		}

		$this->emit('finished', [$foundSubDevices]);
	}

	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);
			$this->handlerTimer = null;
		}
	}

	/**
	 * @return array<Entities\Clients\DiscoveredSubDevice>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function discoverSubDevices(Entities\Devices\Gateway $device): array
	{
		$lanApiApi = $this->lanApiApiFactory->create(
			$this->connector->getIdentifier(),
		);

		if ($device->getIpAddress() === null || $device->getAccessToken() === null) {
			return [];
		}

		try {
			$subDevices = $lanApiApi->getSubDevices(
				$device->getIpAddress(),
				$device->getAccessToken(),
				API\LanApi::GATEWAY_PORT,
				false,
			);
		} catch (Exceptions\LanApiCall $ex) {
			$this->logger->error(
				'Loading sub-devices from NS Panel failed',
				[
					'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
					'type' => 'discovery-client',
					'exception' => BootstrapHelpers\Logger::buildException($ex),
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
					'device' => [
						'id' => $device->getId()->toString(),
					],
				],
			);

			return [];
		}

		return $this->handleFoundSubDevices($device, $subDevices);
	}

	/**
	 * @return array<Entities\Clients\DiscoveredSubDevice>
	 */
	private function handleFoundSubDevices(
		Entities\Devices\Gateway $gateway,
		Entities\API\Response\GetSubDevices $subDevices,
	): array
	{
		$processedSubDevices = [];

		foreach ($subDevices->getData()->getDevicesList() as $subDevice) {
			// Ignore third-party sub devices (registered as virtual devices via connector)
			if ($subDevice->getThirdSerialNumber() !== null) {
				continue;
			}

			try {
				$options = new ObjectMapper\Processing\Options();
				$options->setAllowUnknownFields();

				$processedSubDevices[] = $this->entityMapper->process(
					$subDevice->toArray(),
					Entities\Clients\DiscoveredSubDevice::class,
					$options,
				);

				$this->consumer->append(
					$this->entityMapper->process(
						array_merge(
							[
								'connector' => $this->connector->getId()->toString(),
								'gateway' => $gateway->getId()->toString(),
							],
							$subDevice->toArray(),
						),
						Entities\Messages\DiscoveredSubDevice::class,
						$options,
					),
				);
			} catch (ObjectMapper\Exception\InvalidData $ex) {
				$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
					new ObjectMapper\Printers\TypeToStringConverter(),
				);

				$this->logger->error(
					'Could not map discovered device to result',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
						'type' => 'discovery-client',
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
						'device' => [
							'identifier' => $subDevice->getThirdSerialNumber(),
						],
						'error' => $errorPrinter->printError($ex),
					],
				);
			}
		}

		return $processedSubDevices;
	}

}
