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
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use React\EventLoop;
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
abstract class Periodic implements Writer
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 5.0;

	private const HANDLER_DEBOUNCE_INTERVAL = 2_500.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const HANDLER_PENDING_DELAY = 2_000.0;

	/** @var array<string, MetadataDocuments\DevicesModule\Device>  */
	private array $devices = [];

	/** @var array<string, array<string, MetadataDocuments\DevicesModule\ChannelProperty>>  */
	private array $properties = [];

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, DateTimeInterface> */
	private array $processedProperties = [];
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	/** @var array<string, bool|float|int|string|DateTimeInterface|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|MetadataTypes\CoverPayload|null> */
	private array $lastReportedValue = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		protected readonly MetadataDocuments\DevicesModule\Connector $connector,
		protected readonly Helpers\Entity $entityHelper,
		protected readonly Helpers\Devices\ThirdPartyDevice $thirdPartyDeviceHelper,
		protected readonly Queue\Queue $queue,
		protected readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		protected readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		protected readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		protected readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStatesManager,
		protected readonly DateTimeFactory\Factory $dateTimeFactory,
		protected readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 */
	public function connect(): void
	{
		$this->processedDevices = [];
		$this->processedProperties = [];
		$this->lastReportedValue = [];

		$findDevicesQuery = new DevicesQueries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesConfigurationRepository->findAllBy($findDevicesQuery) as $device) {
			if (
				$device->getType() !== Entities\Devices\SubDevice::TYPE
				&& $device->getType() !== Entities\Devices\ThirdPartyDevice::TYPE
			) {
				continue;
			}

			$this->devices[$device->getId()->toString()] = $device;

			if (!array_key_exists($device->getId()->toString(), $this->properties)) {
				$this->properties[$device->getId()->toString()] = [];
			}

			$findChannelsQuery = new DevicesQueries\Configuration\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsConfigurationRepository->findAllBy($findChannelsQuery);

			foreach ($channels as $channel) {
				$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelProperties();
				$findChannelPropertiesQuery->forChannel($channel);
				$findChannelPropertiesQuery->settable(true);

				$properties = $this->channelsPropertiesConfigurationRepository->findAllBy($findChannelPropertiesQuery);

				foreach ($properties as $property) {
					if (
						(
							$device->getType() === Entities\Devices\SubDevice::TYPE
							&& $property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
						) || (
							$device->getType() === Entities\Devices\ThirdPartyDevice::TYPE
						)
					) {
						$this->properties[$device->getId()->toString()][$property->getId()->toString()] = $property;
					}
				}
			}
		}

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
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function handleCommunication(): void
	{
		foreach ($this->devices as $device) {
			if (!in_array($device->getId()->toString(), $this->processedDevices, true)) {
				$this->processedDevices[] = $device->getId()->toString();

				if ($this->writeChannelsProperty($device)) {
					$this->registerLoopHandler();

					return;
				}
			}
		}

		$this->processedDevices = [];

		$this->registerLoopHandler();
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function writeChannelsProperty(MetadataDocuments\DevicesModule\Device $device): bool
	{
		if (!array_key_exists($device->getId()->toString(), $this->properties)) {
			return false;
		}

		foreach ($this->properties[$device->getId()->toString()] as $property) {
			if (
				$device->getType() === Entities\Devices\SubDevice::TYPE
				&& $property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
			) {
				$this->writeSubDeviceChannelProperty($device, $property);
			} elseif ($device->getType() === Entities\Devices\ThirdPartyDevice::TYPE) {
				$this->writeThirdPartyDeviceChannelProperty($device, $property);
			}
		}

		return false;
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function writeSubDeviceChannelProperty(
		MetadataDocuments\DevicesModule\Device $device,
		MetadataDocuments\DevicesModule\ChannelDynamicProperty $property,
	): bool
	{
		$now = $this->dateTimeFactory->getNow();

		$debounce = array_key_exists($property->getId()->toString(), $this->processedProperties)
			? $this->processedProperties[$property->getId()->toString()]
			: false;

		if (
			$debounce !== false
			&& (float) $now->format('Uv') - (float) $debounce->format('Uv') < self::HANDLER_DEBOUNCE_INTERVAL
		) {
			return false;
		}

		$this->processedProperties[$property->getId()->toString()] = $now;

		$state = $this->channelPropertiesStatesManager->getValue($property);

		if ($state === null) {
			return false;
		}

		if ($state->getExpectedValue() === null) {
			return false;
		}

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
						'connector' => $device->getConnector(),
						'device' => $device->getId(),
						'channel' => $property->getChannel(),
					],
				),
			);
		}

		return false;
	}

	/**
	 * @throws DevicesExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 */
	private function writeThirdPartyDeviceChannelProperty(
		MetadataDocuments\DevicesModule\Device $device,
		MetadataDocuments\DevicesModule\ChannelProperty $property,
	): bool
	{
		$now = $this->dateTimeFactory->getNow();

		$serialNumber = $this->thirdPartyDeviceHelper->getGatewayIdentifier($device);

		if ($serialNumber === null) {
			$this->queue->append(
				$this->entityHelper->create(
					Entities\Messages\StoreDeviceConnectionState::class,
					[
						'connector' => $device->getConnector(),
						'identifier' => $device->getIdentifier(),
						'state' => MetadataTypes\ConnectionState::STATE_ALERT,
					],
				),
			);

			unset($this->devices[$device->getId()->toString()]);

			return false;
		}

		if (
			$property instanceof MetadataDocuments\DevicesModule\ChannelDynamicProperty
			|| $property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty
		) {
			$state = $this->channelPropertiesStatesManager->getValue($property);

			if ($state === null || $state->isValid() === false) {
				return false;
			}

			$propertyValue = $state->getExpectedValue() ?? $state->getActualValue();

			if ($propertyValue === null) {
				return false;
			}
		} elseif ($property instanceof MetadataDocuments\DevicesModule\ChannelVariableProperty) {
			$propertyValue = $property->getValue();
		} else {
			return false;
		}

		$debounce = array_key_exists($property->getId()->toString(), $this->processedProperties)
			? $this->processedProperties[$property->getId()->toString()]
			: false;

		if (
			$debounce !== false
			&& (float) $now->format('Uv') - (float) $debounce->format('Uv') < self::HANDLER_DEBOUNCE_INTERVAL
		) {
			return false;
		}

		$lastReportedValue = array_key_exists(
			$property->getId()->toString(),
			$this->lastReportedValue,
		)
			? $this->lastReportedValue[$property->getId()->toString()]
			: null;

		if ($lastReportedValue === $propertyValue) {
			return false;
		}

		unset($this->processedProperties[$property->getId()->toString()]);

		$this->processedProperties[$property->getId()->toString()] = $now;
		$this->lastReportedValue[$property->getId()->toString()] = $propertyValue;

		$this->queue->append(
			$this->entityHelper->create(
				Entities\Messages\WriteThirdPartyDeviceState::class,
				[
					'connector' => $device->getConnector(),
					'device' => $device->getId(),
					'channel' => $property->getChannel(),
				],
			),
		);

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
