<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     common
 * @since          0.1.0
 *
 * @date           23.02.20
 */

namespace FastyBird\Connector\FbMqtt;

/**
 * Service constants
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	/**
	 * MQTT topic delimiter
	 */
	public const MQTT_TOPIC_DELIMITER = '/';

	/**
	 * MQTT api topic prefix
	 */
	public const MQTT_API_PREFIX = self::MQTT_TOPIC_DELIMITER . 'fb';

	/**
	 * MQTT protocol api prefixes
	 */
	public const MQTT_API_V1_VERSION_PREFIX = self::MQTT_TOPIC_DELIMITER . Types\ProtocolVersion::VERSION_1;

	/**
	 * MQTT message QOS values
	 */
	public const MQTT_API_QOS_0 = 0;

	public const MQTT_API_QOS_1 = 1;

	public const MQTT_API_QOS_2 = 2;

	/**
	 * Payloads
	 */

	public const PAYLOAD_BOOL_TRUE_VALUE = 'true';

	public const PAYLOAD_BOOL_FALSE_VALUE = 'false';

	public const VALUE_NOT_SET = 'noneprivate readonly';

	public const BROKER_LOCALHOST_ADDRESS = '127.0.0.1';

	public const BROKER_LOCALHOST_PORT = 1_883;

	public const BROKER_LOCALHOST_SECURED_PORT = 8_883;

}
