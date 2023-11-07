<?php declare(strict_types = 1);

/**
 * DevicePropertiesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           20.10.22
 */

namespace FastyBird\Bridge\RedisDbDevicesModule\Models\States;

use FastyBird\Bridge\RedisDbDevicesModule\States;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Ramsey\Uuid;

/**
 * Device property state repository
 *
 * @package        FastyBird:RedisDbDevicesModuleBridge!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DevicePropertiesRepository implements DevicesModels\States\IDevicePropertiesRepository
{

	use Nette\SmartObject;

	/** @var RedisDbModels\States\StatesRepository<States\DeviceProperty> */
	private RedisDbModels\States\StatesRepository $stateRepository;

	/**
	 * @phpstan-param RedisDbModels\States\StatesRepositoryFactory<States\DeviceProperty> $stateRepositoryFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\States\StatesRepositoryFactory $stateRepositoryFactory,
		private readonly int $database = 0,
	)
	{
		$this->stateRepository = $stateRepositoryFactory->create(States\DeviceProperty::class);
	}

	/**
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function findOne(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataEntities\DevicesModule\DeviceDynamicProperty|MetadataEntities\DevicesModule\DeviceMappedProperty|DevicesEntities\Devices\Properties\Dynamic|DevicesEntities\Devices\Properties\Mapped $property,
	): States\DeviceProperty|null
	{
		return $this->stateRepository->findOne($property->getId(), $this->database);
	}

	/**
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function findOneById(Uuid\UuidInterface $id): States\DeviceProperty|null
	{
		return $this->stateRepository->findOne($id, $this->database);
	}

}
