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
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Application\Helpers as ApplicationHelpers;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Library\Metadata\Utilities as MetadataUtilities;
use FastyBird\Library\Tools\Exceptions as ToolsExceptions;
use FastyBird\Module\Devices\Documents;
use FastyBird\Module\Devices\Entities;
use FastyBird\Module\Devices\Exceptions;
use FastyBird\Module\Devices\Models;
use FastyBird\Module\Devices\Queries;
use FastyBird\Module\Devices\States;
use FastyBird\Module\Devices\Types;
use Nette;
use Nette\Utils;
use function assert;
use function strval;

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
		private readonly ApplicationHelpers\Database $databaseHelper,
	)
	{
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws ApplicationExceptions\Runtime
	 * @throws DBAL\Exception
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function setState(
		Entities\Connectors\Connector|Documents\Connectors\Connector $connector,
		Types\ConnectionState $state,
	): bool
	{
		$findConnectorPropertyQuery = new Queries\Configuration\FindConnectorDynamicProperties();
		$findConnectorPropertyQuery->byConnectorId($connector->getId());
		$findConnectorPropertyQuery->byIdentifier(MetadataTypes\ConnectorPropertyIdentifier::STATE);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findConnectorPropertyQuery,
			Documents\Connectors\Properties\Dynamic::class,
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
						'dataType' => MetadataTypes\DataType::ENUM,
						'unit' => null,
						'format' => [
							Types\ConnectionState::RUNNING->value,
							Types\ConnectionState::STOPPED->value,
							Types\ConnectionState::UNKNOWN->value,
							Types\ConnectionState::SLEEPING->value,
							Types\ConnectionState::ALERT->value,
						],
						'settable' => false,
						'queryable' => false,
					]));
					assert($property instanceof Entities\Connectors\Properties\Dynamic);

					return $property;
				},
			);
		}

		$property = $this->connectorsPropertiesConfigurationRepository->find($property->getId());
		assert($property instanceof Documents\Connectors\Properties\Dynamic);

		$this->propertiesStatesManager->set(
			$property,
			Utils\ArrayHash::from([
				States\Property::ACTUAL_VALUE_FIELD => $state->value,
				States\Property::EXPECTED_VALUE_FIELD => null,
			]),
			MetadataTypes\Sources\Module::get(MetadataTypes\Sources\Module::DEVICES),
		);

		return false;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Mapping
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function getState(
		Entities\Connectors\Connector|Documents\Connectors\Connector $connector,
	): Types\ConnectionState
	{
		$findConnectorPropertyQuery = new Queries\Configuration\FindConnectorDynamicProperties();
		$findConnectorPropertyQuery->byConnectorId($connector->getId());
		$findConnectorPropertyQuery->byIdentifier(MetadataTypes\ConnectorPropertyIdentifier::STATE);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findConnectorPropertyQuery,
			Documents\Connectors\Properties\Dynamic::class,
		);

		if ($property instanceof Documents\Connectors\Properties\Dynamic) {
			$state = $this->propertiesStatesManager->readState($property);

			if (
				$state?->getRead()->getActualValue() !== null
				&& Types\ConnectionState::tryFrom(
					strval(MetadataUtilities\Value::flattenValue($state->getRead()->getActualValue())),
				) !== null
			) {
				return Types\ConnectionState::tryFrom(
					strval(MetadataUtilities\Value::flattenValue($state->getRead()->getActualValue())),
				);
			}
		}

		return Types\ConnectionState::UNKNOWN;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Mapping
	 * @throws MetadataExceptions\MalformedInput
	 * @throws ToolsExceptions\InvalidArgument
	 */
	public function isRunning(
		Entities\Devices\Device|Documents\Connectors\Connector $connector,
	): bool
	{
		$findDevicePropertyQuery = new Queries\Configuration\FindConnectorProperties();
		$findDevicePropertyQuery->byConnectorId($connector->getId());
		$findDevicePropertyQuery->byIdentifier(MetadataTypes\ConnectorPropertyIdentifier::STATE);

		$property = $this->connectorsPropertiesConfigurationRepository->findOneBy(
			$findDevicePropertyQuery,
			Documents\Connectors\Properties\Dynamic::class,
		);

		if ($property instanceof Documents\Connectors\Properties\Dynamic) {
			$state = $this->propertiesStatesManager->readState($property);

			if (
				$state?->getRead()->getActualValue() !== null
				&& Types\ConnectionState::tryFrom(
					strval(MetadataUtilities\Value::flattenValue($state->getRead()->getActualValue())),
				) !== null
				&& $state->getRead()->getActualValue() === Types\ConnectionState::RUNNING->value
			) {
				return true;
			}
		}

		return false;
	}

}
