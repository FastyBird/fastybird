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
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use React\EventLoop;
use function array_key_exists;
use function array_merge;
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
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	/** @var array<string, bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null> */
	private array $lastReportedValue = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		private readonly Entities\NsPanelConnector $connector,
		private readonly Helpers\Entity $entityHelper,
		private readonly Queue\Queue $queue,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	public function connect(): void
	{
		$this->processedDevices = [];
		$this->processedProperties = [];
		$this->lastReportedValue = [];

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			function (): void {
				$this->registerLoopHandler();
			},
		);
	}

	public function disconnect(): void
	{
		if ($this->handlerTimer !== null) {
			$this->eventLoop->cancelTimer($this->handlerTimer);

			$this->handlerTimer = null;
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function handleCommunication(): void
	{
		$findDevicesQuery = new Queries\FindSubDevices();
		$findDevicesQuery->forConnector($this->connector);

		$subDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\Devices\SubDevice::class,
		);

		$findDevicesQuery = new Queries\FindThirdPartyDevices();
		$findDevicesQuery->forConnector($this->connector);

		$thirdPartyDevices = $this->devicesRepository->findAllBy(
			$findDevicesQuery,
			Entities\Devices\ThirdPartyDevice::class,
		);

		foreach (array_merge($subDevices, $thirdPartyDevices) as $device) {
			if (!in_array($device->getId()->toString(), $this->processedDevices, true)) {
				$this->processedDevices[] = $device->getId()->toString();

				if (
					$device instanceof Entities\Devices\SubDevice
					&& !$this->deviceConnectionManager->getState($device)->equalsValue(
						MetadataTypes\ConnectionState::STATE_ALERT,
					)
				) {
					if ($this->writeSubDeviceChannelProperty($device)) {
						$this->registerLoopHandler();

						return;
					}
				} elseif (
					$device instanceof Entities\Devices\ThirdPartyDevice
					&& !$this->deviceConnectionManager->getState($device)->equalsValue(
						MetadataTypes\ConnectionState::STATE_ALERT,
					)
				) {
					if ($this->writeThirdPartyDeviceChannelProperty($device)) {
						$this->registerLoopHandler();

						return;
					}
				}
			}
		}

		$this->processedDevices = [];

		$this->registerLoopHandler();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function writeSubDeviceChannelProperty(
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

			$properties = $this->channelsPropertiesRepository->findAllBy(
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
						$property->getId()->toString(),
						$this->processedProperties,
					)
						? $this->processedProperties[$property->getId()->toString()]
						: false;

					if (
						$debounce !== false
						&& (float) $now->format('Uv') - (float) $debounce->format(
							'Uv',
						) < self::HANDLER_DEBOUNCE_INTERVAL
					) {
						continue;
					}

					unset($this->processedProperties[$property->getId()->toString()]);

					$pending = $state->getPending();

					if (
						$pending === true
						|| (
							$pending instanceof DateTimeInterface
							&& (float) $now->format('Uv') - (float) $pending->format('Uv') > self::HANDLER_PENDING_DELAY
						)
					) {
						$this->processedProperties[$property->getId()->toString()] = $now;

						$this->queue->append(
							$this->entityHelper->create(
								Entities\Messages\WriteSubDeviceState::class,
								[
									'connector' => $this->connector->getId()->toString(),
									'device' => $device->getId()->toString(),
									'channel' => $channel->getId()->toString(),
								],
							),
						);
					}
				}
			}
		}

		return false;
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function writeThirdPartyDeviceChannelProperty(
		Entities\Devices\ThirdPartyDevice $device,
	): bool
	{
		$now = $this->dateTimeFactory->getNow();

		$serialNumber = $device->getGatewayIdentifier();

		if ($serialNumber === null) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $this->connector->getId()->toString(),
						'identifier' => $device->getIdentifier(),
						'state' => MetadataTypes\ConnectionState::STATE_ALERT,
					],
				),
			);

			return false;
		}

		$findChannelsQuery = new Queries\FindChannels();
		$findChannelsQuery->forDevice($device);

		$channels = $this->channelsRepository->findAllBy($findChannelsQuery, Entities\NsPanelChannel::class);

		foreach ($channels as $channel) {
			$findChannelPropertiesQuery = new DevicesQueries\FindChannelMappedProperties();
			$findChannelPropertiesQuery->forChannel($channel);

			$properties = $this->channelsPropertiesRepository->findAllBy(
				$findChannelPropertiesQuery,
				DevicesEntities\Channels\Properties\Mapped::class,
			);

			foreach ($properties as $property) {
				$state = $this->channelPropertiesStates->getValue($property);

				if ($state === null || $state->isValid() === false) {
					continue;
				}

				$propertyValue = $state->getExpectedValue() ?? $state->getActualValue();

				if ($propertyValue === null) {
					continue;
				}

				$debounce = array_key_exists(
					$property->getId()->toString(),
					$this->processedProperties,
				)
					? $this->processedProperties[$property->getId()->toString()]
					: false;

				if (
					$debounce !== false
					&& (float) $now->format('Uv') - (float) $debounce->format(
						'Uv',
					) < self::HANDLER_DEBOUNCE_INTERVAL
				) {
					continue;
				}

				$lastReportedValue = array_key_exists(
					$property->getId()->toString(),
					$this->lastReportedValue,
				)
					? $this->lastReportedValue[$property->getId()->toString()]
					: null;

				if ($lastReportedValue === $propertyValue) {
					continue;
				}

				unset($this->processedProperties[$property->getId()->toString()]);

				$this->processedProperties[$property->getId()->toString()] = $now;
				$this->lastReportedValue[$property->getId()->toString()] = $propertyValue;

				$this->queue->append(
					$this->entityHelper->create(
						Entities\Messages\WriteThirdPartyDeviceState::class,
						[
							'connector' => $this->connector->getId()->toString(),
							'device' => $device->getId()->toString(),
							'channel' => $channel->getId()->toString(),
						],
					),
				);

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
