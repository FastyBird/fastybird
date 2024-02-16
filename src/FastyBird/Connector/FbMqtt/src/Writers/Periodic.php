<?php declare(strict_types = 1);

/**
 * Periodic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           18.01.23
 */

namespace FastyBird\Connector\FbMqtt\Writers;

use DateTimeInterface;
use FastyBird\Connector\FbMqtt\Documents;
use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\Connector\FbMqtt\Queries;
use FastyBird\Connector\FbMqtt\Queue;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Documents as DevicesDocuments;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use React\EventLoop;
use function array_key_exists;
use function array_merge;
use function in_array;
use function is_bool;
use function React\Async\async;
use function React\Async\await;

/**
 * Periodic properties writer
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Periodic
{

	use Nette\SmartObject;

	private const HANDLER_START_DELAY = 5.0;

	private const HANDLER_DEBOUNCE_INTERVAL = 2_500.0;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const HANDLER_PENDING_DELAY = 2_000.0;

	/** @var array<string, Documents\Devices\Device>  */
	private array $devices = [];

	/** @var array<string, array<string, DevicesDocuments\Devices\Properties\Dynamic|DevicesDocuments\Channels\Properties\Dynamic>>  */
	private array $properties = [];

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, DateTimeInterface> */
	private array $processedProperties = [];

	private EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		protected readonly Documents\Connectors\Connector $connector,
		protected readonly Helpers\MessageBuilder $messageBuilder,
		protected readonly Queue\Queue $queue,
		protected readonly DevicesModels\Configuration\Devices\Repository $devicesConfigurationRepository,
		protected readonly DevicesModels\Configuration\Channels\Repository $channelsConfigurationRepository,
		private readonly DevicesModels\Configuration\Devices\Properties\Repository $devicesPropertiesConfigurationRepository,
		private readonly DevicesModels\Configuration\Channels\Properties\Repository $channelsPropertiesConfigurationRepository,
		private readonly DevicesModels\States\Async\DevicePropertiesManager $devicePropertiesStatesManager,
		private readonly DevicesModels\States\Async\ChannelPropertiesManager $channelPropertiesStatesManager,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
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

		$findDevicesQuery = new Queries\Configuration\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		$devices = $this->devicesConfigurationRepository->findAllBy(
			$findDevicesQuery,
			Documents\Devices\Device::class,
		);

		foreach ($devices as $device) {
			$this->devices[$device->getId()->toString()] = $device;

			if (!array_key_exists($device->getId()->toString(), $this->properties)) {
				$this->properties[$device->getId()->toString()] = [];
			}

			$findDevicePropertiesQuery = new DevicesQueries\Configuration\FindDeviceDynamicProperties();
			$findDevicePropertiesQuery->forDevice($device);
			$findDevicePropertiesQuery->settable(true);

			$properties = $this->devicesPropertiesConfigurationRepository->findAllBy(
				$findDevicePropertiesQuery,
				DevicesDocuments\Devices\Properties\Dynamic::class,
			);

			foreach ($properties as $property) {
				$this->properties[$device->getId()->toString()][$property->getId()->toString()] = $property;
			}

			$findChannelsQuery = new Queries\Configuration\FindChannels();
			$findChannelsQuery->forDevice($device);

			$channels = $this->channelsConfigurationRepository->findAllBy(
				$findChannelsQuery,
				Documents\Channels\Channel::class,
			);

			foreach ($channels as $channel) {
				$findChannelPropertiesQuery = new DevicesQueries\Configuration\FindChannelDynamicProperties();
				$findChannelPropertiesQuery->forChannel($channel);
				$findChannelPropertiesQuery->settable(true);

				$properties = $this->channelsPropertiesConfigurationRepository->findAllBy(
					$findChannelPropertiesQuery,
					DevicesDocuments\Channels\Properties\Dynamic::class,
				);

				foreach ($properties as $property) {
					$this->properties[$device->getId()->toString()][$property->getId()->toString()] = $property;
				}
			}
		}

		$this->eventLoop->addTimer(
			self::HANDLER_START_DELAY,
			async(function (): void {
				$this->registerLoopHandler();
			}),
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
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function handleCommunication(): void
	{
		foreach ($this->devices as $device) {
			if (!in_array($device->getId()->toString(), $this->processedDevices, true)) {
				$this->processedDevices[] = $device->getId()->toString();

				if ($this->writeProperty($device)) {
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
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function writeProperty(Documents\Devices\Device $device): bool
	{
		$now = $this->dateTimeFactory->getNow();

		if (!array_key_exists($device->getId()->toString(), $this->properties)) {
			return false;
		}

		foreach ($this->properties[$device->getId()->toString()] as $property) {
			$debounce = array_key_exists($property->getId()->toString(), $this->processedProperties)
				? $this->processedProperties[$property->getId()->toString()]
				: false;

			if (
				$debounce !== false
				&& (float) $now->format('Uv') - (float) $debounce->format('Uv') < self::HANDLER_DEBOUNCE_INTERVAL
			) {
				continue;
			}

			$this->processedProperties[$property->getId()->toString()] = $now;

			if ($property instanceof DevicesDocuments\Devices\Properties\Dynamic) {
				if ($this->writeDeviceProperty($device, $property)) {
					return true;
				}
			} elseif ($property instanceof DevicesDocuments\Channels\Properties\Dynamic) {
				if ($this->writeChannelProperty($device, $property)) {
					return true;
				}
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
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function writeDeviceProperty(
		Documents\Devices\Device $device,
		DevicesDocuments\Devices\Properties\Dynamic $property,
	): bool
	{
		$now = $this->dateTimeFactory->getNow();

		$state = await(
			$this->devicePropertiesStatesManager->read(
				$property,
				MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::FB_MQTT),
			),
		);

		if (is_bool($state)) {
			return $state;
		} elseif (!$state instanceof DevicesDocuments\States\Properties\Device) {
			// Property state is not set
			return false;
		}

		if ($state->getGet()->getExpectedValue() === null) {
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
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\WriteDevicePropertyState::class,
					[
						'connector' => $device->getConnector(),
						'device' => $device->getId(),
						'property' => $property->getId(),
						'state' => array_merge(
							$state->getGet()->toArray(),
							[
								'id' => $state->getId(),
								'valid' => $state->isValid(),
								'pending' => $state->getPending() instanceof DateTimeInterface
									? $state->getPending()->format(DateTimeInterface::ATOM)
									: $state->getPending(),
							],
						),
					],
				),
			);

			return true;
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
	 * @throws ToolsExceptions\InvalidArgument
	 */
	private function writeChannelProperty(
		Documents\Devices\Device $device,
		DevicesDocuments\Channels\Properties\Dynamic $property,
	): bool
	{
		$now = $this->dateTimeFactory->getNow();

		$state = await(
			$this->channelPropertiesStatesManager->read(
				$property,
				MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::FB_MQTT),
			),
		);

		if (is_bool($state)) {
			return $state;
		} elseif (!$state instanceof DevicesDocuments\States\Properties\Channel) {
			// Property state is not set
			return false;
		}

		if ($state->getGet()->getExpectedValue() === null) {
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
			$this->queue->append(
				$this->messageBuilder->create(
					Queue\Messages\WriteChannelPropertyState::class,
					[
						'connector' => $device->getConnector(),
						'device' => $device->getId(),
						'channel' => $property->getChannel(),
						'property' => $property->getId(),
						'state' => array_merge(
							$state->getGet()->toArray(),
							[
								'id' => $state->getId(),
								'valid' => $state->isValid(),
								'pending' => $state->getPending() instanceof DateTimeInterface
									? $state->getPending()->format(DateTimeInterface::ATOM)
									: $state->getPending(),
							],
						),
					],
				),
			);

			return true;
		}

		return false;
	}

	private function registerLoopHandler(): void
	{
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::HANDLER_PROCESSING_INTERVAL,
			async(function (): void {
				$this->handleCommunication();
			}),
		);
	}

}
