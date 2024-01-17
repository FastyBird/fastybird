<?php declare(strict_types = 1);

/**
 * Repository.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 * @since          1.0.0
 *
 * @date           09.01.22
 */

namespace FastyBird\Module\Devices\Models\States\Channels;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
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
final class Repository
{

	use Nette\SmartObject;

	public function __construct(private readonly IRepository|null $repository = null)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function findOne(
		// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
		MetadataDocuments\DevicesModule\ChannelDynamicProperty|MetadataDocuments\DevicesModule\ChannelMappedProperty|Entities\Channels\Properties\Dynamic|Entities\Channels\Properties\Mapped $property,
	): States\ChannelProperty|null
	{
		if ($this->repository === null) {
			throw new Exceptions\NotImplemented('Channel properties state repository is not registered');
		}

		if (
			$property instanceof MetadataDocuments\DevicesModule\ChannelMappedProperty
			|| $property instanceof Entities\Channels\Properties\Mapped
		) {
			$parent = $property->getParent();

			if ($parent instanceof Entities\Channels\Properties\Dynamic) {
				return $this->findOne($parent);
			} elseif ($parent instanceof Uuid\UuidInterface) {
				return $this->findOneById($parent);
			} else {
				return null;
			}
		}

		return $this->repository->findOne($property);
	}

	/**
	 * @throws Exceptions\NotImplemented
	 *
	 * @interal
	 */
	public function findOneById(Uuid\UuidInterface $id): States\ChannelProperty|null
	{
		if ($this->repository === null) {
			throw new Exceptions\NotImplemented('Channel properties state repository is not registered');
		}

		return $this->repository->findOneById($id);
	}

}
