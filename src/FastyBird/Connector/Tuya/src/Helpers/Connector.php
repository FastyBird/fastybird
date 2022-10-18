<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Helpers
 * @since          0.13.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Connector\Tuya\Helpers;

use FastyBird\Connector\Tuya\Types;
use FastyBird\DevicesModule\Exceptions as DevicesModuleExceptions;
use FastyBird\DevicesModule\Models as DevicesModuleModels;
use FastyBird\Library\Metadata\Entities as MetadataEntities;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use Nette;
use Ramsey\Uuid;
use function strval;

/**
 * Useful connector helpers
 *
 * @package        FastyBird:TuyaConnector!
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
	 * @throws DevicesModuleExceptions\InvalidState
	 * @throws MetadataExceptions\FileNotFound
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidData
	 * @throws MetadataExceptions\InvalidState
	 * @throws MetadataExceptions\Logic
	 * @throws MetadataExceptions\MalformedInput
	 */
	public function getConfiguration(
		Uuid\UuidInterface $connectorId,
		Types\ConnectorPropertyIdentifier $type,
	): float|bool|int|string|null
	{
		$configuration = $this->propertiesRepository->findByIdentifier($connectorId, strval($type->getValue()));

		if ($configuration instanceof MetadataEntities\DevicesModule\ConnectorVariableProperty) {
			if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_CLIENT_MODE) {
				return Types\ClientMode::isValidValue($configuration->getValue()) ? $configuration->getValue() : null;
			}

			if (
				$type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENAPI_ENDPOINT
				&& !Types\OpenApiEndpoint::isValidValue($configuration->getValue())
			) {
				return Types\OpenApiEndpoint::ENDPOINT_EUROPE;
			}

			if (
				$type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENPULSAR_ENDPOINT
				&& !Types\OpenPulsarEndpoint::isValidValue($configuration->getValue())
			) {
				return Types\OpenPulsarEndpoint::ENDPOINT_EUROPE;
			}

			if (
				$type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENPULSAR_TOPIC
				&& !Types\OpenPulsarEndpoint::isValidValue($configuration->getValue())
			) {
				return Types\OpenPulsarTopic::TOPIC_PROD;
			}

			return $configuration->getValue();
		}

		if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENAPI_ENDPOINT) {
			return Types\OpenApiEndpoint::ENDPOINT_EUROPE;
		}

		if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENPULSAR_ENDPOINT) {
			return Types\OpenPulsarEndpoint::ENDPOINT_EUROPE;
		}

		if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_OPENPULSAR_TOPIC) {
			return Types\OpenPulsarTopic::TOPIC_PROD;
		}

		return null;
	}

}
