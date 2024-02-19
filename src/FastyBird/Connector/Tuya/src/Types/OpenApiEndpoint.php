<?php declare(strict_types = 1);

/**
 * OpenApiEndpoint.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 * @since          1.0.0
 *
 * @date           25.08.22
 */

namespace FastyBird\Connector\Tuya\Types;

/**
 * OpenAPI endpoint types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
enum OpenApiEndpoint: string
{

	case CHINA = 'https://openapi.tuyacn.com';

	case AMERICA = 'https://openapi.tuyaus.com';

	case AMERICA_AZURE = 'https://openapi-ueaz.tuyaus.com';

	case EUROPE = 'https://openapi.tuyaeu.com';

	case EUROPE_MS = 'https://openapi-weaz.tuyaeu.com';

	case INDIA = 'https://openapi.tuyain.com';

}
