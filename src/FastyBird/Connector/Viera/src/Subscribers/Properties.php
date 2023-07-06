<?php declare(strict_types = 1);

/**
 * Properties.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           21.06.23
 */

namespace FastyBird\Connector\Viera\Subscribers;

use Doctrine\Common;
use Doctrine\ORM;
use Doctrine\Persistence;
use FastyBird\Connector\Viera\Entities;
use FastyBird\Connector\Viera\Helpers;
use FastyBird\Connector\Viera\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\ValueObjects as MetadataValueObjects;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use IPub\DoctrineCrud;
use Nette;
use Nette\Utils;
use function array_merge;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Properties implements Common\EventSubscriber
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModels\Devices\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Devices\Properties\PropertiesManager $propertiesManager,
		private readonly DevicesModels\Channels\Properties\PropertiesRepository $channelsPropertiesRepository,
		private readonly DevicesModels\Channels\Properties\PropertiesManager $channelsPropertiesManager,
	)
	{
	}

	public function getSubscribedEvents(): array
	{
		return [
			ORM\Events::postPersist,
			ORM\Events::postUpdate,
		];
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 */
	public function postPersist(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		// Check for valid entity
		if ($entity instanceof Entities\VieraDevice) {
			$this->configureDeviceState($entity);
		}
	}

	/**
	 * @param Persistence\Event\LifecycleEventArgs<ORM\EntityManagerInterface> $eventArgs
	 *
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 */
	public function postUpdate(Persistence\Event\LifecycleEventArgs $eventArgs): void
	{
		// onFlush was executed before, everything already initialized
		$entity = $eventArgs->getObject();

		if (
			$entity instanceof DevicesEntities\Channels\Properties\Dynamic
			&& $entity->getChannel()->getDevice() instanceof Entities\VieraDevice
			&& (
				$entity->getIdentifier() === Types\ChannelPropertyIdentifier::IDENTIFIER_HDMI
				|| $entity->getIdentifier() === Types\ChannelPropertyIdentifier::IDENTIFIER_APPLICATION
			)
		) {
			$this->configureDeviceInputSource($entity);
		}
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 */
	private function configureDeviceState(Entities\VieraDevice $device): void
	{
		$findDevicePropertyQuery = new DevicesQueries\FindDeviceProperties();
		$findDevicePropertyQuery->forDevice($device);
		$findDevicePropertyQuery->byIdentifier(Types\DevicePropertyIdentifier::IDENTIFIER_STATE);

		$stateProperty = $this->propertiesRepository->findOneBy($findDevicePropertyQuery);

		if ($stateProperty !== null) {
			$this->propertiesManager->delete($stateProperty);
		}

		$this->propertiesManager->create(Utils\ArrayHash::from([
			'device' => $device,
			'entity' => DevicesEntities\Devices\Properties\Dynamic::class,
			'identifier' => Types\DevicePropertyIdentifier::IDENTIFIER_STATE,
			'name' => Helpers\Name::createName(Types\DevicePropertyIdentifier::IDENTIFIER_STATE),
			'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
			'unit' => null,
			'format' => [
				MetadataTypes\ConnectionState::STATE_CONNECTED,
				MetadataTypes\ConnectionState::STATE_DISCONNECTED,
				MetadataTypes\ConnectionState::STATE_LOST,
				MetadataTypes\ConnectionState::STATE_STOPPED,
				MetadataTypes\ConnectionState::STATE_UNKNOWN,
			],
			'settable' => false,
			'queryable' => false,
		]));
	}

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws DoctrineCrud\Exceptions\InvalidArgumentException
	 * @throws MetadataExceptions\InvalidArgument
	 */
	private function configureDeviceInputSource(DevicesEntities\Channels\Properties\Dynamic $property): void
	{
		$channel = $property->getChannel();

		$findChannelProperty = new DevicesQueries\FindChannelProperties();
		$findChannelProperty->forChannel($channel);
		$findChannelProperty->byIdentifier(Types\ChannelPropertyIdentifier::IDENTIFIER_HDMI);

		$hdmiProperty = $this->channelsPropertiesRepository->findOneBy(
			$findChannelProperty,
			DevicesEntities\Channels\Properties\Dynamic::class,
		);

		$hdmiFormat = $hdmiProperty?->getFormat();

		$hdmiFormat = $hdmiFormat instanceof MetadataValueObjects\CombinedEnumFormat ? $hdmiFormat->toArray() : [];

		$findChannelProperty = new DevicesQueries\FindChannelProperties();
		$findChannelProperty->forChannel($channel);
		$findChannelProperty->byIdentifier(Types\ChannelPropertyIdentifier::IDENTIFIER_APPLICATION);

		$applicationProperty = $this->channelsPropertiesRepository->findOneBy(
			$findChannelProperty,
			DevicesEntities\Channels\Properties\Dynamic::class,
		);

		$applicationFormat = $applicationProperty?->getFormat();

		$applicationFormat = $applicationFormat instanceof MetadataValueObjects\CombinedEnumFormat
			? $applicationFormat->toArray()
			: [];

		$findChannelProperty = new DevicesQueries\FindChannelProperties();
		$findChannelProperty->forChannel($channel);
		$findChannelProperty->byIdentifier(Types\ChannelPropertyIdentifier::IDENTIFIER_INPUT_SOURCE);

		$inputSourceProperty = $this->channelsPropertiesRepository->findOneBy(
			$findChannelProperty,
			DevicesEntities\Channels\Properties\Dynamic::class,
		);

		if ($inputSourceProperty === null) {
			$this->channelsPropertiesManager->create(
				Utils\ArrayHash::from(
					[
						'entity' => DevicesEntities\Channels\Properties\Dynamic::class,
						'channel' => $channel,
						'identifier' => Types\ChannelPropertyIdentifier::IDENTIFIER_INPUT_SOURCE,
						'name' => Helpers\Name::createName(Types\ChannelPropertyIdentifier::IDENTIFIER_INPUT_SOURCE),
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
						'settable' => true,
						'queryable' => false,
						'format' => array_merge(
							[
								[
									'TV',
									500,
									500,
								],
							],
							$hdmiFormat,
							$applicationFormat,
						),
					],
				),
			);
		} else {
			$this->channelsPropertiesManager->update(
				$inputSourceProperty,
				Utils\ArrayHash::from(
					[
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
						'settable' => true,
						'queryable' => false,
						'format' => array_merge(
							[
								[
									'TV',
									500,
									500,
								],
							],
							$hdmiFormat,
							$applicationFormat,
						),
					],
				),
			);
		}
	}

}
