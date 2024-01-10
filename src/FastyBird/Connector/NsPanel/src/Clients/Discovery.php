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
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use React\Promise;
use Throwable;
use function array_key_exists;
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

	public function __construct(
		private readonly MetadataDocuments\DevicesModule\Connector $connector,
		private readonly API\LanApiFactory $lanApiFactory,
		private readonly Queue\Queue $queue,
		private readonly Helpers\Entity $entityHelper,
		private readonly Helpers\Devices\Gateway $gatewayHelper,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function discover(MetadataDocuments\DevicesModule\Device|null $onlyGateway = null): void
	{
		$promises = [];

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

			$promises[] = $this->discoverSubDevices($onlyGateway);

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

			$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
			$findDevicesQuery->forConnector($this->connector);
			$findDevicesQuery->byType(Entities\Devices\Gateway::TYPE);

			$gateways = $this->devicesConfigurationRepository->findAllBy($findDevicesQuery);

			foreach ($gateways as $gateway) {
				$promises[] = $this->discoverSubDevices($gateway);
			}
		}

		Promise\all($promises)
			->then(function (array $results): void {
				$foundSubDevices = [];

				foreach ($results as $result) {
					foreach ($result as $device) {
						if (!array_key_exists($device->getParent()->toString(), $foundSubDevices)) {
							$foundSubDevices[$device->getParent()->toString()] = [];
						}

						$foundSubDevices[$device->getParent()->toString()][] = $device;
					}
				}

				$this->emit('finished', [$foundSubDevices]);
			})
			->catch(function (): void {
				$this->emit('finished', [[]]);
			});
	}

	public function disconnect(): void
	{
		// Nothing to do here
	}

	/**
	 * @return Promise\PromiseInterface<array<Entities\Clients\DiscoveredSubDevice>>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function discoverSubDevices(
		MetadataDocuments\DevicesModule\Device $gateway,
	): Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$lanApi = $this->lanApiFactory->create(
			$this->connector->getIdentifier(),
		);

		if (
			$this->gatewayHelper->getIpAddress($gateway) === null
			|| $this->gatewayHelper->getAccessToken($gateway) === null
		) {
			return Promise\reject(new Exceptions\InvalidArgument('NS Panel is not configured'));
		}

		try {
			$lanApi->getSubDevices(
				$this->gatewayHelper->getIpAddress($gateway),
				$this->gatewayHelper->getAccessToken($gateway),
			)
				->then(function (Entities\API\Response\GetSubDevices $response) use ($deferred, $gateway): void {
					$deferred->resolve($this->handleFoundSubDevices($gateway, $response));
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});
		} catch (Exceptions\LanApiCall | Exceptions\LanApiError $ex) {
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
						'id' => $gateway->getId()->toString(),
					],
				],
			);

			$deferred->reject($ex);
		}

		return $deferred->promise();
	}

	/**
	 * @return array<Entities\Clients\DiscoveredSubDevice>
	 */
	private function handleFoundSubDevices(
		MetadataDocuments\DevicesModule\Device $gateway,
		Entities\API\Response\GetSubDevices $subDevices,
	): array
	{
		$processedSubDevices = [];

		foreach ($subDevices->getData()->getDevicesList() as $subDevice) {
			// Ignore third-party sub-devices (registered as virtual devices via connector)
			if ($subDevice->getThirdSerialNumber() !== null) {
				continue;
			}

			try {
				$processedSubDevices[] = $this->entityHelper->create(
					Entities\Clients\DiscoveredSubDevice::class,
					array_merge(
						$subDevice->toArray(),
						[
							'parent' => $gateway->getId(),
						],
					),
				);

				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\StoreSubDevice::class,
						array_merge(
							[
								'connector' => $gateway->getConnector(),
								'gateway' => $gateway->getId(),
							],
							$subDevice->toArray(),
						),
					),
				);
			} catch (Exceptions\Runtime $ex) {
				$this->logger->error(
					'Could not map discovered device to result',
					[
						'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
						'type' => 'discovery-client',
						'exception' => BootstrapHelpers\Logger::buildException($ex),
						'connector' => [
							'id' => $this->connector->getId()->toString(),
						],
						'device' => [
							'identifier' => $subDevice->getSerialNumber(),
						],
					],
				);
			}
		}

		return $processedSubDevices;
	}

}
