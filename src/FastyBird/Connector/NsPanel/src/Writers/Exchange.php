<?php declare(strict_types = 1);

/**
 * Exchange.php
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

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Library\Exchange\Consumers as ExchangeConsumers;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use Nette;
use function assert;

/**
 * Exchange based properties writer
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Writers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Exchange implements Writer, ExchangeConsumers\Consumer
{

	use Nette\SmartObject;

	public const NAME = 'exchange';

	public function __construct(
		private readonly Entities\NsPanelConnector $connector,
		private readonly Helpers\Entity $entityHelper,
		private readonly Queue\Queue $queue,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly ExchangeConsumers\Container $consumer,
	)
	{
	}

	public function connect(): void
	{
		$this->consumer->enable(self::class);
	}

	public function disconnect(): void
	{
		$this->consumer->disable(self::class);
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function consume(
		MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AutomatorSource $source,
		MetadataTypes\RoutingKey $routingKey,
		MetadataEntities\Entity|null $entity,
	): void
	{
		if (
			$entity instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty
			|| $entity instanceof MetadataEntities\DevicesModule\ChannelMappedProperty
			|| $entity instanceof MetadataEntities\DevicesModule\ChannelVariableProperty
		) {
			$findChannelQuery = new DevicesQueries\FindChannels();
			$findChannelQuery->byId($entity->getChannel());

			$channel = $this->channelsRepository->findOneBy($findChannelQuery);

			if ($channel === null) {
				return;
			}

			$device = $channel->getDevice();

			if (!$device->getConnector()->getId()->equals($this->connector->getId())) {
				return;
			}

			if ($device instanceof Entities\Devices\SubDevice) {
				assert($channel instanceof Entities\NsPanelChannel);

				if (
					(
						$entity instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty
						|| $entity instanceof MetadataEntities\DevicesModule\ChannelMappedProperty
					)
					&& (
						$entity->getExpectedValue() === null
						|| $entity->getPending() !== true
					)
				) {
					return;
				}

				$this->writeSubDeviceChannelProperty($device, $channel);

			} elseif ($device instanceof Entities\Devices\ThirdPartyDevice) {
				assert($channel instanceof Entities\NsPanelChannel);

				if (
					(
						$entity instanceof MetadataEntities\DevicesModule\ChannelDynamicProperty
						|| $entity instanceof MetadataEntities\DevicesModule\ChannelMappedProperty
					)
					&& $entity->isValid() !== true
				) {
					return;
				}

				$this->writeThirdPartyDeviceChannelProperty($device, $channel);
			}
		}
	}

	/**
	 * @throws Exceptions\Runtime
	 */
	public function writeSubDeviceChannelProperty(
		Entities\Devices\SubDevice $device,
		Entities\NsPanelChannel $channel,
	): void
	{
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

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function writeThirdPartyDeviceChannelProperty(
		Entities\Devices\ThirdPartyDevice $device,
		Entities\NsPanelChannel $channel,
	): void
	{
		if ($device->getGatewayIdentifier() === null) {
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

			return;
		}

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
	}

}
