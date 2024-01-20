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

use Doctrine\DBAL;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
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
		private readonly Models\Entities\Connectors\ConnectorsRepository $connectorsEntitiesRepository,
		private readonly Models\Entities\Connectors\Properties\PropertiesManager $connectorsPropertiesEntitiesManager,
		private readonly Models\Configuration\Connectors\Properties\Repository $connectorsPropertiesConfigurationRepository,
		private readonly Models\States\ConnectorPropertiesManager $propertiesStatesManager,
		private readonly Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws Exceptions\Runtime
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function setState(
		Entities\Connectors\Connector|MetadataDocuments\DevicesModule\Connector $connector,
		MetadataTypes\ConnectionState $state,
	): bool
	{
		$findConnectorPropertyQuery = new Queries\Configuration\FindConnectorDynamicProperties();
		$findConnectorPropertyQuery->byConnectorId($connector->getId());
		$findConnectorPropertyQuery->byIdentifier(MetadataTypes\ConnectorPropertyIdentifier::STATE);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findConnectorPropertyQuery,
			MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class,
		);

		if ($property === null) {
			$property = $this->databaseHelper->transaction(
				function () use ($connector): Entities\Connectors\Properties\Dynamic {
					if (!$connector instanceof Entities\Connectors\Connector) {
						$connector = $this->connectorsEntitiesRepository->find($connector->getId());
						assert($connector instanceof Entities\Connectors\Connector);
					}

					$property = $this->connectorsPropertiesEntitiesManager->create(Utils\ArrayHash::from([
						'connector' => $connector,
						'entity' => Entities\Connectors\Properties\Dynamic::class,
						'identifier' => MetadataTypes\ConnectorPropertyIdentifier::STATE,
						'dataType' => MetadataTypes\DataType::get(MetadataTypes\DataType::ENUM),
						'unit' => null,
						'format' => [
							MetadataTypes\ConnectionState::RUNNING,
							MetadataTypes\ConnectionState::STOPPED,
							MetadataTypes\ConnectionState::UNKNOWN,
							MetadataTypes\ConnectionState::SLEEPING,
							MetadataTypes\ConnectionState::ALERT,
						],
						'settable' => false,
						'queryable' => false,
					]));
					assert($property instanceof Entities\Connectors\Properties\Dynamic);

					return $property;
				},
			);
		}

		$this->propertiesStatesManager->set(
			$property,
			Utils\ArrayHash::from([
				States\Property::ACTUAL_VALUE_FIELD => $state->getValue(),
				States\Property::EXPECTED_VALUE_FIELD => null,
			]),
		);

		return false;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function getState(
		Entities\Connectors\Connector|MetadataDocuments\DevicesModule\Connector $connector,
	): MetadataTypes\ConnectionState
	{
		$findConnectorPropertyQuery = new Queries\Configuration\FindConnectorDynamicProperties();
		$findConnectorPropertyQuery->byConnectorId($connector->getId());
		$findConnectorPropertyQuery->byIdentifier(MetadataTypes\ConnectorPropertyIdentifier::STATE);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findConnectorPropertyQuery,
			MetadataDocuments\DevicesModule\ConnectorDynamicProperty::class,
		);

		if ($property instanceof MetadataDocuments\DevicesModule\ConnectorDynamicProperty) {
			$state = $this->propertiesStatesManager->read($property);

			if (
				$state?->getActualValue() !== null
				&& MetadataTypes\ConnectionState::isValidValue($state->getActualValue())
			) {
				return MetadataTypes\ConnectionState::get($state->getActualValue());
			}
		}

		return MetadataTypes\ConnectionState::get(MetadataTypes\ConnectionState::UNKNOWN);
	}

}
