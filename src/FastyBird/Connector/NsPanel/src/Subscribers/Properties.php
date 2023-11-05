<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           12.07.23
 */

namespace FastyBird\Connector\NsPanel\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use IPub\DoctrineCrud;
use Nette;
use Nette\Utils;
use function assert;
use function floatval;
use function is_string;
use function sprintf;
use function strval;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Properties implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Helpers\Loader $loader,
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if (
			$entity instanceof Entities\Devices\Gateway
			|| $entity instanceof Entities\Devices\SubDevice
			|| $entity instanceof Entities\Devices\ThirdPartyDevice
		) {
			$this->processDeviceProperties($entity);

			if ($entity instanceof Entities\Devices\ThirdPartyDevice) {
				$this->processRequiredCapability($entity);
			}
		} elseif (
			$entity instanceof Entities\NsPanelChannel
			&& $entity->getDevice() instanceof Entities\Devices\SubDevice
		) {
			$this->processSubDeviceChannelProperties($entity);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 */
	private function processDeviceProperties(Entities\NsPanelDevice $device): void
	{
		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::STATE);

		$stateProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($stateProperty !== null && !$stateProperty instanceof DevicesEntities\Devices\Properties\Dynamic) {
			$this->devicesPropertiesManager->delete($stateProperty);

			$stateProperty = null;
		}

		$enumValues = $device instanceof Entities\Devices\ThirdPartyDevice ? [
			MetadataTypes\ConnectionState::STATE_CONNECTED,
			MetadataTypes\ConnectionState::STATE_DISCONNECTED,
			MetadataTypes\ConnectionState::STATE_ALERT,
			MetadataTypes\ConnectionState::STATE_UNKNOWN,
		] : [
			MetadataTypes\ConnectionState::STATE_CONNECTED,
			MetadataTypes\ConnectionState::STATE_DISCONNECTED,
			MetadataTypes\ConnectionState::STATE_LOST,
			MetadataTypes\ConnectionState::STATE_ALERT,
			MetadataTypes\ConnectionState::STATE_UNKNOWN,
		];

		if ($stateProperty !== null) {
			$this->devicesPropertiesManager->update($stateProperty, Utils\ArrayHash::from([
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'unit' => null,
				'format' => $enumValues,
				'settable' => false,
				'queryable' => false,
			]));
		} else {
			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'device' => $device,
				'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
				'identifier' => Types\DevicePropertyIdentifier::STATE,
				'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::STATE),
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'unit' => null,
				'format' => $enumValues,
				'settable' => false,
				'queryable' => false,
			]));
		}
	}

	/**
	 * INFO: NS Panel has bug, RSSI capability is required
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function processRequiredCapability(Entities\Devices\ThirdPartyDevice $device): void
	{
		$findChannelQuery = new Queries\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(
			Helpers\Name::convertCapabilityToChannel(Types\Capability::get(Types\Capability::RSSI)),
		);

		$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\NsPanelChannel::class);

		if ($channel === null) {
			$channel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\NsPanelChannel::class,
				'identifier' => Helpers\Name::convertCapabilityToChannel(Types\Capability::get(Types\Capability::RSSI)),
				'name' => 'RSSI',
				'device' => $device,
			]));
			assert($channel instanceof Entities\NsPanelChannel);

			$this->processSubDeviceChannelProperties($channel);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 */
	private function processSubDeviceChannelProperties(Entities\NsPanelChannel $channel): void
	{
		$metadata = $this->loader->loadCapabilities();

		if (!$metadata->offsetExists($channel->getCapability()->getValue())) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for capability: %s was not found',
				$channel->getCapability()->getValue(),
			));
		}

		$capabilityMetadata = $metadata->offsetGet($channel->getCapability()->getValue());

		if (
			!$capabilityMetadata instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('permission')
			|| !is_string($capabilityMetadata->offsetGet('permission'))
			|| !Types\Permission::isValidValue($capabilityMetadata->offsetGet('permission'))
			|| !$capabilityMetadata->offsetExists('protocol')
			|| !$capabilityMetadata->offsetGet('protocol') instanceof Utils\ArrayHash
		) {
			throw new Exceptions\InvalidState('Capability definition is missing required attributes');
		}

		foreach ($capabilityMetadata->offsetGet('protocol') as $protocol) {
			assert(is_string($protocol));

			$metadata = $this->loader->loadProtocols();

			if (!$metadata->offsetExists($protocol)) {
				throw new Exceptions\InvalidArgument(sprintf(
					'Definition for protocol: %s was not found',
					$protocol,
				));
			}

			$protocolMetadata = $metadata->offsetGet($protocol);

			if (
				!$protocolMetadata instanceof Utils\ArrayHash
				|| !$protocolMetadata->offsetExists('data_type')
				|| !is_string($protocolMetadata->offsetGet('data_type'))
			) {
				throw new Exceptions\InvalidState('Protocol definition is missing required attributes');
			}

			$dataType = MetadataTypes\DataType::get($protocolMetadata->offsetGet('data_type'));

			$permission = Types\Permission::get($capabilityMetadata->offsetGet('permission'));

			$format = null;

			if (
				$protocolMetadata->offsetExists('min_value')
				|| $protocolMetadata->offsetExists('max_value')
			) {
				$format = [
					$protocolMetadata->offsetExists('min_value') ? floatval(
						$protocolMetadata->offsetGet('min_value'),
					) : null,
					$protocolMetadata->offsetExists('max_value') ? floatval(
						$protocolMetadata->offsetGet('max_value'),
					) : null,
				];
			}

			if (
				(
					$dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_ENUM)
					|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_SWITCH)
					|| $dataType->equalsValue(MetadataTypes\DataType::DATA_TYPE_BUTTON)
				)
			) {
				if (
					$protocolMetadata->offsetExists('mapped_values')
					&& $protocolMetadata->offsetGet('mapped_values') instanceof Utils\ArrayHash
				) {
					$format = (array) $protocolMetadata->offsetGet('mapped_values');
				} elseif (
					$protocolMetadata->offsetExists('valid_values')
					&& $protocolMetadata->offsetGet('valid_values') instanceof Utils\ArrayHash
				) {
					$format = (array) $protocolMetadata->offsetGet('valid_values');
				}
			}

			$this->processChannelProperty(
				$channel,
				Types\Protocol::get($protocol),
				$dataType,
				$format,
				$permission->equalsValue(Types\Permission::READ_WRITE) || $permission->equalsValue(
					Types\Permission::WRITE,
				),
				$permission->equalsValue(Types\Permission::READ_WRITE) || $permission->equalsValue(
					Types\Permission::READ,
				),
				$protocolMetadata->offsetExists('unit') ? strval($protocolMetadata->offsetGet('unit')) : null,
				$protocolMetadata->offsetExists('invalid_value')
					? strval($protocolMetadata->offsetGet('invalid_value'))
					: null,
			);
		}
	}

	/**
	 * @param string|array<int, string>|array<int, string|int|float|array<int, string|int|float>|Utils\ArrayHash|null>|array<int, array<int, string|array<int, string|int|float|bool>|Utils\ArrayHash|null>>|null $format
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 */
	private function processChannelProperty(
		Entities\NsPanelChannel $channel,
		Types\Protocol $protocol,
		MetadataTypes\DataType $dataType,
		array|string|null $format = null,
		bool $settable = false,
		bool $queryable = false,
		string|null $unit = null,
		string|null $invalidValue = null,
	): void
	{
		$findChannelPropertyQuery = new DevicesQueries\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byIdentifier(Helpers\Name::convertProtocolToProperty($protocol));

		$property = $this->channelsPropertiesRepository->findOneBy($findChannelPropertyQuery);

		if ($property !== null && !$property instanceof DevicesEntities\Channels\Properties\Dynamic) {
			$this->channelsPropertiesManager->delete($property);

			$property = null;
		}

		if ($property !== null) {
			$this->channelsPropertiesManager->update($property, Utils\ArrayHash::from([
				'dataType' => $dataType,
				'unit' => $unit,
				'format' => $format,
				'settable' => $settable,
				'queryable' => $queryable,
				'invalid' => $invalidValue,
			]));
		} else {
			if ($protocol->equalsValue(Types\Protocol::RSSI)) {
				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Helpers\Name::convertProtocolToProperty($protocol),
					'name' => Helpers\Name::createName(Helpers\Name::convertProtocolToProperty($protocol)),
					'channel' => $channel,
					'dataType' => $dataType,
					'unit' => $unit,
					'format' => $format,
					'value' => -40,
				]));
			} else {
				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'channel' => $channel,
					'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
					'identifier' => Helpers\Name::convertProtocolToProperty($protocol),
					'name' => Helpers\Name::createName(Helpers\Name::convertProtocolToProperty($protocol)),
					'dataType' => $dataType,
					'unit' => $unit,
					'format' => $format,
					'settable' => $settable,
					'queryable' => $queryable,
					'invalid' => $invalidValue,
				]));
			}
		}
	}

}
