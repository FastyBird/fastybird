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
use FastyBird\Connector\FbMqtt\Clients;
use FastyBird\Connector\FbMqtt\Entities;
use FastyBird\Connector\FbMqtt\Helpers;
use FastyBird\DateTimeFactory;
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
use Psr\Log;
use Ramsey\Uuid;
use React\EventLoop;
use Throwable;
use function array_key_exists;
use function assert;
use function in_array;

/**
 * Periodic properties writer
 *
 * @package        FastyBird:FbMqttConnector!
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

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Helpers\Property $propertyStateHelper,
		private readonly DevicesModels\Devices\DevicesRepository $devicesRepository,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesUtilities\DevicePropertiesStates $devicesPropertiesStates,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function connect(
		Entities\FbMqttConnector $connector,
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
		Entities\FbMqttConnector $connector,
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function handleCommunication(): void
	{
		foreach ($this->clients as $id => $client) {
			$findDevicesQuery = new DevicesQueries\FindDevices();
			$findDevicesQuery->byConnectorId(Uuid\Uuid::fromString($id));

			foreach ($this->devicesRepository->findAllBy($findDevicesQuery) as $device) {
				assert($device instanceof Entities\FbMqttDevice);

				if (
					!in_array($device->getPlainId(), $this->processedDevices, true)
					&& $this->deviceConnectionManager->getState($device)->equalsValue(
						MetadataTypes\ConnectionState::STATE_CONNECTED,
					)
				) {
					$this->processedDevices[] = $device->getPlainId();

					if ($this->writeDevicesProperty($client, $device)) {
						$this->registerLoopHandler();

						return;
					}

					if ($this->writeChannelsProperty($client, $device)) {
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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function writeDevicesProperty(
		Clients\Client $client,
		Entities\FbMqttDevice $device,
	): bool
	{
		$now = $this->dateTimeFactory->getNow();

		foreach ($device->getProperties() as $property) {
			if (!$property instanceof DevicesEntities\Devices\Properties\Dynamic) {
				continue;
			}

			$state = $this->devicesPropertiesStates->getValue($property);

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

					$client->writeProperty($device, $property)
						->then(function () use ($property): void {
							$this->propertyStateHelper->setValue(
								$property,
								Utils\ArrayHash::from([
									DevicesStates\Property::PENDING_KEY => $this->dateTimeFactory->getNow()->format(
										DateTimeInterface::ATOM,
									),
								]),
							);

							unset($this->processedProperties[$property->getPlainId()]);
						})
						->otherwise(function (Throwable $ex) use ($device, $property): void {
							$this->logger->error(
								'Could write new device property state',
								[
									'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
									'type' => 'periodic-writer',
									'group' => 'writer',
									'exception' => [
										'message' => $ex->getMessage(),
										'code' => $ex->getCode(),
									],
									'connector' => [
										'id' => $device->getConnector()->getPlainId(),
									],
									'device' => [
										'id' => $device->getPlainId(),
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

		return false;
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function writeChannelsProperty(
		Clients\Client $client,
		Entities\FbMqttDevice $device,
	): bool
	{
		$now = $this->dateTimeFactory->getNow();

		foreach ($device->getChannels() as $channel) {
			foreach ($channel->getProperties() as $property) {
				if (!$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
					continue;
				}

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

						$client->writeProperty($device, $property)
							->then(function () use ($property): void {
								$this->propertyStateHelper->setValue(
									$property,
									Utils\ArrayHash::from([
										DevicesStates\Property::PENDING_KEY => $this->dateTimeFactory->getNow()->format(
											DateTimeInterface::ATOM,
										),
									]),
								);

								unset($this->processedProperties[$property->getPlainId()]);
							})
							->otherwise(function (Throwable $ex) use ($device, $channel, $property): void {
								$this->logger->error(
									'Could write new channel property state',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_FB_MQTT,
										'type' => 'periodic-writer',
										'group' => 'writer',
										'exception' => [
											'message' => $ex->getMessage(),
											'code' => $ex->getCode(),
										],
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