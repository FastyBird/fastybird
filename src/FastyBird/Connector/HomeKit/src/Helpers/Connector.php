<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Helpers
 * @since          0.19.0
 *
 * @date           19.09.22
 */

namespace FastyBird\Connector\HomeKit\Helpers;

use DateTimeInterface;
use Doctrine\DBAL;
use Evenement;
use FastyBird\Connector\HomeKit;
use FastyBird\Connector\HomeKit\Exceptions;
use FastyBird\Connector\HomeKit\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Devices\Queries as DevicesQueries;
use FastyBird\Module\Devices\Utilities as DevicesUtilities;
use Nette;
use Nette\Utils;
use Ramsey\Uuid;
use function assert;
use function strval;

/**
 * Useful connector helpers
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector extends Evenement\EventEmitter
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesUtilities\Database $databaseHelper,
		private readonly DevicesModels\Connectors\ConnectorsRepository $connectorsRepository,
		private readonly DevicesModels\Connectors\Properties\PropertiesRepository $propertiesRepository,
		private readonly DevicesModels\Connectors\Properties\PropertiesManager $propertiesManagers,
	)
	{
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getConfiguration(
		Uuid\UuidInterface $connectorId,
		Types\ConnectorPropertyIdentifier $type,
	): float|bool|int|string|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|DateTimeInterface|null
	{
		$findPropertyQuery = new DevicesQueries\FindConnectorProperties();
		$findPropertyQuery->byConnectorId($connectorId);
		$findPropertyQuery->byIdentifier(strval($type->getValue()));

		$configuration = $this->propertiesRepository->findOneBy(
			$findPropertyQuery,
			DevicesEntities\Connectors\Properties\Variable::class,
		);

		if ($configuration instanceof DevicesEntities\Connectors\Properties\Variable) {
			if (
				$type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_PORT
				&& $configuration->getValue() === null
			) {
				return HomeKit\Constants::DEFAULT_PORT;
			}

			if (
				$type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_PAIRED
				&& $configuration->getValue() === null
			) {
				return false;
			}

			if (
				$type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_SERVER_SECRET
				&& ($configuration->getValue() === null || $configuration->getValue() === '')
			) {
				$serverSecret = Protocol::generateSignKey();

				$this->setConfiguration($connectorId, $type, $serverSecret);

				return $serverSecret;
			}

			return $configuration->getValue();
		}

		if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_PORT) {
			return HomeKit\Constants::DEFAULT_PORT;
		}

		if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_SERVER_SECRET) {
			$serverSecret = Protocol::generateSignKey();

			$this->setConfiguration($connectorId, $type, $serverSecret);

			return $serverSecret;
		}

		return null;
	}

	/**
	 * @throws DBAL\Exception
	 * @throws DevicesExceptions\InvalidState
	 * @throws DevicesExceptions\Runtime
	 * @throws Exceptions\InvalidState
	 */
	public function setConfiguration(
		Uuid\UuidInterface $connectorId,
		Types\ConnectorPropertyIdentifier $type,
		string|int|float|bool|null $value = null,
	): void
	{
		$findConnectorProperty = new DevicesQueries\FindConnectorProperties();
		$findConnectorProperty->byConnectorId($connectorId);
		$findConnectorProperty->byIdentifier(strval($type->getValue()));

		$property = $this->propertiesRepository->findOneBy(
			$findConnectorProperty,
			DevicesEntities\Connectors\Properties\Variable::class,
		);
		assert(
			$property instanceof DevicesEntities\Connectors\Properties\Variable || $property === null,
		);

		if ($property === null) {
			if (
				$type->equalsValue(Types\ConnectorPropertyIdentifier::IDENTIFIER_SERVER_SECRET)
				|| $type->equalsValue(Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_PUBLIC_KEY)
				|| $type->equalsValue(Types\ConnectorPropertyIdentifier::IDENTIFIER_SHARED_KEY)
				|| $type->equalsValue(Types\ConnectorPropertyIdentifier::IDENTIFIER_HASHING_KEY)
			) {
				$this->databaseHelper->transaction(
					function () use ($connectorId, $type, $value): void {
						$findConnectorQuery = new DevicesQueries\FindConnectors();
						$findConnectorQuery->byId($connectorId);

						$connector = $this->connectorsRepository->findOneBy(
							$findConnectorQuery,
							HomeKit\Entities\HomeKitConnector::class,
						);

						if ($connector === null) {
							throw new Exceptions\InvalidState(
								'Connector for storing configuration could not be loaded',
							);
						}

						$configuration = $this->propertiesManagers->create(
							Utils\ArrayHash::from([
								'entity' => DevicesEntities\Connectors\Properties\Variable::class,
								'identifier' => $type->getValue(),
								'dataType' => MetadataTypes\DataType::get(
									MetadataTypes\DataType::DATA_TYPE_STRING,
								),
								'value' => $value,
								'connector' => $connector,
							]),
						);

						$this->emit('created', [$connectorId, $type, $configuration]);
					},
				);
			} else {
				throw new Exceptions\InvalidState('Connector property could not be loaded');
			}
		} else {
			$this->databaseHelper->transaction(
				function () use ($connectorId, $property, $type, $value): void {
					$configuration = $this->propertiesManagers->update(
						$property,
						Utils\ArrayHash::from(['value' => $value]),
					);

					$this->emit('updated', [$connectorId, $type, $configuration]);
				},
			);
		}
	}

}
