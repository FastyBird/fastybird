<?php declare(strict_types = 1);

/**
 * ConnectorConnection.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Utilities
 * @since          1.0.0
 *
 * @date           19.07.22
 */

namespace FastyBird\Module\Devices\Utilities;

use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
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
		private readonly Models\Entities\Connectors\Properties\PropertiesRepository $repository,
		private readonly Models\Entities\Connectors\Properties\PropertiesManager $manager,
		private readonly ConnectorPropertiesStates $propertiesStates,
	)
	{
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function setState(
		Entities\Connectors\Connector $connector,
		MetadataTypes\ConnectionState $state,
	): bool
	{
		$findConnectorPropertyQuery = new Queries\FindConnectorProperties();
		$findConnectorPropertyQuery->forConnector($connector);
		$findConnectorPropertyQuery->byIdentifier(MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_STATE);

		$property = $this->repository->findOneBy($findConnectorPropertyQuery);

		if ($property === null) {
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

		assert($property instanceof Entities\Connectors\Properties\Dynamic);

		$this->propertiesStates->writeValue(
			$property,
			Utils\ArrayHash::from([
				States\Property::ACTUAL_VALUE_KEY => $state->getValue(),
				States\Property::EXPECTED_VALUE_KEY => null,
				States\Property::PENDING_KEY => false,
				States\Property::VALID_KEY => true,
			]),
		);

		return false;
	}

	/**
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getState(
		Entities\Connectors\Connector $connector,
	): MetadataTypes\ConnectionState
	{
		$findPropertyQuery = new Queries\FindConnectorProperties();
		$findPropertyQuery->forConnector($connector);
		$findPropertyQuery->byIdentifier(MetadataTypes\ConnectorPropertyIdentifier::IDENTIFIER_STATE);

		$property = $this->repository->findOneBy($findPropertyQuery);

		if ($property instanceof Entities\Connectors\Properties\Dynamic) {
			$state = $this->propertiesStates->readValue($property);

			if (
				$state?->getActualValue() !== null
				&& MetadataTypes\ConnectionState::isValidValue($state->getActualValue())
			) {
				return MetadataTypes\ConnectionState::get($state->getActualValue());
			}
		}

		return MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::STATE_UNKNOWN);
	}

}
