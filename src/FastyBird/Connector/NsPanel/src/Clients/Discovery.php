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

use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Documents;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Events as DevicesEvents;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use Nette;
use Psr\EventDispatcher as PsrEventDispatcher;
use React\Promise;
use Throwable;
use TypeError;
use ValueError;
use function array_merge;

/**
 * Connector sub-devices discovery client
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Discovery
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Documents\Connectors\Connector $connector,
		private readonly API\LanApiFactory $lanApiFactory,
		private readonly Queue\Queue $queue,
		private readonly Helpers\MessageBuilder $messageBuilder,
		private readonly Helpers\Devices\Gateway $gatewayHelper,
		private readonly NsPanel\Logger $logger,
		private readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		private readonly PsrEventDispatcher\EventDispatcherInterface|null $dispatcher = null,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function discover(Documents\Devices\Gateway|null $onlyGateway = null): void
	{
		$promises = [];

		if ($onlyGateway !== null) {
			$this->logger->debug(
				'Starting sub-devices discovery for selected NS Panel',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
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
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'discovery-client',
					'connector' => [
						'id' => $this->connector->getId()->toString(),
					],
				],
			);

			$findDevicesQuery = new Queries\Configuration\FindGatewayDevices();
			$findDevicesQuery->forConnector($this->connector);

			$gateways = $this->devicesConfigurationRepository->findAllBy(
				$findDevicesQuery,
				Documents\Devices\Gateway::class,
			);

			foreach ($gateways as $gateway) {
				$promises[] = $this->discoverSubDevices($gateway);
			}
		}

		Promise\all($promises)
			->then(function (): void {
				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\Sources\Connector::NS_PANEL,
						'Devices discovery finished',
					),
				);
			})
			->catch(function (Throwable $ex): void {
				$this->dispatcher?->dispatch(
					new DevicesEvents\TerminateConnector(
						MetadataTypes\Sources\Connector::NS_PANEL,
						'Devices discovery failed',
						$ex,
					),
				);
			});
	}

	public function disconnect(): void
	{
		// Nothing to do here
	}

	/**
	 * @return Promise\PromiseInterface<true>
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function discoverSubDevices(
		Documents\Devices\Gateway $gateway,
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
				->then(function (API\Messages\Response\GetSubDevices $response) use ($deferred, $gateway): void {
					$this->handleFoundSubDevices($gateway, $response);

					$deferred->resolve(true);
				})
				->catch(static function (Throwable $ex) use ($deferred): void {
					$deferred->reject($ex);
				});
		} catch (Exceptions\LanApiCall | Exceptions\LanApiError $ex) {
			$this->logger->error(
				'Loading sub-devices from NS Panel failed',
				[
					'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
					'type' => 'discovery-client',
					'exception' => ApplicationHelpers\Logger::buildException($ex),
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

	private function handleFoundSubDevices(
		Documents\Devices\Gateway $gateway,
		API\Messages\Response\GetSubDevices $subDevices,
	): void
	{
		foreach ($subDevices->getData()->getDevicesList() as $subDevice) {
			// Ignore third-party sub-devices (registered as virtual devices via connector)
			if ($subDevice->getThirdSerialNumber() !== null) {
				continue;
			}

			try {
				$this->queue->append(
					$this->messageBuilder->create(
						Queue\Messages\StoreSubDevice::class,
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
						'source' => MetadataTypes\Sources\Connector::NS_PANEL->value,
						'type' => 'discovery-client',
						'exception' => ApplicationHelpers\Logger::buildException($ex),
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
	}

}
