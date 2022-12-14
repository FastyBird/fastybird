<?php declare(strict_types = 1);

/**
 * ChannelPropertiesRepository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          0.9.0
 *
 * @date           09.01.22
 */

namespace FastyBird\Module\Devices\Models\States;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\States;
use Nette;
use Ramsey\Uuid;

/**
 * Channel property repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelPropertiesRepository
{

	use Nette\SmartObject;

	public function __construct(private readonly IChannelPropertiesRepository|null $repository)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\NotImplemented
	 */
	public function findOne(
		MetadataEntities\DevicesModule\ChannelDynamicProperty|MetadataEntities\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
	): States\ChannelProperty|null
	{
		if ($this->repository === null) {
			throw new Exceptions\NotImplemented('Channel properties state repository is not registered');
		}

		if ($property->getParent() !== null) {
			$parent = $property->getParent();

			if (
				$parent instanceof Entities\Channels\Properties\Dynamic
				|| $parent instanceof Entities\Channels\Properties\Mapped
			) {
				return $this->repository->findOne($parent);
			} elseif ($parent instanceof Uuid\UuidInterface) {
				return $this->repository->findOneById($parent);
			} else {
				return null;
			}
		}

		return $this->repository->findOne($property);
	}

	/**
	 * @throws Exceptions\NotImplemented
	 */
	public function findOneById(Uuid\UuidInterface $id): States\ChannelProperty|null
	{
		if ($this->repository === null) {
			throw new Exceptions\NotImplemented('Channel properties state repository is not registered');
		}

		return $this->repository->findOneById($id);
	}

}
