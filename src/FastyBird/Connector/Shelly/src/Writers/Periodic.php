<?php declare(strict_types = 1);

/**
 * Periodic.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Writers
 * @since          1.0.0
 *
 * @date           14.12.22
 */

namespace FastyBird\Connector\Shelly\Writers;

use DateTimeInterface;
use FastyBird\Connector\Shelly\Clients;
use FastyBird\Connector\Shelly\Entities;
use FastyBird\Connector\Shelly\Helpers;
use FastyBird\DateTimeFactory;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\States as DevicesStates;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use Psr\Log;
use React\EventLoop;
use Throwable;
use function array_key_exists;
use function assert;
use function in_array;

/**
 * Periodic properties writer
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Periodic implements Writer
{

	use Nette\SmartObject;

	public const NAME = 'periodic';

	private const HANDLER_START_DELAY = 5.0;

	private const HANDLER_DEBOUNCE_INTERVAL = 500;

	private const HANDLER_PROCESSING_INTERVAL = 0.01;

	private const HANDLER_PENDING_DELAY = 2_000;

	/** @var array<string> */
	private array $processedDevices = [];

	/** @var array<string, DateTimeInterface> */
	private array $processedProperties = [];

	private Entities\ShellyConnector|null $connector = null;

	private Clients\Client|null $client = null;

	private EventLoop\TimerInterface|null $handlerTimer = null;

	private Log\LoggerInterface $logger;

	public function __construct(
		private readonly Helpers\Property $propertyStateHelper,
		private readonly DevicesUtilities\DeviceConnection $deviceConnectionManager,
		private readonly DevicesUtilities\ChannelPropertiesStates $channelPropertiesStates,
		private readonly DateTimeFactory\Factory $dateTimeFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		Log\LoggerInterface|null $logger = null,
	)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public function connect(
		Entities\ShellyConnector $connector,
		Clients\Client $client,
	): void
	{
		$this->connector = $connector;
		$this->client = $client;

		$this->processedDevices = [];
		$this->processedProperties = [];

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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function handleCommunication(): void
	{
		assert($this->connector instanceof Entities\ShellyConnector);

		foreach ($this->processedProperties as $index => $processedProperty) {
			if (
				(float) $this->dateTimeFactory->getNow()->format('Uv') - (float) $processedProperty->format(
					'Uv',
				) >= 500
			) {
				unset($this->processedProperties[$index]);
			}
		}

		foreach ($this->connector->getDevices() as $device) {
			assert($device instanceof Entities\ShellyDevice);

			if (
				!in_array($device->getPlainId(), $this->processedDevices, true)
				&& $this->deviceConnectionManager->getState($device)->equalsValue(
					MetadataTypes\ConnectionState::STATE_CONNECTED,
				)
			) {
				$this->processedDevices[] = $device->getPlainId();

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
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	private function writeChannelsProperty(Entities\ShellyDevice $device): bool
	{
		assert($this->connector instanceof Entities\ShellyConnector);

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

						$this->client?->writeChannelProperty($device, $channel, $property)
							->then(function () use ($property): void {
								$this->propertyStateHelper->setValue(
									$property,
									Utils\ArrayHash::from([
										DevicesStates\Property::PENDING_KEY => $this->dateTimeFactory->getNow()->format(
											DateTimeInterface::ATOM,
										),
									]),
								);
							})
							->otherwise(function (Throwable $ex) use ($device, $channel, $property): void {
								$this->logger->error(
									'Could write new property state',
									[
										'source' => MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_SHELLY,
										'type' => 'periodic-writer',
										'group' => 'writer',
										'exception' => [
											'message' => $ex->getMessage(),
											'code' => $ex->getCode(),
										],
										'connector' => [
											'id' => $this->connector?->getPlainId(),
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