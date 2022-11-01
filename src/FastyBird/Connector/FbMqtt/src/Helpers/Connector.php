<?php declare(strict_types = 1);

/**
 * Connector.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Helpers
 * @since          0.25.0
 *
 * @date           04.08.22
 */

namespace FastyBird\Connector\FbMqtt\Helpers;

use DateTimeInterface;
use FastyBird\Connector\FbMqtt;
use FastyBird\Connector\FbMqtt\Types;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use Nette;
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

	/**
	 * @throws DevicesExceptions\InvalidState
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 */
	public function getConfiguration(
		FbMqtt\Entities\FbMqttConnector $connector,
		Types\ConnectorPropertyIdentifier $type,
	): float|bool|int|string|MetadataTypes\ButtonPayload|MetadataTypes\SwitchPayload|DateTimeInterface|null
	{
		$configuration = $connector->findProperty(strval($type->getValue()));

		if ($configuration instanceof DevicesEntities\Connectors\Properties\Variable) {
			if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_SERVER) {
				return is_string(
					$configuration->getValue(),
				)
					? $configuration->getValue()
					: FbMqtt\Constants::BROKER_LOCALHOST_ADDRESS;
			}

			if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_PORT) {
				return is_numeric(
					$configuration->getValue(),
				)
					? $configuration->getValue()
					: FbMqtt\Constants::BROKER_LOCALHOST_PORT;
			}

			if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_SECURED_PORT) {
				return is_numeric(
					$configuration->getValue(),
				)
					? $configuration->getValue()
					: FbMqtt\Constants::BROKER_LOCALHOST_SECURED_PORT;
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
			return FbMqtt\Constants::BROKER_LOCALHOST_ADDRESS;
		}

		if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_PORT) {
			return FbMqtt\Constants::BROKER_LOCALHOST_PORT;
		}

		if ($type->getValue() === Types\ConnectorPropertyIdentifier::IDENTIFIER_SECURED_PORT) {
			return FbMqtt\Constants::BROKER_LOCALHOST_SECURED_PORT;
		}

		return null;
	}

}
