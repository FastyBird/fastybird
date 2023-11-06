<?php declare(strict_types = 1);

/**
 * ClientProcess.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           06.05.23
 */

namespace FastyBird\Connector\Sonoff\Clients;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Sonoff\Entities;
use FastyBird\Connector\Sonoff\Queries;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use React\EventLoop;
use React\Promise;
use function array_key_exists;
use function in_array;

/**
 * Client process methods
 *
 * @package        FastyBird:SonoffConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class ClientProcess
{

	use Nette\SmartObject;

	protected const HANDLER_START_DELAY = 2.0;

	protected const HANDLER_PROCESSING_INTERVAL = 0.01;

	protected const HEARTBEAT_DELAY = 600;

	protected const CMD_STATE = 'state';

	protected const CMD_HEARTBEAT = 'hearbeat';

	/** @var array<string> */
	protected array $processedDevices = [];

	/** @var array<string> */
	protected array $ignoredDevices = [];

	/** @var array<string, array<string, DateTimeInterface|bool>> */
	protected array $processedDevicesCommands = [];

	protected EventLoop\TimerInterface|null $handlerTimer = null;

	public function __construct(
		protected readonly Entities\SonoffConnector $connector,
		protected readonly DevicesModels\Entities\Devices\DevicesRepository $devicesRepository,
		protected readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		protected readonly DevicesUtilities\DevicePropertiesStates $devicePropertiesStates,
		protected readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		protected readonly DateTimeFactory\Factory $dateTimeFactory,
		protected readonly EventLoop\LoopInterface $eventLoop,
	)
	{
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Exception
	 */
	protected function handleCommunication(): void
	{
		$findDevicesQuery = new Queries\FindDevices();
		$findDevicesQuery->forConnector($this->connector);

		foreach ($this->devicesRepository->findAllBy($findDevicesQuery, Entities\SonoffDevice::class) as $device) {
			if (
				!in_array($device->getId()->toString(), $this->processedDevices, true)
				&& !in_array($device->getId()->toString(), $this->ignoredDevices, true)
				&& !$this->deviceConnectionManager->getState($device)->equalsValue(
					MetadataTypes\ConnectionState::STATE_ALERT,
				)
			) {
				$this->processedDevices[] = $device->getId()->toString();

				if ($this->processDevice($device)) {
					$this->registerLoopHandler();

					return;
				}
			}
		}

		$this->processedDevices = [];

		$this->registerLoopHandler();
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Exception
	 */
	protected function processDevice(Entities\SonoffDevice $device): bool
	{
		if ($this->readDeviceInformation($device)) {
			return true;
		}

		return $this->readDeviceState($device);
	}

	protected function readDeviceInformation(Entities\SonoffDevice $device): bool
	{
		if (!array_key_exists($device->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_HEARTBEAT, $this->processedDevicesCommands[$device->getId()->toString()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getId()->toString()][self::CMD_HEARTBEAT];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < self::HEARTBEAT_DELAY
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();

		$this->readInformation($device)
			->then(function () use ($device): void {
				$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_HEARTBEAT] = $this->dateTimeFactory->getNow();
			});

		return true;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	protected function readDeviceState(Entities\SonoffDevice $device): bool
	{
		if (!array_key_exists($device->getId()->toString(), $this->processedDevicesCommands)) {
			$this->processedDevicesCommands[$device->getId()->toString()] = [];
		}

		if (array_key_exists(self::CMD_STATE, $this->processedDevicesCommands[$device->getId()->toString()])) {
			$cmdResult = $this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE];

			if (
				$cmdResult instanceof DateTimeInterface
				&& (
					$this->dateTimeFactory->getNow()->getTimestamp() - $cmdResult->getTimestamp() < $device->getStateReadingDelay()
				)
			) {
				return false;
			}
		}

		$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();

		$this->readState($device)
			->then(function () use ($device): void {
				$this->processedDevicesCommands[$device->getId()->toString()][self::CMD_STATE] = $this->dateTimeFactory->getNow();
			});

		return true;
	}

	protected function registerLoopHandler(): void
	{
		$this->handlerTimer = $this->eventLoop->addTimer(
			self::HANDLER_PROCESSING_INTERVAL,
			function (): void {
				$this->handleCommunication();
			},
		);
	}

	abstract protected function readInformation(Entities\SonoffDevice $device): Promise\PromiseInterface;

	abstract protected function readState(Entities\SonoffDevice $device): Promise\PromiseInterface;

}
