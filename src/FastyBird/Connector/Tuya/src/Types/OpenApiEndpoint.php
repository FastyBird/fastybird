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

use Consistence;
use function strval;

/**
 * OpenAPI endpoint types
 *
 * @package        FastyBird:TuyaConnector!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class OpenApiEndpoint extends Consistence\Enum\Enum
{

	public const CHINA = 'https://openapi.tuyacn.com';

	public const AMERICA = 'https://openapi.tuyaus.com';

	public const AMERICA_AZURE = 'https://openapi-ueaz.tuyaus.com';

	public const EUROPE = 'https://openapi.tuyaeu.com';

	public const EUROPE_MS = 'https://openapi-weaz.tuyaeu.com';

	public const INDIA = 'https://openapi.tuyain.com';

	public function getValue(): string
	{
		return strval(parent::getValue());
	}

	public function __toString(): string
	{
		return self::getValue();
	}

}
