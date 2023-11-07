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
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use React\Promise;
use Throwable;
use function array_key_exists;
use function array_merge;
use function assert;
use function is_array;

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
		private readonly Entities\NsPanelConnector $connector,
		private readonly API\LanApiFactory $lanApiFactory,
		private readonly Queue\Queue $queue,
		private readonly Helpers\Entity $entityHelper,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
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

			$findDevicesQuery = new Queries\Entities\FindGatewayDevices();
			$findDevicesQuery->forConnector($this->connector);

			foreach ($this->devicesRepository->findAllBy(
				$findDevicesQuery,
				Entities\Devices\Gateway::class,
			) as $gateway) {
				$promises[] = $this->discoverSubDevices($gateway);
			}
		}

		Promise\all($promises)
			->then(function (array $results): void {
				$foundSubDevices = [];

				foreach ($results as $result) {
					assert(is_array($result));

					foreach ($result as $device) {
						assert($device instanceof Entities\Clients\DiscoveredSubDevice);

						if (!array_key_exists($device->getParent()->toString(), $foundSubDevices)) {
							$foundSubDevices[$device->getParent()->toString()] = [];
						}

						$foundSubDevices[$device->getParent()->toString()][] = $device;
					}
				}

				$this->emit('finished', [$foundSubDevices]);
			})
			->otherwise(function (): void {
				$this->emit('finished', [[]]);
			});
	}

	public function disconnect(): void
	{
		// Nothing to do here
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function discoverSubDevices(
		Entities\Devices\Gateway $gateway,
	): Promise\ExtendedPromiseInterface|Promise\PromiseInterface
	{
		$deferred = new Promise\Deferred();

		$lanApi = $this->lanApiFactory->create(
			$this->connector->getIdentifier(),
		);

		if ($gateway->getIpAddress() === null || $gateway->getAccessToken() === null) {
			return Promise\reject(new Exceptions\InvalidArgument('NS Panel is not configured'));
		}

		try {
			$lanApi->getSubDevices(
				$gateway->getIpAddress(),
				$gateway->getAccessToken(),
			)
				->then(function (Entities\API\Response\GetSubDevices $response) use ($deferred, $gateway): void {
					$deferred->resolve($this->handleFoundSubDevices($gateway, $response));
				})
				->otherwise(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});
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
		Entities\Devices\Gateway $gateway,
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
							'parent' => $gateway->getId()->toString(),
						],
					),
				);

				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\StoreSubDevice::class,
						array_merge(
							[
								'connector' => $this->connector->getId()->toString(),
								'gateway' => $gateway->getId()->toString(),
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
