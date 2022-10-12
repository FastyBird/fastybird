<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Helpers
 * @since          0.25.0
 *
 * @date           04.08.22
 */

namespace FastyBird\FbMqttConnector\Helpers;

use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\FbMqttConnector;
use FastyBird\FbMqttConnector\Types;
use FastyBird\Metadata\Entities as MetadataEntities;
use FastyBird\Metadata\Exceptions as MetadataExceptions;
use Nette;
use Ramsey\Uuid;
use function is_numeric;
use function is_string;
use function strval;

/**
 * Useful connector helpers
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Helpers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Connector
{

	use Nette\SmartObject;

	public function __construct(
		private readonly DevicesModuleModels\DataStorage\ConnectorPropertiesRepository $propertiesRepository,
	)
	{
	}

	/**
	 * @param Uuid\UuidInterface $connectorId
	 * @param Types\ConnectorPropertyIdentifier $type
	 *
	 * @return float|bool|int|string|null
	 */

	/**
	 * @throws MetadataExceptions\FileNotFound
	 */
	public function getConfiguration(
		Uuid\UuidInterface $connectorId,
		Types\ConnectorPropertyIdentifier $type,
	): float|bool|int|string|null
	{
		$configuration = $this->propertiesRepository->findByIdentifier($connectorId, strval($type->getValue()));

		if ($configuration instanceof MetadataEntities\DevicesModule\ConnectorVariableProperty) {
			if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_SERVER) {
				return is_string(
					$configuration->getValue(),
				)
					? $configuration->getValue()
					: FbMqttConnector\Constants::BROKER_LOCALHOST_ADDRESS;
			}

			if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_PORT) {
				return is_numeric(
					$configuration->getValue(),
				)
					? $configuration->getValue()
					: FbMqttConnector\Constants::BROKER_LOCALHOST_PORT;
			}

			if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_SECURED_PORT) {
				return is_numeric(
					$configuration->getValue(),
				)
					? $configuration->getValue()
					: FbMqttConnector\Constants::BROKER_LOCALHOST_SECURED_PORT;
			}

			if (
				$type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_USERNAME
				|| $type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_PASSWORD
			) {
				return is_string($configuration->getValue()) ? $configuration->getValue() : null;
			}

			if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_PROTOCOL_VERSION) {
				return Types\ProtocolVersion::isValidValue(
					$configuration->getValue(),
				)
					? $configuration->getValue()
					: null;
			}

			return $configuration->getValue();
		}

		if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_SERVER) {
			return FbMqttConnector\Constants::BROKER_LOCALHOST_ADDRESS;
		}

		if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_PORT) {
			return FbMqttConnector\Constants::BROKER_LOCALHOST_PORT;
		}

		if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_SECURED_PORT) {
			return FbMqttConnector\Constants::BROKER_LOCALHOST_SECURED_PORT;
		}

		return null;
	}

}
