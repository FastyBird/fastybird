<?php declare(strict_types = 1);

/**
 * Periodic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           12.07.23
 */

namespace FastyBird\Connector\NsPanel\Writers;

use DateTimeInterface;
use FastyBird\Connector\NsPanel;
use FastyBird\Connector\NsPanel\Clients;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Bootstrap\Helpers as BootstrapHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use React\EventLoop;
use Throwable;
use function array_key_exists;
use function in_array;

/**
 * Periodic properties writer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Periodic implements Writer
{

	use Nette\SmartObject;

	public const NAME = 'periodic';

	private const HANDLER_START_DELAY = 5.0;

	private const HANDLER_DEBOUNCE_INTERVAL = 500.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const HANDLER_PENDING_DELAY = 2_000.0;

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, DateTimeInterface> */
	private array $processedProperties = [];

	/** @var array<string, Clients\Client> */
	private array $clients = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		private readonly Helpers\Property $propertyStateHelper,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelPropertiesRepository,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly NsPanel\Logger $logger,
	)
	{
	}

	public function connect(
		Entities\NsPanelConnector $connector,
		Clients\Client $client,
	): void
	{
		$this->clients[$connector->getPlainId()] = $client;

		$this->processedDevices = [];
		$this->processedProperties = [];

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			function (): void {
				$this->registerLoopHandler();
			},
		);
	}

	public function disconnect(
		Entities\NsPanelConnector $connector,
		Clients\Client $client,
	): void
	{
		unset($this->clients[$connector->getPlainId()]);

		if ($this->clients === [] && $this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function handleCommunication(): void
	{
		foreach ($this->clients as $id => $client) {
			$devices = [];

			if ($client instanceof Clients\Gateway) {
				$findDevicesQuery = new Queries\FindSubDevices();
				$findDevicesQuery->byConnectorId(Uuid\Uuid::fromString($id));

				$devices = $this->devicesRepository->findAllBy(
					$findDevicesQuery,
					Entities\Devices\SubDevice::class,
				);
			} elseif ($client instanceof Clients\Device) {
				$findDevicesQuery = new Queries\FindThirdPartyDevices();
				$findDevicesQuery->byConnectorId(Uuid\Uuid::fromString($id));

				$devices = $this->devicesRepository->findAllBy(
					$findDevicesQuery,
					Entities\Devices\ThirdPartyDevice::class,
				);
			}

			foreach ($devices as $device) {
				if (!in_array($device->getPlainId(), $this->processedDevices, true)) {
					$this->processedDevices[] = $device->getPlainId();

					if (
						$client instanceof Clients\Gateway
						&& $device instanceof Entities\Devices\SubDevice
					) {
						if ($this->writeSubDeviceChannelProperty($client, $device)) {
							$this->registerLoopHandler();

							return;
						}
					} elseif (
						$client instanceof Clients\Device
						&& $device instanceof Entities\Devices\ThirdPartyDevice
					) {
						if ($this->writeThirdPartyDeviceChannelProperty($client, $device)) {
							$this->registerLoopHandler();

							return;
						}
					}
				}
			}
		}

		$this->processedDevices = [];

		$this->registerLoopHandler();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function writeSubDeviceChannelProperty(
		Clients\Gateway $client,
		Entities\Devices\SubDevice $device,
	): bool
	{
		$now = $this->dateTimeFactory->getNow();

		$findChannelsQuery = new Queries\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\NsPanelChannel::class);

		foreach ($channels as $channel) {
			$findChannelPropertiesQuery = new DevicesQueries\FindChannelDynamicProperties();
			$findChannelPropertiesQuery->forChannel($channel);

			$properties = $this->channelPropertiesRepository->findAllBy(
				$findChannelPropertiesQuery,
				DevicesEntities\Channels\Properties\Dynamic::class,
			);

			foreach ($properties as $property) {
				$state = $this->channelPropertiesStates->getValue($property);

				if ($state === null) {
					continue;
				}

				$expectedValue = DevicesUtilities\ValueHelper::flattenValue($state->getExpectedValue());

				if (
					$property->isSettable()
					&& $expectedValue !== null
					&& $state->isPending() === true
				) {
					$debounce = array_key_exists(
						$property->getPlainId(),
						$this->processedProperties,
					)
						? $this->processedProperties[$property->getPlainId()]
						: false;

					if (
						$debounce !== false
						&& (float) $now->format('Uv') - (float) $debounce->format(
							'Uv',
						) < self::HANDLER_DEBOUNCE_INTERVAL
					) {
						continue;
					}

					unset($this->processedProperties[$property->getPlainId()]);

					$pending = $state->getPending();

					if (
						$pending === true
						|| (
							$pending instanceof DateTimeInterface
							&& (float) $now->format('Uv') - (float) $pending->format('Uv') > self::HANDLER_PENDING_DELAY
						)
					) {
						$this->processedProperties[$property->getPlainId()] = $now;

						$client->writeChannelProperty($device, $channel, $property)
							->then(function () use ($property, $now): void {
								unset($this->processedProperties[$property->getPlainId()]);

								$state = $this->channelPropertiesStates->getValue($property);

								if ($state?->getExpectedValue() !== null) {
									$this->propertyStateHelper->setValue(
										$property,
										Utils\ArrayHash::from([
											DevicesStates\Property::PENDING_KEY => $now->format(
												DateTimeInterface::ATOM,
											),
										]),
									);
								}
							})
							->otherwise(function (Throwable $ex) use ($device, $channel, $property): void {
								$this->logger->error(
									'Could not write property state',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
										'type' => 'periodic-writer',
										'exception' => BootstrapHelpers\Logger::buildException($ex),
										'connector' => [
											'id' => $device->getConnector()->getPlainId(),
										],
										'device' => [
											'id' => $device->getPlainId(),
										],
										'channel' => [
											'id' => $channel->getPlainId(),
										],
										'property' => [
											'id' => $property->getPlainId(),
										],
									],
								);

								$this->propertyStateHelper->setValue(
									$property,
									Utils\ArrayHash::from([
										DevicesStates\Property::EXPECTED_VALUE_KEY => null,
										DevicesStates\Property::PENDING_KEY => false,
									]),
								);
							});

						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\LanApiCall
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function writeThirdPartyDeviceChannelProperty(
		Clients\Device $client,
		Entities\Devices\ThirdPartyDevice $device,
	): bool
	{
		$now = $this->dateTimeFactory->getNow();

		$findChannelsQuery = new Queries\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\NsPanelChannel::class);

		foreach ($channels as $channel) {
			$findChannelPropertiesQuery = new DevicesQueries\FindChannelMappedProperties();
			$findChannelPropertiesQuery->forChannel($channel);

			$properties = $this->channelPropertiesRepository->findAllBy(
				$findChannelPropertiesQuery,
				DevicesEntities\Channels\Properties\Mapped::class,
			);

			foreach ($properties as $property) {
				$state = $this->channelPropertiesStates->getValue($property);

				if ($state === null) {
					continue;
				}

				$propertyValue = $state->getExpectedValue() ?? $state->getActualValue();

				if ($propertyValue === null) {
					continue;
				}

				$debounce = array_key_exists(
					$property->getPlainId(),
					$this->processedProperties,
				)
					? $this->processedProperties[$property->getPlainId()]
					: false;

				if (
					$debounce !== false
					&& (float) $now->format('Uv') - (float) $debounce->format(
						'Uv',
					) < self::HANDLER_DEBOUNCE_INTERVAL
				) {
					continue;
				}

				unset($this->processedProperties[$property->getPlainId()]);

				$this->processedProperties[$property->getPlainId()] = $now;

				$client->writeChannelProperty($device, $channel, $property)
					->then(function () use ($property): void {
						unset($this->processedProperties[$property->getPlainId()]);
					})
					->otherwise(function (Throwable $ex) use ($device, $channel, $property): void {
						$this->logger->error(
							'Could not write property state',
							[
								'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL,
								'type' => 'periodic-writer',
								'exception' => BootstrapHelpers\Logger::buildException($ex),
								'connector' => [
									'id' => $device->getConnector()->getPlainId(),
								],
								'device' => [
									'id' => $device->getPlainId(),
								],
								'channel' => [
									'id' => $channel->getPlainId(),
								],
								'property' => [
									'id' => $property->getPlainId(),
								],
							],
						);
					});

				return true;
			}
		}

		return false;
	}

	private function registerLoopHandler(): void
	{
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::HANDLER_PROCESSING_INTERVAL,
			function (): void {
				$this->handleCommunication();
			},
		);
	}

}
