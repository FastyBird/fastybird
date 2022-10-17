<?php declare(strict_types = 1);

/**
 * ConnectorPropertyIdentifier.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Metadata!
 * @subpackage     Types
 * @since          0.40.0
 *
 * @date           08.02.22
 */

namespace FastyBird\Metadata\Types;

use Consistence;
use function strval;

/**
 * Connector property identifier types
 *
 * @package        FastyBird:Metadata!
 * @subpackage     Types
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorPropertyIdentifier extends Consistence\Enum\Enum
{

	/**
	 * Define connector properties identifiers
	 */
	public const IDENTIFIER_STATE = PropertyIdentifier::IDENTIFIER_STATE;

	public const IDENTIFIER_SERVER = PropertyIdentifier::IDENTIFIER_SERVER;

	public const IDENTIFIER_PORT = PropertyIdentifier::IDENTIFIER_PORT;

	public const IDENTIFIER_SECURED_PORT = PropertyIdentifier::IDENTIFIER_SECURED_PORT;

	public const IDENTIFIER_BAUD_RATE = PropertyIdentifier::IDENTIFIER_BAUD_RATE;

	public const IDENTIFIER_INTERFACE = PropertyIdentifier::IDENTIFIER_INTERFACE;

	public const IDENTIFIER_ADDRESS = PropertyIdentifier::IDENTIFIER_ADDRESS;

	public function __toString(): string
	{
		return strval(self::getValue());
	}

}
