<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           23.12.23
 */

namespace FastyBird\Connector\Zigbee2Mqtt;

/**
 * Service constants
 *
 * @package        FastyBird:Zigbee2MqttConnector!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	/**
	 * Zigbee2MQTT topics subscribe format
	 */
	public const BRIDGE_TOPIC = '%s/#';

	/**
	 * MQTT topic delimiter
	 */
	public const MQTT_TOPIC_DELIMITER = '/';

	/**
	 * MQTT message QOS values
	 */
	public const MQTT_API_QOS_0 = 0;

	public const MQTT_API_QOS_1 = 1;

	public const MQTT_API_QOS_2 = 2;

	public const VALUE_NOT_AVAILABLE = 'n/a';

	public const CHANNEL_IDENTIFIER_PATTERN = '%s_%s';

	public const CHANNEL_SPECIAL_IDENTIFIER_PATTERN = '%s_%s_%s';

	public const CHANNEL_IDENTIFIER_REGEX = '/^(?P<type>(binary|numeric|enum|text|composite){1})_(?P<identifier>[a-z0-9_]+)$/';
	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public const CHANNEL_SPECIAL_IDENTIFIER_REGEX = '/^(?P<type>(light|switch|fan|cover|lock|climate){1})_(?P<subtype>(binary|numeric|enum|text|composite){1})_(?P<identifier>[a-z0-9_]+)$/';

	public const IEEE_ADDRESS_REGEX = '/^0x([a-zA-Z0-9]+)$/';

}
