<?php declare(strict_types = 1);

/**
 * ChannelPropertiesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:RedisDbDevicesStatesBridge!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           20.10.22
 */

namespace FastyBird\Bridge\RedisDbDevicesStates\Models;

use FastyBird\Bridge\RedisDbDevicesStates\States;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Plugin\RedisDb\Exceptions as RedisDbExceptions;
use FastyBird\Plugin\RedisDb\Models as RedisDbModels;
use Nette;
use Ramsey\Uuid;

/**
 * Channel property state repository
 *
 * @package        FastyBird:RedisDbDevicesStatesBridge!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ChannelPropertiesRepository implements DevicesModels\States\IChannelPropertiesRepository
{

	use Nette\SmartObject;

	/** @var RedisDbModels\StatesRepository<States\ChannelProperty> */
	private RedisDbModels\StatesRepository $stateRepository;

	/**
	 * @phpstan-param RedisDbModels\StatesRepositoryFactory<States\ChannelProperty> $stateRepositoryFactory
	 *
	 * @throws RedisDbExceptions\InvalidArgument
	 */
	public function __construct(
		RedisDbModels\StatesRepositoryFactory $stateRepositoryFactory,
		private readonly int $database = 0,
	)
	{
		$this->stateRepository = $stateRepositoryFactory->create(States\ChannelProperty::class);
	}

	/**
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function findOne(
		MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty|DevicesEntities\Channels\Properties\Dynamic|DevicesEntities\Channels\Properties\Mapped $property,
	): States\ChannelProperty|null
	{
		return $this->stateRepository->findOne($property->getId(), $this->database);
	}

	/**
	 * @throws RedisDbExceptions\InvalidArgument
	 * @throws RedisDbExceptions\InvalidState
	 */
	public function findOneById(Uuid\UuidInterface $id): States\ChannelProperty|null
	{
		return $this->stateRepository->findOne($id, $this->database);
	}

}
