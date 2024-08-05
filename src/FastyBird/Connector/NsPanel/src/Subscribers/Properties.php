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
use Doctrine\DBAL;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Helpers;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Types as DevicesTypes;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use IPub\DoctrineCrud\Exceptions as DoctrineCrudExceptions;
use Nette;
use Nette\Utils;
use TypeError;
use ValueError;
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
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
		private readonly DevicesModels\Entities\Channels\ChannelsRepository $channelsRepository,
		private readonly DevicesModels\Entities\Channels\ChannelsManager $channelsManager,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesManager $channelsPropertiesManager,
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
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
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
			$entity instanceof Entities\Channels\Channel
			&& $entity->getDevice() instanceof Entities\Devices\SubDevice
		) {
			$this->processSubDeviceChannelProperties($entity);
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 */
	private function processDeviceProperties(Entities\Devices\Device $device): void
	{
		$findDevicePropertyQuery = new Queries\Entities\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::STATE);

		$stateProperty = $this->devicesPropertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($stateProperty !== null && !$stateProperty instanceof DevicesEntities\Devices\Properties\Dynamic) {
			$this->devicesPropertiesManager->delete($stateProperty);

			$stateProperty = null;
		}

		if ($stateProperty !== null) {
			$this->devicesPropertiesManager->update($stateProperty, Utils\ArrayHash::from([
				'dataType' => MetadataTypes\DataType::ENUM,
				'unit' => null,
				'format' => [
					DevicesTypes\ConnectionState::CONNECTED->value,
					DevicesTypes\ConnectionState::DISCONNECTED->value,
					DevicesTypes\ConnectionState::ALERT->value,
					DevicesTypes\ConnectionState::UNKNOWN->value,
				],
				'settable' => false,
				'queryable' => false,
			]));
		} else {
			$this->devicesPropertiesManager->create(Utils\ArrayHash::from([
				'device' => $device,
				'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
				'identifier' => Types\DevicePropertyIdentifier::STATE->value,
				'name' => DevicesUtilities\Name::createName(Types\DevicePropertyIdentifier::STATE->value),
				'dataType' => MetadataTypes\DataType::ENUM,
				'unit' => null,
				'format' => [
					DevicesTypes\ConnectionState::CONNECTED->value,
					DevicesTypes\ConnectionState::DISCONNECTED->value,
					DevicesTypes\ConnectionState::ALERT->value,
					DevicesTypes\ConnectionState::UNKNOWN->value,
				],
				'settable' => false,
				'queryable' => false,
			]));
		}
	}

	/**
	 * INFO: NS Panel has bug, RSSI capability is required
	 *
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function processRequiredCapability(Entities\Devices\ThirdPartyDevice $device): void
	{
		$findChannelQuery = new Queries\Entities\FindChannels();
		$findChannelQuery->forDevice($device);
		$findChannelQuery->byIdentifier(
			Helpers\Name::convertCapabilityToChannel(Types\Capability::RSSI),
		);

		$channel = $this->channelsRepository->findOneBy($findChannelQuery, Entities\Channels\Channel::class);

		if ($channel === null) {
			$channel = $this->channelsManager->create(Utils\ArrayHash::from([
				'entity' => Entities\Channels\Channel::class,
				'identifier' => Helpers\Name::convertCapabilityToChannel(Types\Capability::RSSI),
				'name' => 'RSSI',
				'device' => $device,
			]));
			assert($channel instanceof Entities\Channels\Channel);

			$this->processSubDeviceChannelProperties($channel);
		}
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Nette\IOException
	 * @throws TypeError
	 * @throws ValueError
	 */
	private function processSubDeviceChannelProperties(Entities\Channels\Channel $channel): void
	{
		$metadata = $this->loader->loadCapabilities();

		if (!$metadata->offsetExists($channel->getCapability()->value)) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for capability: %s was not found',
				$channel->getCapability()->value,
			));
		}

		$capabilityMetadata = $metadata->offsetGet($channel->getCapability()->value);

		if (
			!$capabilityMetadata instanceof Utils\ArrayHash
			|| !$capabilityMetadata->offsetExists('permission')
			|| !is_string($capabilityMetadata->offsetGet('permission'))
			|| Types\Permission::tryFrom($capabilityMetadata->offsetGet('permission')) === null
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
				|| MetadataTypes\DataType::tryFrom($protocolMetadata->offsetGet('data_type')) === null
			) {
				throw new Exceptions\InvalidState('Protocol definition is missing required attributes');
			}

			$dataType = MetadataTypes\DataType::from($protocolMetadata->offsetGet('data_type'));

			$permission = Types\Permission::from($capabilityMetadata->offsetGet('permission'));

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
					$dataType === MetadataTypes\DataType::ENUM
					|| $dataType === MetadataTypes\DataType::SWITCH
					|| $dataType === MetadataTypes\DataType::BUTTON
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
				Types\Protocol::from($protocol),
				$dataType,
				$format,
				$permission === Types\Permission::READ_WRITE || $permission === Types\Permission::WRITE,
				$permission === Types\Permission::READ_WRITE || $permission === Types\Permission::READ,
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
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 */
	private function processChannelProperty(
		Entities\Channels\Channel $channel,
		Types\Protocol $protocol,
		MetadataTypes\DataType $dataType,
		array|string|null $format = null,
		bool $settable = false,
		bool $queryable = false,
		string|null $unit = null,
		string|null $invalidValue = null,
	): void
	{
		$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
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
			if ($protocol === Types\Protocol::RSSI) {
				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => Helpers\Name::convertProtocolToProperty($protocol),
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
