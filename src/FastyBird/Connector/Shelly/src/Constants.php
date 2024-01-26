<?php declare(strict_types = 1);

/**
 * Constants.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     common
 * @since          1.0.0
 *
 * @date           17.07.22
 */

namespace FastyBird\Connector\Shelly;

/**
 * Connector constants
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     common
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Constants
{

	public const RESOURCES_FOLDER = __DIR__ . '/../resources';

	public const VALUE_NOT_AVAILABLE = 'n/a';

	public const WRITE_DEBOUNCE_DELAY = 2_000.0;

	public const COAP_ADDRESS = '224.0.1.187';

	public const COAP_PORT = 5_683;

	public const STATE_READING_DELAY = 5_000.0;

}
