<?php declare(strict_types = 1);

/**
 * ConnectorConnection.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 * @since          0.73.0
 *
 * @date           19.07.22
 */

namespace FastyBird\Module\Devices\Utilities;

use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use IPub\DoctrineOrmQuery\Exceptions as DoctrineOrmQueryExceptions;
use Nette;
use Nette\Utils;
use function assert;

/**
 * Connector connection states manager
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ConnectorConnection
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Models\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly Models\DataStorage\ConnectorPropertiesRepository $repository,
		private readonly Models\Connectors\Properties\PropertiesManager $manager,
		private readonly ConnectorPropertiesStates $propertiesStates,
	)
	{
	}

	/**
	 * @throws DoctrineOrmQueryExceptions\InvalidStateException
	 * @throws DoctrineOrmQueryExceptions\QueryException
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function setState(
		Entities\Connectors\Connector|MetadataEntities\DevicesModule\Connector $connector,
		MetadataTypes\ConnectionState $state,
	): bool
	{
		$property = $this->repository->findByIdentifier(
			$connector->getId(),
			MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_STATE,
		);

		if ($property === null) {
			if (!$connector instanceof Entities\Connectors\Connector) {
				$findConnectorQuery = new Queries\FindConnectors();
				$findConnectorQuery->byId($connector->getId());

				$connector = $this->connectorsRepository->findOneBy($findConnectorQuery);

				if ($connector === null) {
					throw new Exceptions\InvalidState('Connector could not be loaded');
				}
			}

			$property = $this->manager->create(Utils\ArrayHash::from([
				'connector' => $connector,
				'entity' => Entities\Connectors\Properties\Dynamic::class,
				'identifier' => MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_STATE,
				'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::DATA_TYPE_ENUM),
				'unit' => null,
				'format' => [
					MetadataTypes\ConnectionState::STATE_RUNNING,
					MetadataTypes\ConnectionState::STATE_STOPPED,
					MetadataTypes\ConnectionState::STATE_UNKNOWN,
					MetadataTypes\ConnectionState::STATE_SLEEPING,
					MetadataTypes\ConnectionState::STATE_ALERT,
				],
				'settable' => false,
				'queryable' => false,
			]));
		}

		assert(
			$property instanceof MetadataEntities\DevicesModule\ConnectorDynamicProperty
			|| $property instanceof Entities\Connectors\Properties\Dynamic,
		);

		$this->propertiesStates->setValue(
			$property,
			Utils\ArrayHash::from([
				'actualValue' => $state->getValue(),
				'expectedValue' => null,
				'pending' => false,
				'valid' => true,
			]),
		);

		return false;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getState(
		Entities\Connectors\Connector|MetadataEntities\DevicesModule\Connector $connector,
	): MetadataTypes\ConnectionState
	{
		$stateProperty = $this->repository->findByIdentifier(
			$connector->getId(),
			MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_STATE,
		);

		if (
			$stateProperty instanceof MetadataEntities\DevicesModule\ConnectorDynamicProperty
			&& $stateProperty->getActualValue() !== null
			&& MetadataTypes\ConnectionState::isValidValue($stateProperty->getActualValue())
		) {
			return MetadataTypes\ConnectionState::get($stateProperty->getActualValue());
		}

		return MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_UNKNOWN);
	}

}
