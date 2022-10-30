<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Helpers
 * @since          0.13.0
 *
 * @date           24.08.22
 */

namespace FastyBird\Connector\Tuya\Helpers;

use DateTimeInterface;
use FastyBird\Connector\Tuya\Entities;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
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

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getConfiguration(
		Entities\TuyaConnector $connector,
		Types\ConnectorPropertyIdentifier $type,
	): float|bool|int|string|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|DateTimeInterface|null
	{
		$configuration = $connector->findProperty(strval($type->getValue()));

		if ($configuration instanceof DevicesEntities\Connectors\Properties\Variable) {
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
