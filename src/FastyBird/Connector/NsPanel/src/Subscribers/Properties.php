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
use FastyBird\Connector\NsPanel\Mapping;
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
use function is_array;
use function sprintf;

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
		private readonly Mapping\Builder $mappingBuilder,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesRepository $devicesPropertiesRepository,
		private readonly DevicesModels\Entities\Devices\Properties\PropertiesManager $devicesPropertiesManager,
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
	 * @throws Exceptions\Runtime
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
	 * @throws ApplicationExceptions\InvalidState
	 * @throws DBAL\Exception\UniqueConstraintViolationException
	 * @throws DoctrineCrudExceptions\EntityCreation
	 * @throws DoctrineCrudExceptions\InvalidArgument
	 * @throws DoctrineCrudExceptions\InvalidState
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 */
	private function processSubDeviceChannelProperties(Entities\Channels\Channel $channel): void
	{
		$capabilityMetadata = $this->mappingBuilder->getCapabilitiesMapping()->findByCapabilityName(
			$channel->getCapability(),
		);

		if ($capabilityMetadata === null) {
			throw new Exceptions\InvalidArgument(sprintf(
				'Definition for capability: %s was not found',
				$channel->getCapability()->value,
			));
		}

		foreach ($capabilityMetadata->getAttributes() as $attributeMetadata) {
			$dataType = $attributeMetadata->getDataType();

			$permission = $capabilityMetadata->getPermission();

			$format = null;

			if (
				$attributeMetadata->getMinValue() !== null
				|| $attributeMetadata->getMaxValue() !== null
			) {
				$format = [
					$attributeMetadata->getMinValue(),
					$attributeMetadata->getMaxValue(),
				];
			}

			if (
				(
					$dataType === MetadataTypes\DataType::ENUM
					|| $dataType === MetadataTypes\DataType::SWITCH
					|| $dataType === MetadataTypes\DataType::BUTTON
				)
			) {
				if ($attributeMetadata->getMappedValues() !== []) {
					$format = $attributeMetadata->getMappedValues();
				} elseif ($attributeMetadata->getValidValues() !== []) {
					$format = $attributeMetadata->getValidValues();
				}
			}

			$this->processChannelProperty(
				$channel,
				$attributeMetadata->getAttribute(),
				is_array($dataType) ? $dataType[0] : $dataType,
				$format,
				$permission === Types\Permission::READ_WRITE || $permission === Types\Permission::WRITE,
				$permission === Types\Permission::READ_WRITE || $permission === Types\Permission::READ,
				$attributeMetadata->getUnit(),
				$attributeMetadata->getInvalidValue(),
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
		Types\Attribute $attribute,
		MetadataTypes\DataType $dataType,
		array|string|null $format = null,
		bool $settable = false,
		bool $queryable = false,
		string|null $unit = null,
		float|int|string|null $invalidValue = null,
	): void
	{
		$findChannelPropertyQuery = new DevicesQueries\Entities\FindChannelProperties();
		$findChannelPropertyQuery->forChannel($channel);
		$findChannelPropertyQuery->byIdentifier($attribute->value);

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
			if ($attribute === Types\Attribute::RSSI) {
				$this->channelsPropertiesManager->create(Utils\ArrayHash::from([
					'entity' => DevicesEntities\Channels\Properties\Variable::class,
					'identifier' => $attribute->value,
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
					'identifier' => $attribute->value,
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
