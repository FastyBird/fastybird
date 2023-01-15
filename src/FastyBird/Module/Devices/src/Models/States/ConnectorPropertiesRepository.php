<?php declare(strict_types = 1);

/**
 * ConnectorPropertiesRepository.php
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
 * Connector property repository
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Models
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorPropertiesRepository
{

	use Nette\SmartObject;

	public function __construct(protected readonly IConnectorPropertiesRepository|null $repository = null)
	{
	}

	/**
	 * @throws Exceptions\NotImplemented
	 */
	public function findOne(
		MetadataEntities\DevicesModule\ConnectorDynamicProperty|Entities\Connectors\Properties\Dynamic $property,
	): States\ConnectorProperty|null
	{
		if ($this->repository === null) {
			throw new Exceptions\NotImplemented('Connector properties state repository is not registered');
		}

		return $this->repository->findOne($property);
	}

	/**
	 * @throws Exceptions\NotImplemented
	 */
	public function findOneById(
		Uuid\UuidInterface $id,
	): States\ConnectorProperty|null
	{
		if ($this->repository === null) {
			throw new Exceptions\NotImplemented('Connector properties state repository is not registered');
		}

		return $this->repository->findOneById($id);
	}

}
